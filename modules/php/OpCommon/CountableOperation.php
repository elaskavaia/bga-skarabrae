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

namespace Bga\Games\skarabrae\OpCommon;

abstract class CountableOperation extends Operation {
    function getRangeMoves() {
        $res = [];
        $count = $this->getCount();
        $mcount = $this->getMinCount();
        if ($mcount == 0) {
            $mcount++;
        }
        if ($mcount == $count) {
            return [
                "$count" => [
                    "name" => "Confirm",
                    "q" => 0,
                ],
            ];
        }
        for ($i = $mcount; $i <= $count; $i++) {
            $res["$i"] = ["q" => 0];
        }
        return $res;
    }

    function getButtonName() {
        if ($this->getCount() == 1) {
            return '${name}';
        }
        return clienttranslate('${name} x ${count}');
    }
    function getPossibleMoves() {
        return $this->getRangeMoves();
    }

    public function getExtraArgs() {
        return ["count" => $this->getCount(), "name" => $this->getOpName()];
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
        if ($v < 0) {
            $v = 0;
        }
        $this->withDataField("mcount", $v);

        return $v;
    }

    function incCount($inc = 1) {
        $v = $this->getDataField("count", 1);
        $v += $inc;
        if ($v < 0) {
            $v = 0;
        }
        $this->withDataField("count", $v);
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

    function isRangedChoice() {
        return $this->getCount() != $this->getMinCount();
    }

    function canSkip() {
        return $this->isOptional();
    }

    function isOptional() {
        return $this->getMinCount() == 0;
    }

    function getTypeFullExpr(bool $withCounts = true) {
        $base = parent::getTypeFullExpr($withCounts);
        if ($withCounts && $this->isRanged()) {
            $min = $this->getMinCount();
            $max = $this->getCount();
            return "[$min,$max]($base)";
        }
        return $base;
    }
}
