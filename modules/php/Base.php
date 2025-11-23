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
use Bga\GameFramework\Table;
use BgaSystemException;
use BgaUserException;
use Exception;

class Base extends Table {
    const PLAYER_AUTOMA = 1;

    public OpMachine $machine;
    public Material $material;
    protected array $player_colors;

    function __construct() {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels([
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ]);
        $this->notify->addDecorator(function (string $message, array $args) {
            if (!isset($args["player_id"])) {
                $args["player_id"] = $this->getActivePlayerId();
            }
            if (isset($args["player_id"]) && !isset($args["player_name"]) && str_contains($message, '${player_name}')) {
                $args["player_name"] = $this->getPlayerNameById($args["player_id"]);
            }
            if (str_contains($message, '${you}')) {
                $args["you"] = "You"; // translated on client side, this is for replay after
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
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos["player_colors"];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] =
                "('" .
                $player_id .
                "','$color','" .
                $player["player_canal"] .
                "','" .
                addslashes($player["player_name"]) .
                "','" .
                addslashes($player["player_avatar"]) .
                "')";
        }
        $sql .= implode(",", $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        // Activate first player (which is in general a good idea :) )
        $this->gamestate->changeActivePlayer($this->getFirstPlayer());

        $this->initStats();
        // Setup the initial game situation here
        return $this->initTables();
        /**
         * ********** End of the game initialization ****
         */
    }
    public function initStats() {
        // INIT GAME STATISTIC
        $all_stats = $this->getStatTypes();
        $player_stats = $all_stats["player"];
        // auto-initialize all stats that starts with game_
        // we need a prefix because there is some other system stuff
        foreach ($player_stats as $key => $value) {
            if (startsWith($key, "game_")) {
                $this->initStat("player", $key, 0);
            }
            if ($key === "turns_number") {
                $this->initStat("player", $key, 0);
            }
        }
        $table_stats = $all_stats["table"];
        foreach ($table_stats as $key => $value) {
            if (startsWith($key, "game_")) {
                $this->initStat("table", $key, 0);
            }
            if ($key === "turns_number") {
                $this->initStat("table", $key, 0);
            }
        }
    }

    /**
     * override to setup all custom tables
     */
    protected function initTables() {}

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(): array {
        $result = ["players" => []];

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result["players"] = self::getCollectionFromDb($sql);

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
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $table_options = $this->getTableOptions();
        $result["table_options"] = [];
        foreach ($table_options as $option_id => $option) {
            $value = $this->tableOptions->get($option_id) ?? ($option["default"] ?? 0);
            $result["table_options"][$option_id] = $option;
            $result["table_options"][$option_id]["value"] = $value;
        }
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
        return false;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////
    /*
     * In this space, you can put any utility methods useful for your game logic
     */

    function isSolo() {
        return $this->getPlayersNumber() == 1;
    }
    function getMostlyActivePlayerId() {
        if ($this->isMultiActive()) {
            return $this->getCurrentPlayerId();
        } else {
            return $this->getActivePlayerId();
        }
    }

    function getActivePlayerColor() {
        return $this->getPlayerColorById((int) $this->getActivePlayerId());
    }
    public function isMultiActive() {
        return $this->gamestate->isMultiactiveState();
    }

    function isRealPlayer($player_id) {
        if ($player_id == 0 || $player_id == 1) {
            return false;
        }
        $players = $this->loadPlayersBasicInfos();
        return isset($players[$player_id]);
    }
    function isPlayerEliminated($player_id) {
        $players = $this->loadPlayersBasicInfos();
        if (isset($players[$player_id])) {
            return $players[$player_id]["player_eliminated"] == 1;
        }
        return false;
    }
    function isZombiePlayer($player_id) {
        $players = $this->loadPlayersBasicInfos();
        if (isset($players[$player_id])) {
            if ($players[$player_id]["player_zombie"] == 1) {
                return true;
            }
        }
        return false;
    }

    function isPlayerAlive($player_id) {
        return $this->isRealPlayer($player_id) && !$this->isPlayerEliminated($player_id) && !$this->isZombiePlayer($player_id);
    }
    /**
     *
     * @return integer first player in natural player order
     */
    function getFirstPlayer() {
        $table = $this->getNextPlayerTable();
        return $table[0];
    }
    function getNextReadyPlayer($player_id) {
        //$this->systemAssertTrue("invalid player id", $this->isRealPlayer($player_id));
        if ($this->isSolo()) {
            if ($this->isRealPlayer($player_id)) {
                return Game::PLAYER_AUTOMA;
            }
            if ($player_id == Game::PLAYER_AUTOMA) {
                return $this->getFirstPlayer();
            }
            return 0;
        }

        $num = $this->getPlayersNumber();
        while ($num-- >= 0) {
            $player_id = $this->getPlayerAfter($player_id);
            if ($this->isPlayerAlive($player_id)) {
                return $player_id;
            }
        }
        // run out of attempts
        return 0;
    }
    function loadPlayersBasicInfosWithBots($bots = true) {
        return parent::loadPlayersBasicInfos();
    }
    public function getPlayerColors() {
        $players_basic = $this->loadPlayersBasicInfosWithBots();
        $colors = [];
        foreach ($players_basic as $player_id => $player_info) {
            $colors[] = $player_info["player_color"];
        }
        return $colors;
    }
    /**
     *
     * @return integer player id based on hex $color, player is not in the list return 0
     */
    function getPlayerIdByColor(?string $color): int {
        if (!$color) {
            return (int) $this->getActivePlayerId();
        }

        $players = $this->loadPlayersBasicInfosWithBots();
        if (!isset($this->player_colors)) {
            $this->player_colors = [];
            foreach ($players as $player_id => $info) {
                $this->player_colors[$info["player_color"]] = $player_id;
            }
        }
        if (!isset($this->player_colors[$color])) {
            return 0;
        }
        return (int) $this->player_colors[$color];
    }

    /**
     * This will throw an exception if condition is false.
     * The message should be translated and shown to the user.
     *
     * @param $message string or NotificationMessage
     *            user side error message, translation is needed, use clienttranslate() when passing string to it (because it needs to be marked but this method will wrap it into _)
     * @param $cond boolean
     *            condition of assert

     * @throws BgaUserException
     */
    function userAssert(string|NotificationMessage $message, $cond = false) {
        if ($cond) {
            return;
        }

        if ($message instanceof NotificationMessage) {
            throw new BgaUserException($message->message, args: $message->args);
        }

        throw new BgaUserException($message);
    }

    /**
     * This will throw an exception if condition is false.
     * This only can happened if user hacks the game, client must prevent this
     *
     * @param string $log
     *            server side log message, no translation needed
     * @param bool $cond
     *            condition of assert
     * @throws BgaUserException
     */
    function systemAssert($log, $cond = false, ?string $logonly = null) {
        if ($cond) {
            return;
        }
        $this->dumpError($log);
        if ($logonly) {
            $this->error($logonly);
        }
        throw new BgaUserException("Internal Error. That should not have happened. Reload page and Retry" . " " . $log);
    }

    /**
     * This to make it public
     */
    public function _($text): string {
        return parent::_($text);
    }

    function dumpError($log) {
        $move = $this->getNextMoveId();
        $this->error("Internal Error during move $move: $log.");
        $e = new Exception($log);
        $this->error($e->getTraceAsString());
    }

    function getNextMoveId() {
        //getGameStateValue does not work when dealing with undo, have to read directly from db
        $next_move_index = 3;
        $subsql = "SELECT global_value FROM global WHERE global_id='$next_move_index' ";
        $move_id = $this->getUniqueValueFromDB($subsql);
        return (int) $move_id;
    }

    // ------ NOTIFICATIONS ----------
    /**
     * Advanced notification, which does more work on parameters
     * 1) If player id is not set it will try to determine it
     * 2) If player_id is set or passed via args it will also add player_name
     * 3) Auto add i18n tag to for all keys if they ends with _name or _tr
     * 4) Auto add preserve tag if keys end with _preserve
     * 5) If _previte is set true in args - send as private, otherwise sends to all players
     * 6) Can also pass _notifType via $args insterad of $type if needed
     * 7) Can add special animation params via args:
     * 'nod'=>true // no delay
     * 'noa'=>true // no animation
     * 'nop'=>true // ignore
     * If any of these parameters passed the type will change to be "${type}Async"
     * - which should be supported on clinet as asyncronious notification
     */
    function notifyWithName($type, $message = "", $args = null, $player_id = 0) {
        if ($args == null) {
            $args = [];
        }
        $this->systemAssert("Invalid notification signature", is_array($args));
        $this->systemAssert("Invalid notification signature", is_string($message));
        if (array_key_exists("player_id", $args) && !$player_id) {
            $player_id = $args["player_id"];
        }
        if (!$player_id) {
            $player_id = $this->getMostlyActivePlayerId();
        }
        $args["player_id"] = $player_id;
        if ($message) {
            // automaticaly add to i18n array all keys if they ends with _name or _tr, except reserved which are auto-translated on client side
            $i18n = array_get($args, "i18n", []);
            foreach ($args as $key => $value) {
                if (
                    is_string($value) &&
                    (endsWith($key, "_tr") ||
                        (endsWith($key, "_name") && $key != "player_name" && $key != "token_name" && $key != "place_name"))
                ) {
                    $i18n[] = $key;
                }
            }
            if (count($i18n) > 0) {
                $args["i18n"] = $i18n;
            }
        }
        if ($message) {
            $player_name = $this->getPlayerNameById($player_id);
            $args["player_name"] = $player_name;
        }
        if (isset($args["_notifType"])) {
            $type = $args["_notifType"];
            unset($args["_notifType"]);
        }
        $this->systemAssert("Invalid notification signature", is_string($type));
        if (array_key_exists("noa", $args) || array_key_exists("nop", $args) || array_key_exists("nod", $args)) {
            $type .= "Async";
        }
        // automaticaly add to preserve array all keys if they ends with _preserve
        $preserve = array_get($args, "preserve", []);
        foreach ($args as $key => $arg) {
            if (is_string($arg) && endsWith($key, "_preserve")) {
                $preserve[] = $key;
            }
            if ($key == "reason_tr") {
                $preserve[] = $key;
            }
        }
        if (count($preserve) > 0) {
            $args["preserve"] = $preserve;
        }
        $private = false;
        if (array_key_exists("_private", $args)) {
            $private = $args["_private"];
            unset($args["_private"]);
        }
        if ($private) {
            $this->notify->player($player_id, $type, $message, $args);
        } else {
            $this->notify->all($type, $message, $args);
        }
    }

    function notifyMessage($message, $args = [], $player_id = 0) {
        $this->notifyWithName("message", $message, $args, $player_id);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn($state, $active_player) {
        throw new \BgaUserException("Zombie mode not supported at this game");
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version) {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            $sql = "ALTER TABLE xxxxxxx ....";
        //            self::DbQuery( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            $sql = "CREATE TABLE xxxxxxx ....";
        //            self::DbQuery( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //
    }
}
// GLOBAL utility functions
function startsWith($haystack, $needle) {
    if ($haystack === null) {
        throw new Exception("ee");
    }
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle) {
    if ($haystack === null) {
        return false;
    }
    $length = strlen($needle);
    return $length === 0 || substr($haystack, -$length) === $needle;
}

function getPart($haystack, $i, $bNoexeption = false) {
    $parts = explode("_", $haystack);
    $len = count($parts);
    if ($bNoexeption && $i >= $len) {
        return "";
    }
    if ($i >= $len) {
        throw new BgaSystemException("Access to $i >= $len for $haystack");
    }
    return $parts[$i];
}

function toJson($data, $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) {
    $json_string = json_encode($data, $options);
    return $json_string;
}

/**
 * Right unsigned shift
 */
function uRShift($a, $b = 1) {
    if ($b == 0) {
        return $a;
    }
    return ($a >> $b) & ~((1 << 8 * PHP_INT_SIZE - 1) >> $b - 1);
}

if (!function_exists("array_key_first")) {
    function array_key_first(array $arr) {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return null;
    }
}
if (!function_exists("array_get")) {
    /**
     * Get an item from an array using "dot" notation.
     * If item does not exists return default
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_get($array, $key, $default = null) {
        if (is_null($key)) {
            return $array;
        }
        if (is_null($array)) {
            return $default;
        }
        if (!is_array($array)) {
            throw new BgaSystemException("array_get first arg is not array");
        }
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        foreach (explode(".", $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }
}
