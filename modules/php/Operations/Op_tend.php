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

use Bga\Games\skarabrae\Material;
use Bga\Games\skarabrae\OpCommon\CountableOperation;

class Op_tend extends CountableOperation {
    function resolve(): void {
        $uargs = $this->getCheckedArg(true);
        $nc = 0;

        foreach ($uargs as $arg => $count) {
            if (str_starts_with($arg, "tracker_midden")) {
                $this->queue("{$count}n_midden");
            } else {
                $action_tile = $arg;
                $this->withDataField($action_tile, 1);
                $owner = $this->getOwner();
                $r = $this->game->getActionRules($action_tile);
                $this->queue($r, $owner, [], $action_tile);
            }
            $nc = $this->incCount(-$count);
            $this->incMinCount(-$count);
        }

        if ($nc > 0) {
            $this->saveToDb($this->queueRank, true);
        }
    }

    public function getPossibleMoves() {
        $owner = $this->getOwner();
        $res = [];
        for ($i = 6; $i <= 9; $i++) {
            $id = "action_main_{$i}_{$owner}";

            $res[$id] = [
                "name" => $this->game->tokens->getTokenName($id),
                "q" => 0,
                "max" => 1,
            ];
            if ($this->getDataField($id, 0)) {
                $res[$id]["q"] = Material::MA_ERR_MAX;
            }
        }
        [$midden_id, $midden_value] = $this->game->getTrackerIdAndValue($owner, "midden");
        $res[$midden_id] = [
            "name" => clienttranslate("Clean"),
            "max" => $this->getCount(),
            "q" => 0,
        ];
        return $res;
    }

    function getUiArgs() {
        return ["replicate" => false];
    }

    public function getArgType() {
        return parent::TTYPE_TOKEN_COUNT;
    }
    public function canSkip() {
        return true;
    }
    public function getPrompt() {
        return clienttranslate('Select unique gather or clean (count: ${count})');
    }
}
