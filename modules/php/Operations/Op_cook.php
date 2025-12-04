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
        return Operation::ARG_TOKEN;
    }
    function resolve() {
        $owner = $this->getOwner();

        $recipe_token = $this->getCheckedArg();

        $recipe_rule = $this->game->getRulesFor($recipe_token, "r");
        $weight = $this->game->getRulesFor($recipe_token, "craft");
        $prevWeight = $this->getWeight();
        $hearth_limit = $this->game->tokens->getTrackerValue($owner, "hearth");
        //throw new BgaSystemException("$recipe_token => $recipe_rule");
        $this->queue($recipe_rule);
        if ($prevWeight + $weight < $hearth_limit) {
            $this->queue("cook", $this->getOwner(), ["weight" => $prevWeight + $weight]);
        }
    }

    function getWeight() {
        return $this->getDataField("weight", 0);
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $hearth_limit = $this->game->tokens->getTrackerValue($owner, "hearth");
        $limit = $hearth_limit - $this->getWeight();
        $list = [];

        for ($i = 1; $i <= 8; $i++) {
            $recipe_token = "recipe_$i";
            $recipe_rule = $this->game->getRulesFor($recipe_token, "r");
            $weight = $this->game->getRulesFor($recipe_token, "craft");
            $list[$recipe_token] = ["q" => 0, "name" => $this->game->getRulesFor($recipe_token, "name"), "w" => $weight];

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

    function canSkip() {
        if ($this->noValidTargets() == false) {
            return true;
        }
        return false;
    }
}
