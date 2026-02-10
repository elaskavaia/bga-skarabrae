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

/**
 * Discard the rest of action from hands (was kept there for undo)
 */
class Op_draftdiscard extends Operation {
    function resolve(): void {
        $cards = $this->game->tokens->getTokensOfTypeInLocation("action", "hand%");
        $this->game->tokens->dbSetTokensLocation($cards, "limbo", 0, "");
        $this->game->customUndoSavepoint(0, 2, $this->getOpId());
    }
}
