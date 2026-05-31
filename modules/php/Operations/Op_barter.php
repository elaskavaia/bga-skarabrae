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

// Barter end-of-round purchase: buy the Resource below the Trade Marker (1 Skaill, free once flipped).
class Op_barter extends Operation {
    public function resolve(): void {
        $owner = $this->getOwner();
        $value = $this->game->tokens->getTrackerValue($owner, "trade");
        $resource = $this->game->getRulesFor("slot_trade_$value", "craft", "nop");
        if ($value > 0 && $resource !== "nop") {
            // free once flipped, otherwise costs 1 Skaill Knife; optional either way
            $flipped = $this->game->getActionTileSide("action_special_9");
            $rules = $flipped ? "?($resource)" : "?(n_skaill:$resource)";
            $this->queue($rules, $owner, null, "action_special_9");
        }
    }
}
