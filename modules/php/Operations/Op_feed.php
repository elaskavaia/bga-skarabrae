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

class Op_feed extends CountableOperation {
    function resolve(): void {
        $count = $this->getCheckedArg();
        if ($count > 0) {
            $this->game->effect_incCount($this->getOwner(), "food", -(int) $count, $this->getOpName());
        }
        $req = $this->getRequiredFood();
        $rem = $req - $count;
        if ($rem > 0) {
            $this->notifyMessage(clienttranslate('${player_name} does not pay enough food and will discard settlers instead'));
            $this->queue("{$rem}discard", $this->getOwner(), null, $this->getOpName());
        }
        return;
    }

    function skip() {
        if ($this->getCount() == 0) {
            $this->notifyMessage(clienttranslate('${player_name} does not need to spend food for feeding'));
        } else {
            $this->notifyMessage(clienttranslate('${player_name} skips feeding and will discard settlers instead'));
        }
    }

    function getPrompt() {
        return clienttranslate('Select how much ${token_div} to pay (max ${max})');
    }

    function getSubTitle() {
        return clienttranslate("Discard settler for each unpaid food");
    }

    function getRequiredFood() {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_roof%", "tableau_{$owner}");
        $countRoof = count($cards);
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_setl", "tableau_{$owner}");
        $countSetl = count($cards);
        return max(0, $countSetl - $countRoof);
    }

    function getCount() {
        $owner = $this->getOwner();
        $food = $this->game->tokens->getTrackerValue($owner, "food");
        return min($food, $this->getRequiredFood());
    }
    function getMinCount() {
        return 0;
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + [
            "token_div" => $this->game->tokens->getTrackerId($this->getOwner(), "food"),
            "max" => $this->getRequiredFood(),
        ];
    }

    function getButtonName() {
        if ($this->getCount() == 1) {
            return '${token_div}';
        }
        return clienttranslate('${count} ${token_div}');
    }
}
