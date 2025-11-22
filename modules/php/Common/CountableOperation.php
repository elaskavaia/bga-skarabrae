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

namespace Bga\Games\skarabrae\Common;

use Bga\Games\skarabrae\States\PlayerTurn;

abstract class CountableOperation extends Operation {
    function auto(): bool {
        $count = $this->getCount();
        $mcount = $this->getMinCount();
        if ($count == $mcount && !$this->requireConfirmation()) {
            $this->resolve();
            return true;
        }
        return false;
    }
    function getPossibleMoves() {
        $res = [];
        $count = $this->getCount();
        $mcount = $this->getMinCount();
        for ($i = $mcount; $i <= $count; $i++) {
            $res[] = "$i";
        }
        return $res;
    }

    public function getExtraArgs() {
        return ["count" => $this->getCount()];
    }

    function getCount() {
        return $this->getDataField("count", 1);
    }

    function getMinCount() {
        return $this->getDataField("mcount", 1);
    }

    function canSkip() {
        return $this->getMinCount() == 0;
    }
}
