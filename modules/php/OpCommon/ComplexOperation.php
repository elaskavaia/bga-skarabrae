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

abstract class ComplexOperation extends CountableOperation {
    /** @var Operation[] */
    protected array $delegates = [];

    function expandOperation($rank = 1) {
        $ranged = $this->isRanged();
        if ($ranged) {
            return false;
        }
        if (!$this->isSubTrancient()) {
            return false;
        }
        $this->game->machine->interrupt($rank);
        foreach ($this->delegates as $sub) {
            $sub->destroy();
            $this->game->machine->put($sub->getType(), $sub->getOwner(), $sub->getData(), $rank);
        }

        return true;
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
        if ($this->isRangedChoice()) {
            return parent::getPossibleMoves();
        }
        $res = [];
        foreach ($this->delegates as $sub) {
            $res[$sub->getId()] = [
                "name" => $sub->getButtonName(),
            ];
        }
        return $res;
    }

    function isSubTrancient() {
        foreach ($this->delegates as $sub) {
            if ($sub->isTrancient()) {
                return true;
            }
        }
        return false;
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

        return ["log" => $log, "args" => $args];
    }

    function getTypeFullExpr(bool $withCounts = true) {
        $op = $this->getOperator();

        $opcount = count($this->delegates);
        if ($opcount == 1) {
            $base = static::str($this->delegates[0]);
        } elseif ($opcount == 0) {
            $base = "0";
        } else {
            $res = static::str($this->delegates[0], $op);
            for ($i = 1; $i < $opcount; $i++) {
                $res .= $op . static::str($this->delegates[$i], $op);
            }
            $base = $res;
        }
        if ($withCounts && $this->isRanged()) {
            $min = $this->getMinCount();
            $max = $this->getCount();
            return "[$min,$max]($base)";
        }
        return $base;
    }
}
