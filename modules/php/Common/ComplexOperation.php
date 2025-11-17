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

use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\States\PlayerTurn;
use Bga\Games\skarabrae\Common\OpExpression;

abstract class ComplexOperation extends Operation {
    /** @var Operation[] */
    protected array $delegates = [];

    public function withExpr(OpExpression $expr) {
        parent::withExpr($expr);
        $machine = Game::$instance->machine;
        $this->delegates = [];
        foreach ($expr->args as $arg) {
            $sub = $machine->instanciateOperation(OpExpression::str($arg), $this->getOwner(), $this->getData());
            $this->withSub($sub);
        }
        return $this;
    }

    function withSub(Operation $sub) {
        $this->delegates[] = $sub;
        $sub->withDataField("parent", $this->getType());
        return $this;
    }

    function storeDelegates() {
        $stored = false;
        foreach ($this->delegates as $sub) {
            if ($sub->isTrancient()) {
                $this->game->machine->store($sub, 1);
                $stored = true;
            }
        }

        return $stored;
    }

    function auto() {
        if ($this->storeDelegates()) {
            return;
        }

        return PlayerTurn::class;
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
