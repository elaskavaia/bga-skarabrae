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
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\OpCommon\OpMachine;

class Op_turnall extends Operation {
    function resolve(): void {
        $curturn = $this->game->tokens->dbResourceInc(Game::TURNS_NUMBER_GLOBAL, 1, "");

        $others = $this->game->tokens->getTokensOfTypeInLocation("turnmarker");
        foreach ($others as $key => $info) {
            $state = $info["state"];
            if ($state >= 20) {
                $this->game->tokens->dbSetTokenState($key, $state - 20, "");
            }
        }

        $this->notifyMessage(clienttranslate('-- Turn ${turn} --'), ["turn" => $curturn]);

        $this->queue("turnpick", OpMachine::GAME_MULTI_COLOR);
        //if (count($players_basic) > 1)
        $this->queue("barrier", OpMachine::GAME_BARIER_COLOR);
        if ($curturn > 1) {
            $n = $curturn - 1;
            $this->game->effect_cleanCards($n);

            if ($this->game->isSolo() && $curturn > 1) {
                // reveal
                $cards = $this->game->tokens->getTokensOfTypeInLocation(null, "cardset_$curturn");
                $this->game->tokens->dbSetTokensLocation($cards, "cardset_$curturn", 0, "");
            }
        }
    }
}
