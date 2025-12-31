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

/** Calculate and push payment for trade */
class Op_tradePay extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "trade");
        if ($value >= 7) {
            return ["q" => Material::MA_ERR_MAX];
        }
        $cost = $this->getCostOp();
        if ($this->game->machine->instanciateOperation($cost, $owner)->isVoid()) {
            return ["q" => Material::MA_ERR_COST];
        }

        return [Operation::TARGET_CONFIRM];
    }

    function getDiscount() {
        $dis = $this->getDataField("dis", 0);
        return $dis;
    }

    function getCount() {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "trade", 0);
        $this->game->systemAssert("", $value >= 0);
        $value += 1;
        $coeff = $this->game->getRulesFor("slot_trade_$value", "rb") - $this->getDiscount();
        return $coeff;
    }

    function getCostOp() {
        $n = $this->getCount();
        if ($n <= 0) {
            return "nop";
        }
        return "{$n}payAny";
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $this->queue($this->getCostOp(), $this->getOwner(), [], "action_main_5_{$owner}");
    }
}
