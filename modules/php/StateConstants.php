<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae;

class StateConstants {
    // states for operaton stack games
    const STATE_PLAYER_TURN_OP = 12;
    const STATE_GAME_DISPATCH = 13;
    const STATE_MULTI_PLAYER_TURN_OP = 14;
    const STATE_PLAYER_TURN_CONF = 15;

    const STATE_MACHINE_HALTED = 42;

    // last state
    const STATE_END_GAME = 99;
}
