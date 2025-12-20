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
    function resolve() {
        $arg = $this->getCheckedArg();
        $nc = $this->incCount(-1);
        $this->incMinCount(-1);

        if ($arg == "midden") {
            $this->queue("n_midden");
            return;
        }
        $action_tile = $arg;
        $owner = $this->getOwner();
        $r = $this->game->getActionRules($action_tile);
        $this->queue($r, $owner, [], $action_tile);

        if ($nc > 0) {
            $this->withDataField($arg, 1);
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
            ];
            if ($this->getDataField($id, 0)) {
                $res[$id]["q"] = Material::MA_ERR_MAX;
            }
        }
        $res["midden"] = [
            "name" => $this->game->tokens->getTokenName("Op_n_midden"),
            "token_div" => "tracker_midden_$owner",
            "q" => 0,
        ];
        return $res;
    }

    public function canSkip() {
        return true;
    }
    public function getPrompt() {
        return clienttranslate('Select unique gather or clean (${count} left)');
    }
}
