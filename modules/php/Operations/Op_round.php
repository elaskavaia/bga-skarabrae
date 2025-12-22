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
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\States\PlayerTurnConfirm;

class Op_round extends Operation {
    function resolve() {
        // start the round

        $roundNum = $this->game->tokens->dbResourceInc(Game::ROUNDS_NUMBER_GLOBAL, 1, "");

        if ($this->game->isEndOfGame()) {
            $this->notifyMessage(clienttranslate("--- End of game ---"));
            $this->game->finalScoring();
            return PlayerTurnConfirm::class;
            //return StateConstants::STATE_END_GAME;
        }

        $this->notifyMessage(clienttranslate('--- Round ${number} begins ---'), ["number" => $roundNum]);

        $this->game->tokens->dbSetTokenState(Game::TURNS_NUMBER_GLOBAL, 0);

        $players_basic = $this->game->loadPlayersBasicInfos();
        foreach ($players_basic as $player_info) {
            $color = $player_info["player_color"];
            $workers = $this->game->tokens->getTokensOfTypeInLocation("worker_$roundNum", "tableau_$color", 0);
            foreach ($workers as $worker => $info) {
                $this->game->tokens->dbSetTokenState($worker, 1, clienttranslate('${player_name} gains worker'));
            }
        }

        $this->notifyMessage(clienttranslate("games deals new village cards for the round"));
        $num = $this->game->getPlayersNumber();
        $cardsNum = $num == 4 ? 5 : 4;
        for ($i = 1; $i <= 3; $i++) {
            $cards = $this->game->tokens->tokens->pickTokensForLocation($num == 1 ? 5 - $i : $cardsNum, "deck_village", "cardset_$i");
            $this->game->tokens->dbSetTokensLocation($cards, "cardset_$i", 0, "", ["place_from" => "deck_village"]);
            $this->queue("turnall", null, ["num" => $i]);
        }

        $this->queue("endOfRound", null);
    }
}
