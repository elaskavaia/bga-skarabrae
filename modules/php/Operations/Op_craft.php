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
use Bga\Games\skarabrae\States\PlayerTurn;

class Op_craft extends Operation {
    function getArgType() {
        return Operation::ARG_TOKEN;
    }
    function auto(): bool {
        if ($this->isPaid()) {
            return true;
        }
        return false;
    }
    function getPossibleMoves() {
        $owner = $this->getOwner();
        return array_keys($this->game->tokensmop->getTokensOfTypeInLocation("action", "tableau_$owner"));
    }

    function isPaid() {
        return $this->getDataField("paid", false);
    }
    function resolve(mixed $data = []) {
        if ($this->isPaid()) {
            $action = $this->getDataField("card");
            $this->game->tokensmop->dbSetTokenState($action, 1, clienttranslate('${player_name} crafts ${token_name}'));
        } else {
            $action = $this->getCheckedArg($data);
            $cost = $this->game->getRulesFor($action, "craft", "n_bone");
            $this->queue($cost, $this->getOwner(), [], $this->getOpId());
            $this->queue($this->getType(), $this->getOwner(), ["paid" => true, "card" => $action]);
        }
        return;
    }
}
