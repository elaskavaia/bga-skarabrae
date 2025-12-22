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
        $this->game->undoSavepoint();
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function getPrompt() {
        return clienttranslate('${You} must select a village card or Pass to take turn later');
    }
    public function getSubTitle() {
        return [
            "log" => clienttranslate('Round ${round} of 4 - Turn ${turn} of 3'),
            "args" => [
                "round" => $this->game->getRoundNumber(),
                "turn" => $this->game->getTurnNumber(),
            ],
        ];
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
        $passpos = $this->game->getMaxTurnMarkerPosition(1);
        if ($tmarker < 10 && $passpos + 2 < 10 + $this->game->getPlayersNumber()) {
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
        return parent::getPossibleMoves();
    }
}
