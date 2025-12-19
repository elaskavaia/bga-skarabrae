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

use Bga\Games\skarabrae\Material;

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
        $res = $this->getCheckedArg();
        if ($this->getCount() == 1 || !is_array($res)) {
            $res = [$res => 1];
        }
        $total = 0;
        $count = $this->getCount();
        $minCount = $this->getMinCount();
        foreach ($this->delegates as $i => $sub) {
            $key = "choice_$i";
            $c = $res[$key] ?? 0;
            $total += $c;
            if ($c > 0) {
                $this->game->machine->push("$c(" . $sub->getTypeFullExpr(false) . ")", $sub->getOwner(), ["reason" => $this->getReason()]);
                //$this->notifyMessage(clienttranslate('${player_name} selected ${opname}'), ["opname" => $arg->getOpName()]);
                $this->incMinCount(-$c);
                $this->incCount(-$c);
            }
            $sub->destroy();
        }

        if ($total > $count) {
            $this->game->userAssert(clienttranslate("Cannot use this action because superfluous amount of elements selected"));
        }
        $this->game->machine->interrupt(2);
        $this->expandOperation(2);
        return;
    }

    function getPossibleMoves() {
        $res = [];
        $totalLimit = 0;
        foreach ($this->delegates as $i => $sub) {
            $err = "";
            if ($sub->isVoid()) {
                $err = $sub->getError();
            }
            $q = 0;
            $max = 0;
            if ($err) {
                $q = 1;
            } elseif ($sub instanceof CountableOperation) {
                $count = $this->getCount();
                $limit = $sub->getLimitCount();
                $max = min($count, $limit);
            } else {
                $max = 1000;
            }

            $totalLimit += $max;

            $res["choice_$i"] = [
                "name" => $sub->getButtonName(),
                "args" => $sub->getExtraArgs(),
                "err" => $err,
                "r" => $sub->getTypeFullExpr(),
                "q" => $q,
                "max" => $max,
            ];
        }
        if ($totalLimit < $this->getMinCount()) {
            return ["q" => Material::MA_ERR_COST];
        }
        return $res;
    }

    function getArgType() {
        if ($this->getCount() > 1) {
            return Operation::TTYPE_TOKEN_COUNT;
        }
        return parent::getArgType();
    }

    function getPrompt() {
        if ($this->getCount() > 1) {
            return clienttranslate('Choose one of the options ${name} (count: ${count})');
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
