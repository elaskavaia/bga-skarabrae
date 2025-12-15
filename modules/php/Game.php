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

namespace Bga\Games\skarabrae;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\skarabrae\Common\PGameTokens;
use Bga\Games\skarabrae\Db\DbTokens;
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\States\GameDispatch;

class Game extends Base {
    const TURNS_NUMBER_GLOBAL = "nturns";
    const ROUNDS_NUMBER_GLOBAL = "nrounds";
    public static Game $instance;
    public OpMachine $machine;
    public Material $material;
    public PGameTokens $tokens;

    function __construct() {
        parent::__construct();
        Game::$instance = $this;

        $this->material = new Material();
        $this->machine = new OpMachine();
        $tokens = new DbTokens($this);
        $this->tokens = new PGameTokens($this, $tokens);

        $this->notify->addDecorator(function (string $message, array $args) {
            if (str_contains($message, '${reason}') && !isset($args["reason"])) {
                $args["reason"] = "";
            }
            return $args;
        });
    }

    /*
        initTables:
        
        init all game tables (players and stats init in base class)
        called from setupNewGame
    */
    protected function initTables() {
        $this->tokens->createTokens();
        $tokens = $this->tokens->tokens;
        // setup

        /* 
        1. Each player takes 1 Player Board with a Slider placed over the second column of the Storage Area (see below). 
        They also place 1 Furnish Marker into the left-most slot of the Furnish Track, and 1 Trade Marker into the left-most slot of the Trade Track. 
        */
        $players = $this->loadPlayersBasicInfos();

        //2. Each player takes all 9 Standard Action Tiles in their chosen player colour (see the banner in the top-right corner of each Tile).
        //These should be placed in the correct order to the right of their Player Board so that the artwork lines up. Make sure that all Action Tiles are placed on their correct side. Each Action Tile should show 2 Resources on a tan banner along the bottom edge.

        //3. Each player places all 4 of their Workers in a nearby reserve (these are not in their supply yet).

        //4. Place all Resources, Roof Cards, and Extra Storage Tiles into a Main Supply.
        //5. Each player takes 2 Skaill Knives from the Main Supply, placing them to the left of the Slider on their Player Board (each in a separate space of the Storage Area).
        foreach ($players as $player_id => $player) {
            $color = $this->getPlayerColorById((int) $player_id);
            $this->tokens->tokens->setTokenState("tracker_slider_$color", 1);
            $this->effect_incCount($color, "skaill", 2, "setup");
        }
        //6. Place the Turn Order Tile within reach of all players.
        //Randomly stack the Turn Markers of all player colours being used on the left space of the Turn Order Tile.
        //2-Players: Include 1 more Turn Marker of an unused colour.
        //Solo: Return the Turn Order Tile and all Turn Markers to the box.

        //7. Shuffle all Village Cards, placing them into a facedown Draw Pile.
        $tokens->shuffle("deck_village");
        /*
         * 8. Solo games only: Shuffle the Focus Cards, placing 1 faceup above the Player Board. Return the rest to box. Shuffle the Task Cards, placing 4 faceup above the Player Board. Return the rest to box.
         */
        if ($this->isSolo()) {
            $tokens->shuffle("deck_task");
            $tokens->shuffle("deck_goal");
            foreach ($players as $player_id => $player) {
                $color = $this->getPlayerColorById((int) $player_id);
                $tokens->pickTokensForLocation(4, "deck_task", "tableau_$color");
                $tokens->pickTokensForLocation(1, "deck_goal", "tableau_$color");
            }
        }
        /*
         * 9. Shuffle all Special Action Tiles and deal 2 to each player. Players must select 1 to keep, returning the other to the box.
         * 1-2 Players: If desired, 3 Special Action Tiles can be dealt to each player instead, with each player returning 2 of them.
         */
        $tokens->shuffle("deck_action"); // XXX
        foreach ($players as $player_id => $player) {
            $color = $this->getPlayerColorById((int) $player_id);
            $tokens->pickTokensForLocation(1, "deck_action", "tableau_$color");
        }

        $this->globals->set(Game::TURNS_NUMBER_GLOBAL, 0);
        $this->globals->set(Game::ROUNDS_NUMBER_GLOBAL, 0);
        $this->machine->push("round", $this->getPlayerColorById((int) $this->getActivePlayerId()));
        return GameDispatch::class;
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(): array {
        $result = parent::getAllDatas();

        $result = array_merge($result, $this->tokens->getAllDatas());
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression() {
        $round = (int) $this->globals->get(Game::ROUNDS_NUMBER_GLOBAL, 0);
        $turn = (int) $this->globals->get(Game::TURNS_NUMBER_GLOBAL, 0);
        if ($round == 0) {
            return 0;
        }
        if ($round == 5) {
            return 100;
        }
        return ($round - 1) * 25 + ($turn - 1) * 8;
    }

    function isEndOfGame() {
        $num = (int) $this->globals->get(Game::ROUNDS_NUMBER_GLOBAL, 0);
        return $num >= 5;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function effect_incCount(string $color, string $type, int $inc = 1, string $reason, array $options = []) {
        $message = array_get($options, "message", "*");
        unset($options["message"]);

        $token_id = $this->tokens->getTrackerId($color, $type);

        $this->tokens->dbResourceInc(
            $token_id,
            $inc,
            $message,
            ["reason" => $reason, "place_from" => $reason] + $options,
            $this->getPlayerIdByColor($color)
        );

        if ($this->getRulesFor($token_id, "s") == 1) {
            $x = $this->getTotalResCount($color);
            $cap = $this->tokens->getTrackerValue($color, "slider") * 3;
            if ($x > $cap) {
                $this->tokens->dbResourceInc(
                    "tracker_slider_$color",
                    ceil(($x - $cap) / 3),
                    clienttranslate('${player_name}\'s slider shifts ${inc} spaces to the right'),
                    $options,
                    $this->getPlayerIdByColor($color)
                );
            }
        }
    }

    function effect_incTrack(string $color, string $type, int $inc = 1, string $reason = "", array $args = []) {
        $message = array_get($args, "message", "*");
        unset($args["message"]);
        $token_id = $this->tokens->getTrackerId($color, $type);
        $value = $this->tokens->getTrackerValue($color, $type);
        $value = $value + $inc;
        $location = "slot_{$type}_{$value}_{$color}";
        $this->tokens->dbSetTokenLocation(
            $token_id,
            $location,
            $value,
            $message,
            $args + ["reason" => $reason],
            $this->getPlayerIdByColor($color)
        );
    }

    function effect_incVp(string $owner, int $inc, string $stat = "", array $options = []) {
        $player_id = $this->getPlayerIdByColor($owner);

        if ($inc < 0) {
            $message = clienttranslate('${player_name} loses ${absInc} VP ${reason}');
        } else {
            // if 0 print gain 0
            $message = clienttranslate('${player_name} gains ${absInc} VP ${reason}');
        }

        $score = $this->playerScore->inc(
            $player_id,
            $inc,
            new NotificationMessage($message, [
                "reason" => $stat,
            ])
        );
        // XXX: inc stat

        // $this->notifyWithName(
        //     "score",
        //     $message,
        //     [
        //         "player_score" => $score,
        //         "inc" => $inc,
        //         "mod" => abs((int) $inc),
        //         "duration" => 500,
        //         "target" => $target,
        //     ],
        //     $player_id
        // );
    }

    function effect_drawSimpleCard(string $color, string $type, int $inc = 1, string $reason = "", array $args = []) {
        $message = array_get($args, "message", clienttranslate('${player_name} gains ${token_name} ${reason}'));
        unset($args["message"]);
        $from = "deck_$type";
        $location = "tableau_{$color}";
        $tokens = $this->tokens->tokens->pickTokensForLocation($inc, $from, $location);

        $this->tokens->dbSetTokensLocation(
            $tokens,
            $location,
            0,
            $message,
            $args + ["reason" => $reason, "place_from" => $from],
            $this->getPlayerIdByColor($color)
        );
    }

    function effect_gainCard(string $color, string $card, string $reason = "", array $args = []) {
        $message = array_get($args, "message", clienttranslate('${player_name} gains ${token_name} ${reason}'));
        unset($args["message"]);

        $location = "tableau_{$color}";
        $this->tokens->dbSetTokenLocation($card, $location, 0, $message, $args + ["reason" => $reason], $this->getPlayerIdByColor($color));

        $type = getPart($card, 1);
        $owner = $color;
        $data = ["reason" => $card];
        switch ($type) {
            case "setl":
                $this->effect_settlerCard($owner, $card);
                break;
            case "ball":
                $r = $this->getRulesFor($card, "r");
                $this->machine->push("cotag(5,$r)", $owner, $data);
                break;
            case "roof":
                break;
            case "util":
                $r = $this->getRulesFor($card, "r");
                $this->machine->push("$r", $owner, $data);
                break;
        }
    }
    function effect_settlerCard(string $owner, string $card, bool $and = true) {
        $data = ["reason" => $card];
        $r = $this->getRulesFor($card, "r");
        $terr = $this->getTerrainNum($card);
        $ac = $terr + 5;
        $gain = $this->getRulesFor("action_main_$ac", "r"); // gathering
        if (!$and) {
            $this->machine->push("cotag($terr,$gain)/$r", $owner, $data);
        } else {
            $this->machine->push("?($r)", $owner, $data);
            $this->machine->push("cotag($terr,$gain)", $owner, $data);
        }
    }

    function getRulesFor($token_id, $field = "r", $default = "") {
        return $this->material->getRulesFor($token_id, $field, $default);
    }
    function getTokenName($token_id, $default = "") {
        if (!$default) {
            $default = "$token_id ?";
        }
        return $this->material->getRulesFor($token_id, "name", $default);
    }

    function getTerrainNum(string $card) {
        $num = getPart($card, 2);
        $tnum = floor(($num - 1) / 15) + 1;
        return $tnum;
    }

    function getActionTileSide(string $action_tile) {
        $state = $this->tokens->tokens->getTokenState($action_tile, 0);
        return $state;
    }

    function getHearthLimit(string $color) {
        $owner = $color;
        $hearth_limit = 4;
        $craftState = $this->getActionTileSide("action_main_2_$owner");
        if ($craftState) {
            $hearth_limit += 2;
        }
        $cards = $this->tokens->getTokensOfTypeInLocation("card_util", "tableau_{$color}");
        $count = count($cards);
        $hearth_limit += $count;

        if ($this->hasSpecial(5, $color)) {
            // muster
            $hearth_limit += 1;
        }
        return $hearth_limit;
    }

    function hasSpecial(int $num, string $color) {
        $cards = $this->tokens->getTokensOfTypeInLocation("action_special_$num", "tableau_{$color}");
        return count($cards) > 0;
    }

    function countTags(int $tagtype, string $owner) {
        if ($tagtype <= 4) {
            $ac = $tagtype + 5;
            // if gathering card is flipped it has another tag
            $count = $this->getActionTileSide("action_main_{$ac}_{$owner}");
            $cards = $this->tokens->getTokensOfTypeInLocation("card_setl", "tableau_{$owner}");
            foreach ($cards as $card => $info) {
                $num = $this->getTerrainNum($card);
                if ($num == $tagtype) {
                    $count++;
                }
            }
        } elseif ($tagtype == 5) {
            // stone balls
            $cards = $this->tokens->getTokensOfTypeInLocation("card_ball", "tableau_{$owner}");
            $count = count($cards);
        } elseif ($tagtype == 6) {
            // spindle wharl
            $cards = $this->tokens->getTokensOfTypeInLocation("card_spin", "tableau_{$owner}");
            $count = count($cards);
        } elseif ($tagtype == 7) {
            // roof
            $cards = $this->tokens->getTokensOfTypeInLocation("card_roof%", "tableau_{$owner}");
            $count = count($cards);
        }
        return $count;
    }

    function finalScoring() {
        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $color = $this->getPlayerColorById((int) $player_id);
            //         Furnish Track (VP per Settler and completed rows of Settlers).
            $furnish = $this->tokens->getTrackerValue($color, "furnish");
            $flevel1 = $this->getRulesFor("slot_furnish_{$furnish}", "r");
            $flevel2 = $this->getRulesFor("slot_furnish_{$furnish}", "rb");

            $cards = $this->tokens->getTokensOfTypeInLocation("card_setl", "tableau_{$color}");
            $this->effect_incVp($color, (int) ($flevel1 * count($cards)), "game_vp_setl_count");
            $types = [0, 0, 0, 0, 0];
            foreach ($cards as $card => $info) {
                $num = $this->getTerrainNum($card);
                $types[$num]++;
            }
            unset($types[0]);
            $sets = min($types);
            $this->effect_incVp($color, (int) ($flevel2 * $sets), "game_vp_setl_sets");
            // Trade Track (VP based on Trade Marker position).
            $trade = $this->tokens->getTrackerValue($color, "trade");
            $tlevel = $this->getRulesFor("slot_trade_{$trade}", "r");
            $this->effect_incVp($color, (int) $tlevel, "game_vp_trade");
            // Craft (VP for turned-over Action Tiles).
            $cards = $this->tokens->getTokensOfTypeInLocation("action", "tableau_{$color}");
            $ac = 0;
            foreach ($cards as $card => $info) {
                $state = $info["state"];
                if ($state) {
                    $ac += 2;
                }
            }
            $this->effect_incVp($color, (int) $ac, "game_vp_action_tiles");
            // Roof, Utensil, and Stone Ball Cards (VP shown on each card).
            $cards = $this->tokens->getTokensOfTypeInLocation("card", "tableau_{$color}");
            foreach ($cards as $card => $info) {
                $r = $this->getRulesFor($card, "vp", 0);
                if ($r) {
                    $this->effect_incVp($color, (int) $r, "game_vp_cards");
                }
            }
            // Food and Skaill Knives (1VP per item in Storage Area).
            $v = $this->tokens->getTrackerValue($color, "food");
            $this->effect_incVp($color, (int) $v, "game_vp_food");
            $v = $this->tokens->getTrackerValue($color, "skaill");
            $this->effect_incVp($color, (int) $v, "game_vp_skaill");
            // Midden (-1VP per Midden in Storage Area).
            $v = $this->tokens->getTrackerValue($color, "midden");
            $this->effect_incVp($color, (int) -$v, "game_vp_midden");

            // Slider (negative VP shown in the bottom hole).
            $v = $this->tokens->getTrackerValue($color, "slider");
            $vp = $this->getRulesFor("slot_slider_$v", "rb", 0);
            $this->effect_incVp($color, (int) $vp, "game_vp_slider");

            $score = $this->playerScore->get($player_id);
            $this->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $score]);
            if ($this->isSolo()) {
                $goal = 55;
                if ($score < $goal) {
                    $this->notifyMessage(clienttranslate('${player_name} scores less than ${points}, score is negated'), [
                        "points" => $goal,
                    ]);
                    $score = $this->playerScore->set($player_id, -1);
                }
            }
        }
    }

