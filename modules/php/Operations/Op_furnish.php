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

class Op_furnish extends Operation {
    function getArgType() {
        return Operation::ARG_TOKEN;
    }

    function getPossibleMoves() {
        if ($this->isPaid()) {
            return [Operation::TARGET_AUTO];
        }
        $owner = $this->getOwner();
        $cost = $this->getCost();
        if ($this->game->machine->instanciateOperation($cost, $owner)->isVoid()) {
            return ["err" => clienttranslate("Cannot afford")];
        }
        return [Operation::TARGET_AUTO];
    }

    function getCost() {
        $owner = $this->getOwner();
        $value = $this->game->tokensmop->getTrackerValue($owner, "furnish");
        $value += 1;
        $multi = [0, 1, 2, 2, 2, 3, 3];
        $coeff = $multi[$value];
        return "{$coeff}n_hide";
    }

    function isPaid() {
        return $this->getDataField("paid", false);
    }
    function resolve(mixed $data = []) {
        if ($this->isPaid()) {
            $this->game->effect_incTrack($this->getOwner(), $this->getType(), 1, $this->getReason());
        } else {
            $this->queue($this->getCost(), $this->getOwner(), [], $this->getOpId());
            $this->queue("furnish", $this->getOwner(), ["paid" => true]);
        }
        return;
    }
}
