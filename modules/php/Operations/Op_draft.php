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

use function Bga\Games\skarabrae\getPart;

/**
 * Keep an action tile, discard the rest
 */
class Op_draft extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function getPrompt() {
        return "Select an action to keep, rest will be discarded";
    }

    public function getDescription() {
        return clienttranslate('${actplayer} selects an action to keep');
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $keys = array_keys($this->game->tokens->getTokensOfTypeInLocation("action", "hand_$owner"));

        $res = [];

        foreach ($keys as $card) {
            $res[$card] = [
                "name" => $this->game->tokens->getTokenName($card),
                "q" => 0,
            ];
        }
        return $res;
    }

    public function requireConfirmation() {
        return true;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function auto(): bool {
        $this->game->switchActivePlayer($this->getPlayerId());
        return parent::auto();
    }
    function resolve() {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $this->game->playerStats->set("game_special_action", (int) getPart($card, 2), $this->getPlayerId());
        $this->game->tokens->dbSetTokenLocation($card, "tableau_{$owner}", 0, "*", [], $this->getPlayerId());
        $cards = $this->game->tokens->getTokensOfTypeInLocation("action", "hand_$owner");
        $this->game->tokens->dbSetTokensLocation($cards, "limbo", 0, "");
    }
}
