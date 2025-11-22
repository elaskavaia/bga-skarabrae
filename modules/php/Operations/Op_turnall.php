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

class Op_turnall extends Operation {
    function auto(): bool {
        $this->game->globals->inc(Game::TURNS_NUMBER_GLOBAL, 1);
        $players_basic = $this->game->loadPlayersBasicInfos();
        // schedle player in order of disk TODO
        foreach ($players_basic as $player_info) {
            $color = $player_info["player_color"];
            $this->queue("turn", $color);
        }
        return true;
    }
}
