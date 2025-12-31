<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\States;

use Bga\GameFramework\StateType;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\States\GameState;

class MultiPlayerTurnPrivate extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct(
            $game,
            id: StateConstants::STATE_MULTI_PLAYER_TURN_PRIVATE,
            type: StateType::PRIVATE,
            descriptionMyTurn: clienttranslate('${you} perform an action'), // We tell the ACTIVE player what they must do
            description: clienttranslate('${actplayer} performs an action'), // We tell OTHER players what they are waiting for
            transitions: ["loopback" => MultiPlayerMaster::class]
        );
    }

    public function getArgs(?int $player_id): array {
        $this->game->systemAssert("Player id is not set in MultiPlayerTurnPrivate", $player_id);
        $args = $this->game->machine->getArgs($player_id);
        return $args;
    }

    public function onEnteringState(int $player_id) {
        $this->game->systemAssert("Player id is not set in MultiPlayerTurnPrivate", $player_id);
        $state = $this->game->machine->onEnteringPlayerState($player_id);
        if ($state === null) {
            return null;
        }
        if ($state === GameDispatch::class) {
            return MultiPlayerMaster::class;
        }
        return $state;
    }
    #[PossibleAction]
    function action_resolve(#[JsonParam] array $data) {
        $this->game->machine->action_resolve((int) $this->game->getCurrentPlayerId(), $data);
        return MultiPlayerMaster::class;
    }
    #[PossibleAction]
    function action_skip() {
        $this->game->machine->action_skip((int) $this->game->getCurrentPlayerId());
        return MultiPlayerMaster::class;
    }
    #[PossibleAction]
    function action_undo(int $move_id = 0) {
        $this->game->machine->action_undo((int) $this->game->getCurrentPlayerId(), $move_id);
        return MultiPlayerMaster::class;
    }
    #[PossibleAction]
    function action_whatever() {
        $this->game->machine->action_whatever((int) $this->game->getCurrentPlayerId());
        return MultiPlayerMaster::class;
    }
    public function zombie(int $playerId) {
        $this->game->machine->action_whatever($playerId);
        return MultiPlayerMaster::class;
    }
}
