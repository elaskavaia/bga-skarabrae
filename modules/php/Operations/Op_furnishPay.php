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

/** Calculate and push payment for furnish */
class Op_furnishPay extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getDiscount() {
        $dis = $this->getDataField("dis", 0);
        return $dis;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $cost = $this->getCost();
        $value = $this->game->tokens->getTrackerValue($owner, "furnish");
        if ($value >= 6) {
            return ["q" => Material::MA_ERR_MAX];
        }
        if ($this->game->machine->instanciateOperation($cost, $owner)->isVoid()) {
            return ["q" => Material::MA_ERR_COST];
        }
        return [Operation::TARGET_AUTO];
    }

    function getCost() {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "furnish");
        $value += 1;
        $multi = [0, 1, 2, 2, 2, 3, 3];
        $coeff = $multi[$value];
        $coeff -= $this->getDiscount();
        if ($coeff <= 0) {
            return "nop";
        }
        return "{$coeff}(n_hide/2n_wool)";
    }

    function resolve() {
        $this->queue($this->getCost(), $this->getOwner(), [], $this->getOpId());
    }
}
