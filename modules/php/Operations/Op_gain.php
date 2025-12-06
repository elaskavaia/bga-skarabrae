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

use Bga\Games\skarabrae\OpCommon\CountableOperation;

class Op_gain extends CountableOperation {
    function resolve() {
        $count = $this->getCheckedArg();
        if ($count == "confirm") {
            $count = $this->getCount();
        }
        $this->game->effect_incCount($this->getOwner(), $this->getType(), (int) $count, $this->getReason());

        return;
    }

    function getPrompt() {
        return $this->getOpName();
    }
}
