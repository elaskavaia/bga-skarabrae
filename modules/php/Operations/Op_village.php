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

class Op_village extends Operation {
    public function getPossibleMoves() {
        $card = $this->getCard();
        if ($card) {
            return [$card];
        }
        $n = $this->game->getTurnNumber();
        $cards = $this->game->tokens->getTokensOfTypeInLocation(null, "cardset_$n");
        return array_keys($cards);
    }

    public function getCard() {
        $card = $this->getDataField("card", null);
        return $card;
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function getPrompt() {
        return clienttranslate('${You} must select a village card');
    }

    function getExtraArgs() {
        return [
            "round" => $this->game->getRoundNumber(),
            "turn" => $this->game->getTurnNumber(),
        ];
    }
    function resolve() {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();

        $this->game->effect_gainCard($owner, $card, $this->getOpId());

        $maxpass = $this->game->getMaxTurnMarkerPosition(2);
        $this->game->setTurnMarkerPosition($owner, $maxpass + 1);

        if ($this->game->isSolo() || $maxpass + 2 - 20 >= $this->game->getPlayersNumber()) {
            $n = $this->game->getTurnNumber();
            $this->game->effect_cleanCards($n);
        }
        return;
    }
}
