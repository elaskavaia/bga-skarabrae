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

use Bga\Games\skarabrae\OpCommon\Operation;

/** Activate action without worker */
class Op_activate extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN;
    }

    function getCard() {
        return $this->getParam(0, null) ?? $this->getDataField("card", "xxx");
    }

    function getPossibleMoves() {
        $card = $this->getCard();
        $this->game->systemAssert("Card must be pre-selected for this action", $card);
        return [$card];
    }

    function activateAction($action_tile) {
        $owner = $this->getOwner();
        $r = $this->game->getActionRules($action_tile);
        $this->queue($r, $owner, [], $action_tile);
    }

    function resolve() {
        $action_tile = $this->getCheckedArg();
        $this->activateAction($action_tile);
    }

    public function getPrompt() {
        return clienttranslate('Confirm activating card ${token_name}');
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + ["token_name" => $this->getCard()];
    }

    function requireConfirmation() {
        return true;
    }

    public function canSkip() {
        return true;
    }
}
