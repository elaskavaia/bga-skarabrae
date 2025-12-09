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
/** User choses operation. If count is used it is shared and decreases for all choices */
class Op_or extends ComplexOperation {
    function withDelegate(Operation $sub) {
        if ($sub instanceof CountableOperation) {
            // shared counter
            $this->withDataField("count", $sub->getCount());
            $this->withDataField("mcount", $sub->getMinCount());
            $sub->withDataField("count", null);
            $sub->withDataField("mcount", null);
        }
        return parent::withDelegate($sub);
    }
    function expandOperation($rank = 1) {
        if ($this->getCount() == 0) {
            return true; // destroy
        }
        if (!$this->isSubTrancient()) {
            return false;
        }

        $this->game->machine->interrupt($rank);

        foreach ($this->delegates as $sub) {
            $this->game->machine->put(
                $sub->getTypeFullExpr(),
                $sub->getOwner(),
                [
                    "xop" => $this->getOperator(),
                    "count" => $this->getCount(),
                    "mcount" => $this->getMinCount(),
                    "reason" => $this->getReason(),
                ],
                $rank
            );
        }

        return true;
    }

    function resolve() {
        $target = $this->getCheckedArg();

        foreach ($this->delegates as $i => $sub) {
            if ("choice_$i" == $target) {
                $this->game->machine->push($sub->getTypeFullExpr(false), $sub->getOwner(), ["reason" => $this->getReason()]);
                //$this->notifyMessage(clienttranslate('${player_name} selected ${opname}'), ["opname" => $arg->getOpName()]);
                $this->incMinCount(-1);
                $this->incCount(-1);
            }
            $sub->destroy();
        }
        $this->game->machine->interrupt(2);
        $this->expandOperation(2);
        return;
    }

    function getPossibleMoves() {
        $res = [];
        foreach ($this->delegates as $i => $sub) {
            $err = "";
            if ($sub->isVoid()) {
                $err = $sub->getError();
            }
            $q = 0;
            if ($err) {
                $q = 1;
            }

            $res["choice_$i"] = [
                "name" => $sub->getButtonName(),
                "args" => $sub->getExtraArgs(),
                "err" => $err,
                "r" => $sub->getTypeFullExpr(),
                "q" => $q,
            ];
        }
        return $res;
    }

    function getPrompt() {
        if ($this->getCount() > 1) {
            return clienttranslate('Choose one of the options ${name} (${count} left)');
        }
        return clienttranslate('Choose one of the options ${name}');
    }
    function getDescription() {
        return clienttranslate('${actplayer} chooses one of the options');
    }
    function getOpName() {
        return $this->getRecName(" / ");
    }
}
