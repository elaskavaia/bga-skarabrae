<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\States;

use Bga\GameFramework\StateType;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\States\GameState;

class MultiPlayerTurn extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct(
            $game,
            id: StateConstants::STATE_MULTI_PLAYER_TURN_OP,
            type: StateType::MULTIPLE_ACTIVE_PLAYER, // This state type means that one player is active and can do actions
            descriptionMyTurn: clienttranslate('${you} perform an action'), // We tell the ACTIVE player what they must do
            description: clienttranslate('${actplayer} performs an action'), // We tell OTHER players what they are waiting for
            transitions: ["next" => StateConstants::STATE_GAME_DISPATCH]
        );
    }

    public function getArgs(?int $active_player_id): array {
        // Send playable card ids of the active player privately

        $res = [
            "description" => $args["description"] ?? "",
            "_private" => [],
        ];
        $ids = $this->game->gamestate->getActivePlayerList();
        foreach ($ids as $player_id) {
            $args = $this->game->machine->getArgs((int) $player_id);
            $res["_private"][$player_id] = $args;
        }

        return $res;
    }

    public function onEnteringState(int $active_player_id) {
        return $this->game->machine->onEnteringPlayerState(0);
    }
    #[PossibleAction]
    function action_resolve(#[JsonParam] array $data) {
        return $this->game->machine->action_resolve((int) $this->game->getCurrentPlayerId(), $data);
    }
    #[PossibleAction]
    function action_skip() {
        return $this->game->machine->action_skip((int) $this->game->getCurrentPlayerId());
    }
    #[PossibleAction]
    function action_undo(int $move_id = 0) {
        return $this->game->machine->action_undo((int) $this->game->getCurrentPlayerId(), $move_id);
    }
    #[PossibleAction]
    function action_whatever() {
        return $this->game->machine->action_whatever((int) $this->game->getCurrentPlayerId());
    }
    public function zombie(int $playerId) {
        return $this->game->machine->action_whatever($playerId);
    }
}
