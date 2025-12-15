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

class Op_craft extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getPrompt() {
        return "Select an Action Tile to turn over";
    }

    function getPossibleMoves() {
        $card = $this->getCard();
        if ($this->isPaid() && $card) {
            return [$card];
        }
        $owner = $this->getOwner();
        if ($card) {
            $cost = $this->getCost();
            if ($this->game->machine->instanciateOperation($cost, $owner)->isVoid()) {
                return ["q" => Material::MA_ERR_COST];
            }
            return [$card];
        }
        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("action", "tableau_$owner"));

        $res = [];
        foreach ($keys as $card) {
            $res[$card] = [
                "name" => $this->game->tokens->getTokenName($card),
                "q" => 0,
            ];
            $state = $this->game->getActionTileSide($card);

            if ($state) {
                // already flipped
                $res[$card]["q"] = Material::MA_ERR_NOT_APPLICABLE;
                continue;
            }
            $cost = $this->game->getRulesFor($card, "craft");
            $this->game->systemAssert("Cannot determine cost for $card", $cost);
            $op = $this->game->machine->instanciateOperation($cost, $owner);

            if ($op->isVoid()) {
                $res[$card]["err"] = $op->getError();
                $res[$card]["q"] = 1;
            }
            $res[$card]["cost"] = $cost;
        }
        return $res;
    }

    function isPaid() {
        return $this->getDataField("paid", false) || $this->getParams() == "paid";
    }
    function getCard() {
        return $this->getDataField("card", false);
    }
    function getCost() {
        if ($this->isPaid()) {
            return "nop";
        }
        $card = $this->getCard();
        $cost = $this->game->getRulesFor($card, "craft");
        $this->game->systemAssert("Cannot determine cost for $card", $cost);
        return $cost;
    }

    public function requireConfirmation() {
        if ($this->isPaid()) {
            return false;
        }
        return true;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }
    function resolve() {
        if ($this->isPaid()) {
            $card = $this->getDataField("card");
            $this->game->tokens->dbSetTokenState($card, 1, clienttranslate('${player_name} crafts ${token_name}'));
        } else {
            $card = $this->getCheckedArg();
            $cost = $this->game->getRulesFor($card, "craft", "");
            $this->queue($cost, $this->getOwner(), [], $this->getOpId());
            $this->queue($this->getType(), $this->getOwner(), ["paid" => true, "card" => $card]);
        }
        return;
    }
}
