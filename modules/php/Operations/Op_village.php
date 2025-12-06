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

use function Bga\Games\skarabrae\getPart;

class Op_village extends Operation {
    public function getPossibleMoves() {
        if (!$this->game->globals) {
            $n = 1; // XXX
        } else {
            $n = $this->game->globals->get(Game::TURNS_NUMBER_GLOBAL);
        }
        $cards = $this->game->tokens->getTokensOfTypeInLocation(null, "cardset_$n");
        return array_keys($cards);
    }

    public function getArgType() {
        return "token";
    }
    function resolve() {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $type = getPart($card, 1);
        $this->game->effect_gainCard($owner, $card, $this->getOpId());
        switch ($type) {
            case "setl":
                $r = $this->game->getRulesFor($card, "r");
                $terr = $this->game->getTerrainNum($card);
                $ac = $terr + 5;
                $gain = $this->game->getRulesFor("action_main_$ac", "r");
                $this->queue("cotag($terr,$gain)", $owner, null, $card);
                $this->queue("?$r", $owner, null, $card);

                break;
            case "ball":
                $this->queue("ball");
                break;
            case "roof":
            case "util":
                break;
        }
        return;
    }
}
