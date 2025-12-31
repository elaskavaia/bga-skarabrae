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

// gain midden
class Op_clutter extends CountableOperation {
    function resolve(): void {
        $color = $this->getOwner();
        $v = $this->game->tokens->getTrackerValue($color, "slider");
        $m = $this->game->getRulesFor("slot_slider_$v", "r", 0);

        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_util", "tableau_{$color}");
        $count = count($cards);
        $res = max(0, (int) $m - $count);
        $this->game->effect_incCount($color, "midden", $res, $this->getOpName());
    }
}
