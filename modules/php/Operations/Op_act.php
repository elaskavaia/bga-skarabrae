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

/** Standard action */
class Op_act extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner", 1);
        if (count($workers) == 0) {
            return ["err" => clienttranslate("No available workers")];
        }
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
        $locs = $this->game->tokens->getReverseLocationTokensMapping($workersf);
        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("action", "tableau_$owner"));
        $res = [];
        foreach ($keys as $act) {
            $res[$act] = [
                "name" => $this->game->tokens->getTokenName($act),
                "q" => 0,
            ];
            $occupied = $locs[$act] ?? [];
            $countw = count($occupied);
            if ($countw >= 2) {
                $res[$act]["q"] = Material::MA_ERR_OCCUPIED;
                continue;
            }

            if ($countw >= 1) {
                if (!$largeWorker) {
                    $res[$act]["q"] = Material::MA_ERR_OCCUPIED;
                    continue;
                }
                $res[$act]["worker"] = $largeWorker;
            } else {
                $res[$act]["worker"] = $anyWorker;
            }
            $state = $this->game->getActionTileSide($act);
            $rulesField = "r";
            if ($state) {
                $rulesField = "rb";
            }
            $rules = $this->game->getRulesFor($act, $rulesField, "nop");
            $op = $this->game->machine->instanciateOperation($rules, $owner);

            if ($op->isVoid()) {
                $res[$act]["err"] = $op->getError();
                $res[$act]["q"] = 1;
            }
        }
        return $res;
    }
    function getUiArgs() {
        return ["buttons" => false];
    }
    function resolve() {
        $owner = $this->getOwner();
        $args = $this->getArgs();
        $action_tile = $this->getCheckedArg();
        $worker = $args["info"][$action_tile]["worker"];
        $this->game->tokens->dbSetTokenLocation($worker, $action_tile, 1);
        $side = $this->game->getActionTileSide($action_tile);
        if ($side) {
            $r = $this->game->getRulesFor($action_tile, "rb");
        } else {
            $r = $this->game->getRulesFor($action_tile, "r");
        }
        $this->queue($r, $owner, [], $action_tile);

        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", "tableau_$owner", 1);
        $worker = array_shift($workers);
        if ($worker) {
            $this->queue($this->getType(), $owner);
        }
    }

    public function getPrompt() {
        return clienttranslate("Select an action for worker");
    }

    public function canSkip() {
        return true;
    }
}
