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
use BgaUserException;

use function Bga\Games\skarabrae\toJson;

/** Task */
class Op_task extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $card = $this->getCard();
        //throw new BgaUserException($card . " a");
        if ($card) {
            return [$card];
        }

        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("card_task", "tableau_$owner", 0));
        $res = [];
        foreach ($keys as $act) {
            $res[$act] = [
                "name" => $this->game->tokens->getTokenName($act),
                "q" => 0,
                "call" => $this->getType(),
            ];

            $rules = $this->game->getRulesFor($act, "r", "");
            $this->game->systemAssert("no rules for $act", $rules);

            $op = $this->game->machine->instanciateOperation($rules, $owner);

            if ($op->isVoid()) {
                $res[$act]["err"] = $op->getError();
                $res[$act]["q"] = Material::MA_ERR_COST;
                $res[$act]["r"] = $rules;
            }
        }
        return $res;
    }
    function getUiArgs() {
        return ["buttons" => false];
    }

    function getCard() {
        return $this->getDataField("card", null);
    }
    function resolve() {
        $owner = $this->getOwner();
        $action_tile = $this->getCheckedArg();
        $this->game->tokens->dbSetTokenState($action_tile, 1, clienttranslate('${player_name} completes task ${token_name}'));
        $r = $this->game->getRulesFor($action_tile, "r");
        $this->queue($r, $owner, [], $action_tile);
    }

    public function getPrompt() {
        return clienttranslate("Select a task card");
    }

    public function requireConfirmation() {
        if ($this->getCard()) {
            return false;
        }
        return true;
    }

    public function canSkip() {
        if ($this->getCard()) {
            return false;
        }
        return true;
    }
}
