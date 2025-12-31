<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\States;

use Bga\GameFramework\StateType;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\States\GameState;

class MultiPlayerMaster extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct(
            $game,
            id: StateConstants::STATE_MULTI_PLAYER_MASTER,
            type: StateType::MULTIPLE_ACTIVE_PLAYER, // This state type means that one player is active and can do actions
            descriptionMyTurn: "",
            description: "",
            initialPrivate: StateConstants::STATE_MULTI_PLAYER_TURN_PRIVATE
        );
    }

    public function getArgs(): array {
        // Send playable card ids of the active player privately
        // $this->game->systemAssert("getArgs MultiPlayerMaster");
        return [];
        // $res = [
        //     "description" => $args["description"] ?? "",
        //     "_private" => [],
        // ];
        // $ids = $this->game->gamestate->getActivePlayerList();
        // foreach ($ids as $player_id) {
        //     $args = $this->game->machine->getArgs((int) $player_id);
        //     $res["_private"][$player_id] = $args;
        // }

        // return $res;
    }

    public function onEnteringState() {
        return $this->game->machine->multiplayerDistpatch();
    }

    #[PossibleAction]
    function action_undo(int $move_id = 0) {
        return $this->game->machine->action_undo((int) $this->game->getCurrentPlayerId(), $move_id);
    }

    public function zombie(int $playerId) {
        return $this->game->machine->action_whatever($playerId);
    }
}
