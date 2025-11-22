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

use Bga\Games\skarabrae\Common\Operation;

/** Standard action */
class Op_act extends Operation {
    function getArgType() {
        return Operation::ARG_TOKEN;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        return array_keys($this->game->tokensmop->getTokensOfTypeInLocation("action", "tableau_$owner"));
    }
    function resolve(mixed $data = []) {
        $action_tile = $this->getCheckedArg($data);
        $r = $this->game->getRulesFor($action_tile);
        if ($r) {
            $this->queue($r);
        } else {
            $this->game->userAssert("not implemented yet $action_tile");
        }
    }

    public function getPrompt() {
        return clienttranslate("Select an action for worker");
    }
}
