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
            // should never happen
            return StateConstants::STATE_PLAYER_TURN_CONF;
        }
        return $state;
    }
}
