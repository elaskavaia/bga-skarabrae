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
    function expandOperation() {
        if ($this->isRanged()) {
            return parent::expandOperation();
        }
        $stored = false;
        foreach ($this->delegates as $sub) {
            if ($sub->isTrancient()) {
                $stored = true;
                break;
            }
        }
        $this->game->machine->interrupt(0, count($this->delegates));
        $rank = 1;
        foreach ($this->delegates as $sub) {
            $sub->destroy();
            $this->game->machine->put($sub->getType(), $sub->getOwner(), $sub->getData(), $rank);
            $rank++;
        }
        //$this->game->debug_dumpMachineDb();
        return $stored;
    }
    function auto(): bool {
        if (count($this->delegates) == 0) {
            return true;
        }
        if (!$this->canResolveAutomatically()) {
            return false;
        }

        $this->game->machine->interrupt();
        $this->game->machine->renice($this->delegates[0], 1);
        return true;
    }

    function getPossibleMoves() {
        if (count($this->delegates) == 0) {
            return ["err" => "No moves"];
        }
        if ($this->isRanged()) {
            return ["confirm"];
        }
        foreach ($this->delegates as $i => $sub) {
            if ($sub->isVoid()) {
                return ["err" => $sub->getError()];
            }
        }
        $sub = $this->delegates[0];
        return $sub->getPossibleMoves();
    }

    function getPrompt() {
        if ($this->isRanged()) {
            $max = $this->getCount();
            if ($max > 1) {
                return clienttranslate('Perform ${op_name} up to ${count} times?');
            }
            return clienttranslate('Perform ${op_name}');
        }
        return parent::getPrompt();
    }

    function getExtraArgs() {
        return ["op_name" => $this->getRecName(" => ")];
    }

    public function resolve() {
        if ($this->isRanged()) {
            $this->incMinCount(1);
            $this->withDataField("orig", null);
            $this->expandOperation();
            return;
        }
        return parent::resolve();
    }
}
