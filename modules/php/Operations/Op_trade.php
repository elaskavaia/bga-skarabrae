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

class Op_trade extends Operation {
    function resolve() {
        $owner = $this->getOwner();
        if ($this->getDataField("paid", false)) {
            $type = $this->getType();
            $value = $this->game->tokens->getTrackerValue($owner, $type);
            $value++;

            $this->queue("tradeInc");

            $state = $this->game->getActionTileSide("action_main_5_{$owner}");

            $good = $this->game->getRulesFor("slot_trade_$value", "craft", "nop");

            if ($state) {
                $rules = "?(payAny:$good)";
            } else {
                $rules = "?(n_skaill:$good)";
            }
            $this->queue($rules, $owner, null, "action_main_5_{$owner}");
        } else {
            $this->queue("tradePay", $owner, null, $this->getReason());
            $this->queue("trade", $owner, ["paid" => true], $this->getReason());
        }
    }

    function getPossibleMoves() {
        if (!$this->getDataField("paid", false)) {
            $owner = $this->getOwner();
            $op = $this->game->machine->instanciateOperation("tradePay", $owner);
            if ($op->isVoid()) {
                return ["err" => $op->getError() ?: "err"];
            }
        }
        return parent::getPossibleMoves();
    }
}
