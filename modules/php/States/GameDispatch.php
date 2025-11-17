<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\States;

use Bga\GameFramework\StateType;
use Bga\Games\skarabrae\Game;
use Bga\GameFramework\States\GameState;
use Bga\Games\skarabrae\StateConstants;

class GameDispatch extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct($game, id: StateConstants::STATE_GAME_DISPATCH, type: StateType::GAME);
    }

    public function onEnteringState() {
        $game = $this->game;
        if ($game->isEndOfGame()) {
            return StateConstants::STATE_END_GAME;
        }
        $state = $game->machine->dispatchAll();
        if ($state === StateConstants::STATE_MACHINE_HALTED) {
            // schedule another turn
            // Standard case just active the next player and schedule there turn
            $player_id = $game->getPlayerAfter((int) $this->game->getActivePlayerId());
            $game->giveExtraTime($player_id);
            $game->gamestate->changeActivePlayer($player_id);
            $game->machine->push("turn", $game->getPlayerColorById($player_id));

            return StateConstants::STATE_GAME_DISPATCH;
        }
        return $state;
    }
}
