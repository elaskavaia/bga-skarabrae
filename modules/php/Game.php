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
            $this->effect_incCount($color, "skaill", 2, "setup");
            $this->effect_incCount($color, "hearth", 4, "setup");
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
        // TODO: compute and return the game progression

        return 0;
    }

    function isEndOfGame() {
        $num = (int) $this->globals->get(Game::ROUNDS_NUMBER_GLOBAL, 0);
        return $num >= 4;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function effect_incCount(string $color, string $type, int $inc = 1, string $reason, array $options = []) {
        $message = array_get($options, "message", "*");
        unset($options["message"]);

        $min = array_get($options, "min", 0);
        $check = array_get($options, "check", true);
        unset($options["min"]);
        unset($options["check"]);

        $token_id = $this->tokens->getTrackerId($color, $type);
        $current = $this->tokens->getTrackerValue($color, $type);
        $value = $current + $inc;
        if ($inc < 0) {
            if ($value < $min && $check) {
                $message = new NotificationMessage(clienttranslate('Not enough ${token_name} to pay: ${value} of ${absInc}'), [
                    "token_name" => $this->getTokenName($token_id),
                    "value" => $current,
                    "absInc" => -$inc,
                ]);
                $this->userAssert($message, false);
            }
        }
        if (array_get($options, "onlyCheck")) {
            return;
        }

        $this->tokens->dbResourceInc($token_id, $inc, $message, ["reason" => $reason] + $options, $this->getPlayerIdByColor($color));
    }

    function effect_incTrack(string $color, string $type, int $inc = 1, string $reason, array $args = []) {
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

    function getRulesFor($token_id, $field = "r", $default = "") {
        return $this->material->getRulesFor($token_id, $field, $default);
    }
    function getTokenName($token_id, $default = "") {
        if (!$default) {
            $default = "$token_id ?";
        }
        return $this->material->getRulesFor($token_id, "name", $default);
    }

    function debug_op(string $type) {
        $color = $this->getCurrentPlayerColor();
        $this->machine->push($type, $color);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
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
        $this->notifyAllPlayers("log", $info, $args);
        $this->warn($info);
    }
    function debugLog($info, $args = []) {
        $this->notifyAllPlayers("log", "", $args + ["info" => $info]);
        $this->warn($info);
    }
}
