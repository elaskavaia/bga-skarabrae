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
use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\Operation;

class Op_n_midden extends CountableOperation {
    function getResType() {
        return "midden";
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $current = $this->game->tokens->getTrackerValue($owner, $this->getResType());
        /**
         * @var Operation_n_slider
         */
        $sliderOp = $this->game->machine->instanciateOperation("n_slider", $owner);
        $canSlider = !$sliderOp->noValidTargets();
        $midden = $this->game->tokens->getTrackerId($owner, "midden");
        $slider = $this->game->tokens->getTrackerId($owner, "slider");
        $count = $this->getCount();
        return [
            $midden => [
                "q" => $current > 0 ? Material::MA_OK : Material::MA_ERR_NOT_ENOUGH,
                "name" => '${token_div}',
                "args" => ["token_div" => $midden],
                "max" => min($current, $count),
            ],
            $slider => [
                "q" => $canSlider ? Material::MA_OK : Material::MA_ERR_NOT_ENOUGH,
                "name" => '${token_div}',
                "args" => ["token_div" => $slider],
                "max" => min($sliderOp->getLimitCount(), $count),
            ],
        ];
    }

    function canSkip() {
        return true;
    }

    function getArgType() {
        $count = $this->getCount();
        if ($count > 1) {
            return Operation::TTYPE_TOKEN_COUNT;
        }
        return Operation::TTYPE_TOKEN;
    }

    function resolve() {
        $owner = $this->getOwner();
        $res = $this->getCheckedArg();
        if (!is_array($res)) {
            $res = [$res => 1];
        }
        $count = 0;
        foreach ($res as $elem => $c) {
            if ($c == 0) {
                continue;
            }
            $count += $c;
            if (str_starts_with($elem, "tracker_slider")) {
                $this->queue("{$c}n_slider", $owner, null, $this->getReason());
            } else {
                $this->game->effect_incCount($this->getOwner(), $this->getResType(), -$c, $this->getReason(), [
                    "message" => clienttranslate('${player_name} cleans ${token_div} x ${absInc}'),
                ]);
            }
        }

        $rem = $this->getCount() - $count;
        if ($rem > 0) {
            $this->queue("{$rem}n_midden", $owner, null, $this->getReason());
        } elseif ($rem < 0) {
            $this->game->userAssert(clienttranslate("Cannot use this action because superfluous amount of elements selected"));
        }
        return;
    }

    function getUiArgs() {
        return ["name" => '${token_div}'];
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + ["token_div" => $this->game->tokens->getTrackerId($this->getOwner(), $this->getResType())];
    }

    public function getPrompt() {
        return clienttranslate('Select to clean midden or shift the slider back (${count} left)');
    }

    public function requireConfirmation() {
        return true;
    }
}
