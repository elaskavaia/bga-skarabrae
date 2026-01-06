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
use Bga\Games\skarabrae\Material;

class Op_cook extends Operation {
    function getArgType() {
        return Operation::TTYPE_TOKEN_COUNT;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $res = $this->getCheckedArg();
        $prevWeight = $this->getWeight();
        $hearth_limit = $this->game->getHearthLimit($owner);
        foreach ($res as $recipe_token => $c) {
            $recipe_rule = $this->game->getRulesFor($recipe_token, "r");
            $this->game->systemAssert("ERR:Op_cook:1", $recipe_rule);
            $weight = $this->game->getRulesFor($recipe_token, "craft");
            $this->queue("$c($recipe_rule)", $this->getOwner(), null, "action_main_2_$owner"); // cook action is the reason
            $prevWeight += $weight * $c;
            if ($prevWeight > $hearth_limit) {
                $this->game->userAssert("Cannot cook that much stuff, if not sure select one thing at a time");
            }
        }

        if ($prevWeight < $hearth_limit) {
            $this->queue("cook", $this->getOwner(), ["weight" => $prevWeight]);
        }
    }

    function getWeight() {
        return $this->getDataField("weight", 0);
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $hearth_limit = $this->game->getHearthLimit($owner);
        $limit = $hearth_limit - $this->getWeight();
        $list = [];

        $max = 7;
        if ($this->game->hasSpecial(7, $owner)) {
            // boar hunt
            $max = 8;
        }
        for ($i = 1; $i <= $max; $i++) {
            $recipe_token = "recipe_$i";
            $recipe_rule = $this->game->getRulesFor($recipe_token, "r");
            $weight = $this->game->getRulesFor($recipe_token, "craft");
            $item = substr(explode(":", $recipe_rule)[0], 2);
            $list[$recipe_token] = [
                "q" => 0,
                "name" => '${token_div}',
                "token_id" => "tracker_{$item}_{$owner}",
                "w" => $weight,
                "max" => min(floor($limit / $weight), $this->game->tokens->getTrackerValue($owner, $item)),
                "args" => [
                    "token_div" => "tracker_$item",
                    "tooltip" => $this->game->getTokenName($recipe_token),
                ],
            ];

            if ($weight > $limit) {
                $list[$recipe_token]["q"] = Material::MA_ERR_PREREQ;
                continue;
            }
            if ($this->game->machine->instanciateOperation($recipe_rule, $owner)->isVoid()) {
                $list[$recipe_token]["q"] = Material::MA_ERR_COST;
                continue;
            }
        }
        return $list;
    }

    public function getExtraArgs() {
        $owner = $this->getOwner();
        $hearth_limit = $this->game->getHearthLimit($owner);
        $limit = $hearth_limit - $this->getWeight();
        return parent::getExtraArgs() + ["count" => $limit, "hearth" => $hearth_limit, "token_div" => "tracker_hearth", "mcount" => 0];
    }

    public function getPrompt() {
        return clienttranslate('Select recipe to cook, you have ${count}/${hearth} ${token_div} left');
    }

    function canSkip() {
        if ($this->getWeight() == 0) {
            return false;
        }
        return true;
    }

    public function requireConfirmation() {
        return true;
    }
}
