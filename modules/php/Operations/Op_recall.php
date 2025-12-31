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

class Op_recall extends Operation {
    function resolve(): void {
        $owner = $this->getOwner();
        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker%_$owner", null, 1);
        $this->game->tokens->dbSetTokensLocation($workers, "tableau_$owner", 1);
        if ($this->game->hasSpecial(3, $owner)) {
            // recruit
            $workers = $this->game->tokens->getTokensOfTypeInLocation("worker%_000000", null, 1);
            $this->game->tokens->dbSetTokensLocation($workers, "tableau_$owner", 1);
        }
    }
}
