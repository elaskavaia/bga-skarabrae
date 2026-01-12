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
use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\OpCard;

class Op_muster extends OpCard {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getPrompt() {
        return clienttranslate("Select an environment to activate");
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_setl", "tableau_$owner", null, "token_state");

        $res = [];
        $set = [];
        foreach ($tokens as $info) {
            $card = $info["key"];
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
    public function getUiArgs() {
        return ["buttons" => false];
    }
    function resolve(): void {
        $card = $this->getCheckedArg();
        $owner = $this->getOwner();
        $and = !!$this->game->getActionTileSide("action_special_5");
        $this->effect_settlerCard($owner, $card, $and ? 3 : 1);
    }
}
