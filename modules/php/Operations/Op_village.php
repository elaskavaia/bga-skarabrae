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
        $n = $this->game->getTurnNumber();
        $cards = $this->game->tokens->getTokensOfTypeInLocation(null, "cardset_$n");
        return array_keys($cards);
    }

    public function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function getPrompt() {
        return clienttranslate('${You} must select a village card');
    }

    public function getDescription() {
        return clienttranslate('${actplayer} chooses one of the village cards');
    }

    public function getExtraArgs() {
        $n = $this->game->getTurnNumber();
        return parent::getExtraArgs() + ["nturn" => $n];
    }
    function resolve() {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();

        $this->game->effect_gainCard($owner, $card, $this->getOpId());
        if ($this->game->isSolo()) {
            $n = $this->game->getTurnNumber();
            $this->game->effect_cleanCards($n);
        }
        return;
    }
}
