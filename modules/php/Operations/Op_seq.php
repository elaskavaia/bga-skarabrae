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

use Bga\Games\skarabrae\Common\ComplexOperation;

class Op_seq extends ComplexOperation {
    function storeDelegates() {
        $stored = false;
        foreach ($this->delegates as $sub) {
            if ($sub->isTrancient()) {
                $stored = true;
                break;
            }
        }
        $this->game->machine->interrupt(0, count($this->delegates));
        $rank = 1;
        foreach ($this->delegates as $sub) {
            $sub->destroy();
            $this->game->machine->store($sub, $rank);
            $rank++;
        }

        return $stored;
    }
    function auto() {
        if ($this->storeDelegates()) {
            return;
        }

        $this->game->machine->interrupt();
        $this->game->machine->renice($this->delegates[0], 1);
    }
}