    function getTotalResCount(string $color) {
        $count = 0;
        foreach (Material::getAllNonPoopResources() as $res) {
            $count += $this->tokens->getTrackerValue($color, $res);
        }
        $count += $this->tokens->getTrackerValue($color, "midden");
        return $count;
    }

    function debug_op(string $type) {
        $color = $this->getCurrentPlayerColor();
        $this->machine->push($type, $color);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_q() {
        $rules = "(2n_bone/2n_food):boar";
        $color = $this->getCurrentPlayerColor();
        /** @var ComplexOperation */
        $op = $this->machine->instanciateOperation($rules, $color);
        $this->debugLog("$rules", $op->delegates[0]->getArgs());
    }
    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3) {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playAutomatically(int $moves = 1) {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int) $playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }
    public function debug_playAutomatically1() {
        return $this->debug_playAutomatically(1);
    }

    function debug_maxRes() {
        $color = $this->getCurrentPlayerColor();

        foreach (Material::getAllNonPoopResources() as $res) {
            $this->effect_incCount($color, $res, 2, "debug");
        }

        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_initTables() {
        $this->DbQuery("DELETE FROM token");
        $this->DbQuery("DELETE FROM machine");
        $this->DbQuery("DELETE FROM multiundo");
        $this->initTables();
        //$newGameDatas = $this->getAllTableDatas(); // this is framework function
        //$this->notify->player($this->getActivePlayerId(), "resetInterfaceWithAllDatas", "", $newGameDatas); // this is notification to reset all data
        $this->notify->all("message", "setup is done", []);
        $this->notify->all("undoRestorePoint", "", []);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_dumpMachineDb() {
        $t = $this->machine->gettablearr();
        $this->debugLog("all stack", ["t" => $t]);
        return $t;
    }
    function debugConsole($info, $args = []) {
        $this->notify->all("log", $info, $args);
        $this->warn($info);
    }
    function debugLog($info, $args = []) {
        $this->notify->all("log", "", $args + ["info" => $info]);
        $this->warn($info . ": " . toJson($args));
    }
}
