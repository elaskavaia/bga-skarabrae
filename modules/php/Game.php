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
use Bga\Games\skarabrae\Db\DbTokens;
use Bga\Games\skarabrae\States\GameDispatch;

class Game extends Base {
    const TURNS_NUMBER_GLOBAL = "nturns";
    const ROUNDS_NUMBER_GLOBAL = "nrounds";
    public static Game $instance;
    public OpMachine $machine;
    public Material $material;
    public PGameTokens $tokensmop;
    public DbTokens $tokens;

    function __construct() {
        parent::__construct();
        Game::$instance = $this;

        $this->material = new Material();
        $this->machine = new OpMachine();
        $this->tokens = new DbTokens($this);
        $this->tokensmop = new PGameTokens($this, $this->tokens);

        $this->notify->addDecorator(function (string $message, array $args) {
            if (!isset($args["player_id"])) {
                $args["player_id"] = $this->getActivePlayerId();
            }

            return $args;
        });
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = []) {
        parent::setupNewGame($players, $options);
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
        $table_options = $this->getTableOptions();
        $result["table_options"] = [];
        foreach ($table_options as $option_id => $option) {
            $value = $this->tableOptions->get($option_id) ?? ($option["default"] ?? 0);
            $result["table_options"][$option_id] = $option;
            $result["table_options"][$option_id]["value"] = $value;
        }
        //$result["CON"] = $this->game->getPhpConstants("MA_");

        // Get information about players
        // Note: this is needed because basic does not have the score

        $players = $this->loadPlayersBasicInfosWithBots();

        foreach ($players as $player_id => $player) {
            foreach ($player as $pkey => $value) {
                $key = str_replace("player_", "", $pkey);
                $result["players"][$player_id][$key] = $value;
            }
        }
        $result = array_merge($result, $this->tokensmop->getAllDatas());
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
    /*
     * In this space, you can put any utility methods useful for your game logic
     */

    function effect_incCount(string $color, string $type, int $inc = 1, string $reason, array $options = []) {
        $message = array_get($options, "message", "*");
        unset($options["message"]);
        $token_id = $this->tokensmop->getTrackerId($color, $type);
        //$this->createCounterToken($token_id);
        $this->tokensmop->dbResourceInc($token_id, $inc, $message, ["reason" => $reason], $this->getPlayerIdByColor($color), $options);
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
}
