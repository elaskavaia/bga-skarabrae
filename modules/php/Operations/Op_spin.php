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

// Spin wool
class Op_spin extends Operation {
    function resolve() {
        $color = $this->getOwner();
        $from = "deck_spin";
        $card = $this->game->tokens->tokens->getTokenOnTop($from);
        $this->game->systemAssert("no more cards", $card);
        $this->game->effect_gainCard($color, $card["key"], $this->getReason(), [
            "place_from" => $from,
        ]);
        return;
    }
}
