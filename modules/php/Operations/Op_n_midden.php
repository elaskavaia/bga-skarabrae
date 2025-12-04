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

class Op_n_midden extends CountableOperation {
    function getResType() {
        $type = $this->getType();
        return substr($type, 2); // n_XYZ -> XYZ
    }

    function getPossibleMoves() {
        return [$this->getResType()];
    }

    function resolve() {
        $owner = $this->getOwner();
        $count = $this->getCount();
        $current = $this->game->tokens->getTrackerValue($owner, $this->getResType());
        if ($current < $count) {
            $count = $current;
        }
        if ($count > 0) {
            $this->game->effect_incCount($this->getOwner(), $this->getResType(), -$count, $this->getReason(), [
                "message" => clienttranslate('${player_name} cleans ${token_div} x ${absInc}'),
            ]);
        } else {
            $this->notifyMessage(clienttranslate('${player_name} has no midden to clean'));
        }
        return;
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + ["token_div" => $this->game->tokens->getTrackerId($this->getOwner(), $this->getResType())];
    }

    public function getPrompt() {
        return clienttranslate('Clean ${count} ${token_div}');
    }

    public function requireConfirmation() {
        return false;
    }
}
