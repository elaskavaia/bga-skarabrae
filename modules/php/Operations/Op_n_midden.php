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
        $canSlider = !$this->game->machine->instanciateOperation("n_slider", $owner)->isVoid();
        $midden = $this->game->tokens->getTrackerId($owner, "midden");
        $slider = $this->game->tokens->getTrackerId($owner, "slider");
        return [
            $midden => ["q" => $current > 0 ? Material::MA_OK : Material::MA_ERR_NOT_ENOUGH],
            $slider => ["q" => $canSlider ? Material::MA_OK : Material::MA_ERR_NOT_ENOUGH],
        ];
    }

    function canSkip() {
        return true;
    }

    function resolve() {
        $owner = $this->getOwner();
        $res = $this->getCheckedArg();
        $count = $this->getCount();
        if (str_starts_with($res, "tracker_slider")) {
            $this->queue("n_slider", $owner, null, $this->getReason());
            $count = 1;
        } else {
            $canSlider = !$this->game->machine->instanciateOperation("n_slider", $owner)->isVoid();
            if ($canSlider) {
                $count = 1;
            }
            $current = $this->game->tokens->getTrackerValue($owner, $this->getResType());
            if ($current < $count) {
                $count = $current;
            }
            if ($count > 0) {
                $this->game->effect_incCount($this->getOwner(), $this->getResType(), -1, $this->getReason(), [
                    "message" => clienttranslate('${player_name} cleans ${token_div} x ${absInc}'),
                ]);
            } else {
                $this->notifyMessage(clienttranslate('${player_name} has no midden to clean'));
            }
        }
        $rem = $this->getCount() - $count;
        if ($rem > 0) {
            $this->queue("{$rem}n_midden", $owner, null, $this->getReason());
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
