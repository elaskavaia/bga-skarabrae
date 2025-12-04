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

class Op_trade extends Operation {
    function resolve() {
        $owner = $this->getOwner();
        $type = $this->getType();
        $value = $this->game->tokens->getTrackerValue($owner, $type);
        $value++;
        $this->game->userAssert("Maximum is reached", $value < 7); // NOI18N
        $this->queue("tradeInc");

        $state = $this->game->tokens->tokens->getTokenState("action_main_5");

        $good = $this->game->getRulesFor("slot_trade_$value", "craft", "nop");

        if ($state) {
            $rules = "?(payAny:$good)";
        } else {
            $rules = "?(n_skaill:$good)";
        }
        $this->queue($rules);
        return;
    }
}
