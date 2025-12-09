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

use Bga\Games\skarabrae\Material;
use Bga\Games\skarabrae\OpCommon\Operation;

use function Bga\Games\skarabrae\getPart;

class Op_clean extends Operation {
    private $r1 = "hide,barley,seaweed,wood,food";

    function resolve() {
        $res = $this->getCheckedArg();
        $args = $this->getArgs();
        $mcount = $args["mcount"];
        $maxcount = $args["count"];
        $userCount = count($res);
        $this->game->userAssert(
            clienttranslate("Cannot use this action because insuffient amount of elements selected"),
            $userCount >= $mcount
        );

        $this->game->userAssert(
            clienttranslate("Cannot use this action because superfluous amount of elements selected"),
            $userCount <= $maxcount
        );
        foreach ($res as $item) {
            $name = getPart($item, 1);
            $this->game->effect_incCount($this->getOwner(), $name, -1, $this->getReason());
        }

        $mc = [1, 3, 0];
        if ($maxcount == 4) {
            $mc = [2, 4, 6];
        }
        $midden = $mc[$userCount - 2];
        $this->queue("{$midden}n_midden");
        $this->queue("roof");
    }

    function isFlipped() {
        $owner = $this->getOwner();
        $state = $this->game->getActionTileSide("action_main_4_$owner"); // clean action card
        return $state;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $state = $this->isFlipped();
        $items = explode(",", $this->r1);
        if (!$state) {
            unset($items[4]); // remove food
        }
        $res = [];
        foreach ($items as $item) {
            $id = $this->game->tokens->getTrackerId($owner, $item);
            $v = $this->game->tokens->getTrackerValue($owner, $item);
            $res[$id] = [
                "q" => $v ? Material::MA_OK : Material::MA_ERR_NOT_ENOUGH,
                "max" => $v ? 1 : 0,
                "name" => '${token_div}',
                "args" => ["token_div" => $id],
            ];
        }
        return $res;
    }

    function getExtraArgs() {
        $state = $this->isFlipped();
        return [
            "count" => $state ? 4 : 3,
            "mcount" => 2,
        ];
    }

    function getArgType() {
        return Operation::TTYPE_TOKEN_ARRAY;
    }

    function getPrompt() {
        return clienttranslate('Select ${mcount} to ${count} unique resources to clean');
    }
}
