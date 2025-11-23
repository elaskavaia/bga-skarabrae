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

use Bga\Games\skarabrae\Common\Operation;
use Bga\Games\skarabrae\Material;

/** Calculate and push payment for trade */
class Op_tradePay extends Operation {
    function getArgType() {
        return Operation::ARG_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "trade");
        if ($value >= 6) {
            return ["q" => Material::MA_ERR_MAX];
        }
        $cost = $this->getCostOp();
        if ($this->game->machine->instanciateOperation($cost)->isVoid()) {
            return ["q" => Material::MA_ERR_COST];
        }

        return [Operation::TARGET_CONFIRM];
    }

    function getDiscount() {
        $dis = $this->getDataField("dis", false) || $this->getParams() == "dis";
        if ($dis) {
            return 2;
        }
        return 0;
    }

    function getCount() {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "trade", 0);
        $this->game->systemAssert("", $value >= 0);
        $value += 1;
        $multi = [0, 2, 3, 4, 4, 5, 5, 6];
        $coeff = $multi[$value] - $this->getDiscount();
        return $coeff;
    }

    function getCostOp() {
        $n = $this->getCount();
        return "{$n}payAny";
    }

    function resolve() {
        $this->queue($this->getCostOp(), $this->getOwner(), [], "trade");
    }
}
