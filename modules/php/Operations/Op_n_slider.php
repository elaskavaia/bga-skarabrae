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

use Bga\Games\skarabrae\OpCommon\CountableOperation;

class Op_n_slider extends CountableOperation {
    function getResType() {
        return "slider";
    }

    public function getLimitCount() {
        $owner = $this->getOwner();
        $x = $this->game->getTotalResCount($owner);
        $cap = $this->game->tokens->getTrackerValue($owner, "slider") * 3;
        if ($x >= $cap) {
            return 0;
        }
        $possible = floor(($cap - $x) / 3);
        return $possible;
    }

    function getPossibleMoves() {
        $count = $this->getCount();

        $possible = $this->getLimitCount();

        if ($count <= $possible) {
            return [$this->getResType()];
        } else {
            return ["err" => clienttranslate("Cannot shift slider")];
        }
    }

    function resolve() {
        $this->checkVoid();
        $count = $this->getCount();
        $this->game->effect_incCount($this->getOwner(), $this->getResType(), -$count, $this->getReason(), [
            "message" => clienttranslate('${player_name} shifts slider ${absInc} spaces to the left'),
        ]);
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + ["token_div" => $this->game->tokens->getTrackerId($this->getOwner(), $this->getResType())];
    }
}
