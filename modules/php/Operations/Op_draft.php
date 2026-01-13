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
        return clienttranslate("Select an action to keep, rest will be discarded");
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
        return ["buttons" => false, "undo" => true];
    }

    public function auto(): bool {
        $this->game->switchActivePlayer($this->getPlayerId());
        return parent::auto();
    }
    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCheckedArg();
        $this->game->playerStats->set("game_special_action", (int) getPart($card, 2), $this->getPlayerId());
        $this->game->tokens->dbSetTokenLocation($card, "tableau_{$owner}", 0, "*", [], $this->getPlayerId());
    }

    public function undo() {
        $owner = $this->getOwner();
        $card = $this->game->tokens->db->getTokensOfTypeInLocationSingleKey("action_special", "tableau_{$owner}");
        $this->game->systemAssert("missing card", $card);
        $this->game->tokens->dbSetTokenLocation($card, "hand_{$owner}", 0, "*", ["_private" => true], $this->getPlayerId());
        $this->game->machine->push($this->getType(), $owner);
    }
}
