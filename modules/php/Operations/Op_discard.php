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
use Bga\Games\skarabrae\OpCommon\CountableOperation;

class Op_discard extends CountableOperation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getPrompt() {
        return "Select a settler to discard";
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("card_setl", "tableau_$owner"));

        $res = [];
        $set = [];
        foreach ($keys as $card) {
            $t = $this->game->getRulesFor($card, "t");
            $set[$t] = $card;
        }
        foreach ($set as $card) {
            $res[$card] = [
                "name" => $this->game->tokens->getTokenName($card),
                "q" => 0,
            ];
        }
        return $res;
    }

    public function requireConfirmation() {
        return true;
    }
    // public function getUiArgs() {
    //     return ["buttons" => false];
    // }
    function resolve() {
        $card = $this->getCheckedArg();

        $this->game->tokens->dbSetTokenLocation($card, "discard_village", 0, clienttranslate('${player_name} discards ${token_name}'));
        $rem = $this->getCount() - 1;
        if ($rem > 0) {
            $this->queue("$rem" . $this->getType());
        }
    }
}
