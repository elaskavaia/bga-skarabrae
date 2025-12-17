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

use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\OpCommon\Operation;

class Op_turn extends Operation {
    function auto(): bool {
        $player_id = $this->getPlayerId();
        $this->game->giveExtraTime($player_id);
        $this->game->gamestate->changeActivePlayer($player_id);
        $this->queue("village", $this->getOwner());
        $this->queue("act", $this->getOwner());
        $this->queue("recall", $this->getOwner());
        $curturn = $this->game->globals->get(Game::TURNS_NUMBER_GLOBAL, 1);
        if ($curturn == 3) {
            $this->queue("night", $this->getOwner());
        }
        $this->game->undoSavepoint();
        return true;
    }
}
