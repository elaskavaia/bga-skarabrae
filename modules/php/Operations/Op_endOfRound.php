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

class Op_endOfRound extends Operation {
    function auto(): bool {
        $cards = $this->game->tokensmop->getTokensOfTypeInLocation(null, "cardset_%");
        $this->game->tokensmop->dbSetTokensLocation($cards, "discard_village");
        $this->queue("round");

        return true;
    }
}
