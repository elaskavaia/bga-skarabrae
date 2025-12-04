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

abstract class ComplexOperation extends CountableOperation {
    /** @var Operation[] */
    protected array $delegates = [];

    function expandOperation() {
        $stored = false;

        $ranged = $this->isRanged();
        foreach ($this->delegates as $sub) {
            if ($sub->isTrancient()) {
                $stored = true;
                if ($ranged) {
                    // can only store itself
                    // $this->game->machine->put(
                    //     $sub->getDataField("orig", $this->getType()),
                    //     $sub->getOwner(),
                    //     ["xop" => $sub->getDataField("xop", ",")],
                    //     1
                    // );
                    // break;
                    return false;
                } else {
                    $this->game->machine->put($sub->getType(), $sub->getOwner(), $sub->getData(), 1);
                }
            }
        }

        return $stored;
    }

    function canSkip() {
        if (count($this->delegates) == 0) {
            return true;
        }
        return parent::canSkip();
    }

    function withDelegate(Operation $sub) {
        $this->delegates[] = $sub;
        return $this;
    }

    function getPossibleMoves() {
        $res = [];
        foreach ($this->delegates as $sub) {
            $res[$sub->getId()] = [
                "name" => $sub->getButtonName(),
            ];
        }
        return $res;
    }

    function getRecName($join) {
        $args = [];
        $pars = [];
        foreach ($this->delegates as $i => $sub) {
            $pars[] = "p$i";
            $args["p$i"] = ["log" => $sub->getButtonName(), "args" => $sub->getExtraArgs()];
        }
        $log = implode(
            $join,
            array_map(function ($a) {
                return '${' . $a . "}";
            }, $pars)
        );
        $args["i18n"] = $pars;
        return ["log" => $log, "args" => $args];
    }
}
