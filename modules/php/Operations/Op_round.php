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

class Op_round extends Operation {
    function resolve(): void {
        // start the round

        $roundNum = $this->game->tokens->dbResourceInc(Game::ROUNDS_NUMBER_GLOBAL, 1, "");
        $this->game->tokens->dbSetTokenState(Game::TURNS_NUMBER_GLOBAL, 0, "");

        if ($this->game->isEndOfGame()) {
            $this->notifyMessage(clienttranslate("--- End of game ---"));
            $this->game->finalScoring();
            return;
        }

        $this->notifyMessage(clienttranslate('--- Round ${number} begins ---'), ["number" => $roundNum]);

        $players_basic = $this->game->loadPlayersBasicInfos();
        foreach ($players_basic as $player_id => $player_info) {
            $color = $player_info["player_color"];
            $workers = $this->game->tokens->getTokensOfTypeInLocation("worker_$roundNum", "tableau_$color", 0);
            foreach ($workers as $worker => $info) {
                $this->game->tokens->dbSetTokenState($worker, 1, clienttranslate('${player_name} gains worker'), [], (int) $player_id);
            }
        }

        $this->notifyMessage(clienttranslate("game deals new village cards for the round"));
        $num = $this->game->getPlayersNumber();
        $cardsNum = $num == 4 ? 5 : 4;
        for ($i = 1; $i <= 3; $i++) {
            $cards = $this->game->tokens->db->pickTokensForLocation($num == 1 ? 5 - $i : $cardsNum, "deck_village", "cardset_$i");
            $state = 0;
            if ($this->game->isSolo() && $i > 1) {
                $state = 1;
            }
            $pos = 2; // will start at 2 because 1 is face down
            foreach ($cards as $card) {
                $this->game->tokens->dbSetTokenLocation($card, "cardset_$i", $state == 1 ? 1 : $pos, "", ["place_from" => "deck_village"]);
                $pos++;
            }
            $this->queue("turnall", OpMachine::GAME_BARIER_COLOR, ["num" => $i]);
        }

        $this->queue("endOfRound", OpMachine::GAME_BARIER_COLOR);
    }
}
