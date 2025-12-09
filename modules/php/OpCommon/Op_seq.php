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

/** Sequence of operations, no user choice */
class Op_seq extends ComplexOperation {
    function expandOperation($rank = 1) {
        if (count($this->delegates) == 0) {
            return true;
        }
        $count = $this->getCount();
        if ($this->isRangedChoice()) {
            return false;
        }
        if (!$this->isSubTrancient()) {
            return false;
        }

        $this->game->machine->interrupt($rank, count($this->delegates));
        foreach ($this->delegates as $sub) {
            $sub->destroy();
            $this->game->machine->put(
                $count . "(" . $sub->getTypeFullExpr() . ")",
                $sub->getOwner(),
                ["reason" => $this->getReason()],
                $rank
            );
            $rank++;
        }

        return true;
    }

    function getPossibleMoves() {
        if ($this->isRangedChoice()) {
            return parent::getRangeMoves();
        }
        foreach ($this->delegates as $sub) {
            if ($sub->isVoid()) {
                return ["err" => $sub->getError()];
            }
        }
        $sub = $this->delegates[0];
        return $sub->getPossibleMoves();
    }

    function getPrompt() {
        if ($this->isRangedChoice()) {
            $max = $this->getCount();
            if ($max > 1) {
                return clienttranslate('Select how many time to perform ${name}');
            }
            return clienttranslate('Perform ${name}');
        }
        return parent::getPrompt();
    }

    function getOpName() {
        return $this->getRecName(" ");
    }

    public function resolve() {
        if ($this->isRangedChoice()) {
            $c = $this->getCheckedArg();
            $this->game->machine->interrupt(1, 2);
            $choice = $this->getTypeFullExpr(false);
            $this->game->machine->put("$c($choice)", $this->getOwner(), ["reason" => $this->getReason()], 1);
            return;
        }
        return parent::resolve();
    }
}
