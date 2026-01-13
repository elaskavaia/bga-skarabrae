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
use Bga\Games\skarabrae\OpCommon\OpCard;

/**
 * Keep a village card, discard the rest
 */
class Op_keep extends OpCard {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getPrompt() {
        return clienttranslate("Select a card to keep, rest will be discarded");
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("card", "hand_$owner"));

        $res = [];

        foreach ($keys as $card) {
            $res[$card] = [
                "name" => $this->game->tokens->getTokenName($card),
                "q" => 0,
            ];
        }
        return $res;
    }
    public function canSkip() {
        if ($this->noValidTargets()) {
            return true;
        }
        return parent::canSkip();
    }

    public function requireConfirmation() {
        return true;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $state = $this->game->getActionTileSide($this->getReason() ?: "action_special_6");
        $this->effect_gainCard($owner, $card, $this->getReason(), ["flags" => $state ? 2 : 0]);
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card", "hand_$owner");
        $this->game->tokens->dbSetTokensLocation($cards, "discard_village", 0, clienttranslate('${player_name} discards ${token_name}'));
    }
}
