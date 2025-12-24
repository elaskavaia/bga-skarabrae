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

class Op_turn extends Operation {
    function auto(): bool {
        $player_id = $this->getPlayerId();
        $this->game->switchActivatePlayer($player_id);
        $this->game->undoSavepoint();
        return parent::auto();
    }

    public function resolve() {
        $card = $this->getCheckedArg();

        if ($card == "yield") {
            $this->queue("pass");
            return;
        }
        $this->queue("village", $this->getOwner(), ["card" => $card]);
        $this->queue("act", $this->getOwner());
        $this->queue("recall", $this->getOwner());
        $curturn = $this->game->getTurnNumber();
        if ($curturn == 3) {
            $this->queue("night", $this->getOwner());
        }
    }
    public function getUiArgs() {
        return ["replicate" => true];
    }
    public function getDescription() {
        return clienttranslate('${actplayer} chooses one of the village cards');
    }
    public function getPrompt() {
        $args = $this->getArgs();
        if ($args["target"]["yield"] ?? false) {
            return clienttranslate('${You} must select a village card or Pass to take turn later');
        }
        return clienttranslate('${You} must select a village card');
    }
    public function getSubTitle() {
        $turn = $this->game->getTurnNumber();
        if ($turn == 3) {
            return clienttranslate("This is last turn before you have to feed the settlers");
        }
        return;
    }

    function getExtraArgs() {
        return [
            "round" => $this->game->getRoundNumber(),
            "turn" => $this->game->getTurnNumber(),
        ];
    }
    public function getPossibleMoves() {
        $op = $this->game->machine->instanciateOperation("village", $this->getOwner());
        $res = $op->getPossibleMoves();
        $tmarker = $this->game->getTurnMarkerPosition($this->getOwner());
        if ($tmarker < 10 && $tmarker != 0) {
            return $res + [
                "yield" => [
                    "name" => clienttranslate("Pass"),
                    "q" => 0,
                    "color" => "secondary",
                    "tooltip" => clienttranslate(
                        "Skip you turn for now to get player position advantage, you will get it after other players"
                    ),
                ],
            ];
        }
        return $res;
    }
}
