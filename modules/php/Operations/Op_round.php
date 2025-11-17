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
use Bga\Games\skarabrae\Game;

class Op_round extends Operation {
    function auto() {
        // start the round
        $roundNum = $this->game->globals->inc(Game::ROUNDS_NUMBER_GLOBAL, 1);
        $this->notifyMessage(clienttranslate('--- Round ${number} begins ---'), ["number" => $roundNum]);

        $this->game->globals->set(Game::TURNS_NUMBER_GLOBAL, 0);

        $this->queue("turn", null);
        $this->queue("turn", null);
        $this->queue("turn", null);
        $this->queue("endOfRound", null);
    }
}
