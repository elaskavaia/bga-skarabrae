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

namespace Bga\Games\skarabrae\OpCommon;

use function Bga\Games\skarabrae\array_get;
use function Bga\Games\skarabrae\getPart;

abstract class OpCard extends Operation {
    function effect_gainCard(string $color, string $card, string $reason = "", array $args = []) {
        $message = array_get($args, "message", clienttranslate('${player_name} gains ${token_name} ${reason}'));
        unset($args["message"]);

        $location = "tableau_{$color}";

        $type = getPart($card, 1);
        $owner = $color;
        $data = ["reason" => $card];
        switch ($type) {
            case "setl":
                $t = $this->game->getTerrainNum($card);
                $c = count($this->game->tokens->getTokensOfTypeInLocation("card_setl_$t", $location));

                $this->game->tokens->dbSetTokenLocation(
                    $card,
                    $location,
                    $c + 1,
                    $message,
                    $args + ["reason" => $reason],
                    $this->game->getPlayerIdByColor($color)
                );
                $this->effect_settlerCard($owner, $card, $args["flags"] ?? 3);
                return;
            case "ball":
                $r = $this->game->getRulesFor($card, "r");
                $this->queue("cotag(5,$r)", $owner, $data);
                break;
            case "spin":
                $r = $this->game->getRulesFor($card, "r");
                $this->queue("cotag(6,$r)", $owner, $data);
                break;
            case "roof":
                break;
            case "util":
                $r = $this->game->getRulesFor($card, "r");
                $this->queue("$r", $owner, $data);
                break;
        }
        $this->game->tokens->dbSetTokenLocation($card, $location, 0, $message, $args + ["reason" => $reason], $this->getPlayerId());

        if ($type == "util") {
            $this->game->tokens->notifyCounterDirect("tracker_hearth_$owner", $this->game->getHearthLimit($owner), "");
        }
    }
    function effect_settlerCard(string $owner, string $card, int $flags = 3) {
        $data = ["reason" => $card];
        $r = $this->game->getRulesFor($card, "r");
        $terr = $this->game->getTerrainNum($card);
        $ac = $terr + 5;
        $gain = $this->game->getRulesFor("action_main_$ac", "r"); // gathering
        if ($flags == 1) {
            $this->queue("cotag($terr,$gain)/($r)", $owner, $data);
        } elseif ($flags == 3) {
            $this->queue("cotag($terr,$gain)", $owner, $data);
            $this->queue("?($r)", $owner, $data);
        } elseif ($flags == 2) {
            // bottom only
            $this->queue("?($r)", $owner, $data);
        }
    }
}
