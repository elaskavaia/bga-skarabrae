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

use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Material;

/** Calculate and push payment for trade */
class Op_payAny extends CountableOperation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    public function requireConfirmation() {
        return true;
    }

    function getPossibleMoves() {
        $list = $this->getCostArr();
        if (count($list) == 0) {
            return ["q" => Material::MA_ERR_COST];
        }
        return $list;
    }

    function getCostArr() {
        $owner = $this->getOwner();
        $this->game->systemAssert("Owner has to be set for payAny", $owner);
        $n = $this->getCount();
        $ress = Material::getAllNonPoopResources();
        $list = [];
        foreach ($ress as $res) {
            $resvalue = $this->game->tokens->getTrackerValue($owner, $res);
            //$list[$res] = ["q" => Material::MA_ERR_NOT_ENOUGH];
            if ($resvalue >= $n) {
                $list[$res]["q"] = 0;
                $list[$res]["max"] = $resvalue;
                $list[$res]["name"] = '${token_div}';
                $list[$res]["token_id"] = "tracker_$res";
                $list[$res]["args"] = ["token_div" => "tracker_$res"];
            }
        }

        return $list;
    }

    function resolve(): void {
        $res = $this->getCheckedArg();
        $n = $this->getCount();
        $this->queue("{$n}n_{$res}", $this->getOwner(), [], $this->getReason());
    }

    public function getPrompt() {
        return clienttranslate('Pay ${count} of selected resource');
    }
}
