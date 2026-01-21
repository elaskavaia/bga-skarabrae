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

class Op_draw extends CountableOperation {
    public function getPrompt() {
        return clienttranslate('Confirm draw ${count} cards, this cannot be undone');
    }

    public function requireConfirmation() {
        return true;
    }

    function resolve(): void {
        $this->getCheckedArg();
        $owner = $this->getOwner();
        $this->game->systemAssert("Count?", $this->getCount() > 0);
        $cards = $this->game->tokens->db->pickTokensForLocation($this->getCount(), "deck_village", "hand_{$owner}");
        $this->game->tokens->dbSetTokensLocation(
            $cards,
            "hand_{$owner}",
            0,
            "",
            [
                "_private" => true,
                "place_from" => "deck_village",
            ],
            $this->getPlayerId()
        );
        $this->notifyMessage(clienttranslate('${player_name} draws ${count} card/s ${reason}'), [
            "count" => count($cards),
        ]);
        $this->game->customUndoSavepoint($this->player_id, 1);
    }
}
