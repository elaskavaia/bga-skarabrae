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

use Bga\Games\skarabrae\OpCommon\OpCard;
use Bga\Games\skarabrae\OpCommon\OpMachine;

class Op_village extends OpCard {
    public function getPossibleMoves() {
        $card = $this->getCard();
        if ($card) {
            return [$card];
        }
        $n = $this->game->getTurnNumber();
        $cards = $this->game->tokens->getTokensOfTypeInLocation(null, "cardset_$n");
        $owner = $this->getOwner();
        if ($owner === "000000") {
            return [array_keys($cards)[0]];
        }
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

    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $maxpass = $this->game->getMaxTurnMarkerPosition(2);

        if ($owner === OpMachine::GAME_MULTI_COLOR) {
            $this->game->tokens->dbSetTokenLocation($card, "discard_village", 0, clienttranslate('neutral player discards ${token_name}'));
        } else {
            $this->effect_gainCard($owner, $card, $this->getOpId());
            if ($this->game->isSolo()) {
                $n = $this->game->getTurnNumber();
                $this->game->effect_cleanCards($n);
            }
        }

        $this->game->setTurnMarkerPosition($owner, $maxpass + 1);
        if ($this->game->gamestate->isMultiactiveState() && $owner !== OpMachine::GAME_MULTI_COLOR) {
            $this->destroy(); // have to remove current op from stack before saving
            $this->game->customUndoSavepoint($this->getPlayerId(), 1);
        }
        if ($this->game->gamestate->isMultiactiveState()) {
            $this->queue("turnpick", OpMachine::GAME_MULTI_COLOR);
        }

        return;
    }
}
