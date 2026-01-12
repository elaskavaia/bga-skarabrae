<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * skarabrae implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * skarabrae.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\skarabrae\Operations;

use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Material;

use function Bga\Games\skarabrae\getPart;

/** Standard action */
class Op_act extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $taskavail = $this->isTaskAvailable();

        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner", 1);
        if (count($workers) == 0 && !$taskavail) {
            return ["err" => clienttranslate("No available workers")];
        }
        $res = [];
        if (count($workers) > 0) {
            $largeWorker = null;
            $anyWorker = null;
            foreach ($workers as $worker => $info) {
                if (str_starts_with($worker, "worker_1")) {
                    $largeWorker = $worker;
                } else {
                    $anyWorker = $worker;
                }
            }
            if ($anyWorker == null) {
                $anyWorker = $largeWorker;
            }
            $workersf = $this->game->tokens->getTokensOfTypeInLocation("worker%_$owner", null, 1);
            $workersPerAction = 2;
            if ($this->game->hasSpecial(3, $owner)) {
                // recruit
                $workers_extra = $this->game->tokens->getTokensOfTypeInLocation("worker%_000000", null, 1);
                $workersf = array_merge($workersf, $workers_extra);
                if ($this->game->getActionTileSide("action_special_3")) {
                    $workersPerAction = 3;
                }
            }
            $locs = $this->game->tokens->getReverseLocationTokensMapping($workersf);
            $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("action", "tableau_$owner"));

            foreach ($keys as $act) {
                $res[$act] = [
                    "name" => $this->game->tokens->getTokenName($act),
                    "q" => 0,
                ];
                $occupied = $locs[$act] ?? [];
                $countw = count($occupied);
                if ($countw >= $workersPerAction) {
                    $res[$act]["q"] = Material::MA_ERR_OCCUPIED;
                    continue;
                }

                if ($countw >= $workersPerAction - 1) {
                    if (!$largeWorker) {
                        $res[$act]["q"] = Material::MA_ERR_OCCUPIED;
                        continue;
                    }
                    $res[$act]["worker"] = $largeWorker;
                } else {
                    $res[$act]["worker"] = $anyWorker;
                }
                $rules = $this->game->getActionRules($act);
                $res[$act]["r"] = $rules;

                $op = $this->game->machine->instanciateOperation($rules, $owner, ["reason" => $act]);

                if ($op->isVoid()) {
                    $res[$act]["err"] = $op->getError();
                    $res[$act]["q"] = 1;
                }
            }
        }

        if ($taskavail) {
            return $res + $this->game->machine->instanciateOperation("task", $owner)->getPossibleMoves();
        }

        return $res;
    }

    function getSkipArgs() {
        $res = parent::getSkipArgs();
        $count = $this->getArgs()["count_workers"] ?? "?";
        if ($count) {
            $name = clienttranslate("Skip placing workers");
            $res["confirm"] = clienttranslate("You will forfeit placing all remaining worker. This is not a good idea in general");
        } else {
            $name = clienttranslate("Skip");
        }
        $res["name"] = $name;
        return $res;
    }
    function isTaskAvailable() {
        $owner = $this->getOwner();
        if (!$this->game->isSolo()) {
            return false;
        }
        $taskop = $this->game->machine->instanciateOperation("task", $owner);
        return !$taskop->noValidTargets();
    }
    function getUiArgs() {
        return ["buttons" => false];
    }
    function activateAction($action_tile) {
        $owner = $this->getOwner();
        $r = $this->game->getActionRules($action_tile);
        $this->queue($r, $owner, [], $action_tile);
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $args = $this->getArgs();
        $action_tile = $this->getCheckedArg();
        if (str_starts_with($action_tile, "card_task")) {
            $this->queue("task", $owner, ["card" => $action_tile]);
            $this->queue($this->getType(), $owner);
            return;
        }
        $worker = $args["info"][$action_tile]["worker"];
        $this->game->tokens->dbSetTokenLocation($worker, $action_tile, 1);

        $this->activateAction($action_tile);

        if (str_starts_with($action_tile, "action_main")) {
            $num = getPart($action_tile, 2);
            $stat = "game_action_$num";
        } else {
            $stat = "game_action_10";
        }

        $val = 1 + (int) $this->game->tokens->db->getTokenState("{$stat}_{$owner}");
        $this->game->tokens->db->createTokenIfNot("{$stat}_{$owner}", "stat", $val);
        $this->game->playerStats->set($stat, $val, $this->getPlayerId());

        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner", 1);
        $worker = array_shift($workers);

        $this->queue($this->getType(), $owner);
    }

    public function getPrompt() {
        if ($this->isTaskAvailable()) {
            return clienttranslate("Select an action for worker or task");
        }

        return clienttranslate("Select an action for worker");
    }

    public function getExtraArgs() {
        $owner = $this->getOwner();
        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner", 1);
        $count = count($workers);
        return [
            "count_workers" => $count,
        ];
    }
    public function canSkip() {
        return true;
    }
}
