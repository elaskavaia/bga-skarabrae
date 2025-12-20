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

use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\Operation;
use BgaSystemException;

class Op_tend extends CountableOperation {
    function resolve() {
        $this->notifyMessage(""); // empty message
        throw new BgaSystemException("not impl");
        return;
    }

    public function getPossibleMoves() {
        $owner = $this->getOwner();
        $res = [];
        for ($i = 6; $i <= 9; $i++) {
            $id = "action_main_$i";
            $res[$id] = [
                "name" => $this->game->tokens->getTokenName($id),
                "q" => 0,
            ];
        }
        $res["midden"] = [
            "name" => $this->game->tokens->getTokenName("Op_n_midden"),
            "token_div" => "tracker_midden_$owner",
            "q" => 0,
        ];
        return $res;
    }

    public function getPrompt() {
        return clienttranslate("Select action");
    }
}
