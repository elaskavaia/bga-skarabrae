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
        return Operation::ARG_TOKEN;
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
        $n = $this->getCount();
        $ress = Material::getAllNonPoopResources();
        $list = [];
        foreach ($ress as $res) {
            $resvalue = $this->game->tokens->getTrackerValue($owner, $res);
            if ($resvalue >= $n) {
                $list[] = "$res";
            }
        }

        return $list;
    }

    function resolve() {
        $res = $this->getCheckedArg();
        $n = $this->getCount();
        $this->queue("{$n}n_{$res}", $this->getOwner(), [], $this->getOpId());
    }

    public function getPrompt() {
        return clienttranslate('Pay ${count} of selected resource');
    }
}
