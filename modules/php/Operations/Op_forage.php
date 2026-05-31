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

// Forage: resolve a single Gather Action Tile up to N times (N = count)
class Op_forage extends CountableOperation {
    public function resolve(): void {
        $action_tile = $this->getCheckedArg();
        $owner = $this->getOwner();

        $r = $this->game->getActionRules($action_tile);
        $max = $this->getCount();
        $this->queue("[1,$max]($r)", $owner, [], $action_tile);
    }

    public function getPossibleMoves() {
        $owner = $this->getOwner();
        $res = [];
        for ($i = 6; $i <= 9; $i++) {
            $id = "action_main_{$i}_{$owner}";
            $res[$id] = [
                "name" => $this->game->tokens->getTokenName($id),
                "q" => 0,
            ];
        }
        return $res;
    }

    public function getUiArgs() {
        return ["replicate" => false];
    }

    public function getPrompt() {
        return clienttranslate('Forage a Gather Action Tile (count: ${count})');
    }
}
