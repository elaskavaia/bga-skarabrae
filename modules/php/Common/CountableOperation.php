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

namespace Bga\Games\skarabrae\Common;

abstract class CountableOperation extends Operation {
    // function canResolveAutomatically(): bool {
    //     if (!parent::canResolveAutomatically()) {
    //         return false;
    //     }
    //     $count = $this->getCount();
    //     $mcount = $this->getMinCount();
    //     if ($count == $mcount) {
    //         return true;
    //     }
    //     return false;
    // }
    function getPossibleMoves() {
        $res = [];
        $count = $this->getCount();
        $mcount = $this->getMinCount();
        for ($i = $mcount; $i <= $count; $i++) {
            $res[] = "$i";
        }
        return $res;
    }

    public function getExtraArgs() {
        return ["count" => $this->getCount()];
    }

    function getCount() {
        return $this->getDataField("count", 1);
    }

    function getMinCount() {
        return $this->getDataField("mcount", 1);
    }

    function incMinCount($inc = 1) {
        $v = $this->getDataField("mcount", 1);
        $v += $inc;
        $this->withDataField("mcount", 1);
        return $v;
    }

    function incCount($inc = 1) {
        $v = $this->getDataField("count", 1);
        $v += $inc;
        $this->withDataField("count", 1);
        return $v;
    }

    function isRanged() {
        $count = $this->getCount();
        $mcount = $this->getMinCount();
        if ($count == 1 && $mcount == 1) {
            return false;
        }
        return true;
    }

    function canSkip() {
        return $this->isOptional();
    }

    function isOptional() {
        return $this->getMinCount() == 0;
    }
}
