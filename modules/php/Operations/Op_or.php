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
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\Operation;

/** User choses operation. If count is used it is shared and decreases for all choices */
class Op_or extends ComplexOperation {
    function resolve() {
        $res = $this->getCheckedArg();
        if (!is_array($res)) {
            $res = [$res => 1];
        }
        $total = 0;
        $count = $this->getCount();
        $minCount = $this->getMinCount();
        $rank = 1;
        foreach ($this->delegates as $i => $sub) {
            $key = "choice_$i";
            $c = $res[$key] ?? 0;
            $total += $c;
            if ($c > 0) {
                $sub->withDataField("reason", $this->getReason());
                $sub->withDataField("count", $sub->getDataField("count", 1) * $c);
                $sub->withDataField("mcount", $sub->getDataField("mcount", 1) * $c);

                $sub->saveToDb($rank, true);
                $rank++;

                //$this->notifyMessage(clienttranslate('${player_name} selected ${opname}'), ["opname" => $arg->getOpName()]);
                $this->incMinCount(-$c);
                $this->incCount(-$c);
            }
            $sub->destroy();
        }

        if ($total > $count) {
            $this->game->userAssert(clienttranslate("Cannot use this action because superfluous amount of elements selected"));
        }

        if ($this->getCount() > 0) {
            $this->saveToDb($rank, true);
        }
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

    function getOperator() {
        return "/";
    }
}
