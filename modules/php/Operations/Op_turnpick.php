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
use Bga\Games\skarabrae\OpCommon\OpMachine;

use function Bga\Games\skarabrae\getPart;

class Op_turnpick extends Operation {
    function auto(): bool {
        // schedle player in order of disk

        $token = null;
        $this->game->getMaxTurnMarkerPosition(0, $token);
        if (!$token) {
            // second pile
            $this->game->getMaxTurnMarkerPosition(1, $token);
        }
        if ($token) {
            $color = getPart($token, 1);
            if ($color === OpMachine::GAME_MULTI_COLOR) {
                $this->queue("village", $color);
                $this->queue("turnpick", OpMachine::GAME_MULTI_COLOR);
            } else {
                $this->queue("turn", $color);
            }
        }
        if ($this->game->isSimultanousPlay()) {
            $this->game->customUndoSavepoint($this->getPlayerId(), 1);
        } else {
            $this->game->customUndoSavepoint($this->getPlayerId(), 0);
        }
        return true;
    }
}
