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
use Bga\Games\skarabrae\Db\DbMultiUndo;
use Bga\Games\skarabrae\Db\DbTokens;
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\OpCommon\OpMachine;
use Bga\Games\skarabrae\States\GameDispatch;

class Game extends Base {
    const TURNS_NUMBER_GLOBAL = "tracker_nturns";
    const ROUNDS_NUMBER_GLOBAL = "tracker_nrounds";
    public static Game $instance;
    public OpMachine $machine;
    public Material $material;
    public PGameTokens $tokens;
    public DbMultiUndo $dbMultiUndo;

    function __construct() {
        Game::$instance = $this;
        parent::__construct();
        self::initGameStateLabels([
            "variant_draft_num" => 100,
            "variant_solo_dif" => 101,
            "variant_multi" => 102,
        ]);

        $this->material = new Material();
        $this->machine = new OpMachine();
        $tokens = new DbTokens($this);
        $tokens->autoreshuffle = true;
        $decks = ["deck_village"];
        foreach ($decks as $deck) {
            $tokens->autoreshuffle_custom[$deck] = "discard_" . getPart($deck, 1);
        }
        $this->tokens = new PGameTokens($this, $tokens);
        $this->dbMultiUndo = new DbMultiUndo($this, "restorePlayerTables");

        $this->notify->addDecorator(function (string $message, array $args) {
            if (str_contains($message, '${reason}') && !isset($args["reason"])) {
                $args["reason"] = "";
            }
            return $args;
        });
    }

    /*
        setupGameTables:
        
        init all game tables (players and stats init in base class)
        called from setupNewGame
    */
    protected function setupGameTables() {
        $this->tokens->createTokens();
        $tokens = $this->tokens->db;
        // setup

        /* 
        1. Each player takes 1 Player Board with a Slider placed over the second column of the Storage Area (see below). 
        They also place 1 Furnish Marker into the left-most slot of the Furnish Track, and 1 Trade Marker into the left-most slot of the Trade Track. 
        */
        $players = $this->loadPlayersBasicInfos();
        $pnum = $this->getPlayersNumber();

        //2. Each player takes all 9 Standard Action Tiles in their chosen player colour (see the banner in the top-right corner of each Tile).
        //These should be placed in the correct order to the right of their Player Board so that the artwork lines up. Make sure that all Action Tiles are placed on their correct side. Each Action Tile should show 2 Resources on a tan banner along the bottom edge.

        //3. Each player places all 4 of their Workers in a nearby reserve (these are not in their supply yet).

        //4. Place all Resources, Roof Cards, and Extra Storage Tiles into a Main Supply.
        //5. Each player takes 2 Skaill Knives from the Main Supply, placing them to the left of the Slider on their Player Board (each in a separate space of the Storage Area).
        foreach ($players as $player_id => $player) {
            $color = $player["player_color"];
            $this->tokens->db->setTokenState("tracker_slider_$color", 1);
            $this->effect_incCount($color, "skaill", 2, "setup");
        }
        //6. Place the Turn Order Tile within reach of all players.
        //Randomly stack the Turn Markers of all player colours being used on the left space of the Turn Order Tile.

        $startingPlayer = $this->getActivePlayerId();

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
                $color = $player["player_color"];
                $tokens->pickTokensForLocation(4, "deck_task", "tableau_$color");
                $tokens->pickTokensForLocation(1, "deck_goal", "tableau_$color");
            }
        }
        /*
         * 9. Shuffle all Special Action Tiles and deal 2 to each player. Players must select 1 to keep, returning the other to the box.
         * 1-2 Players: If desired, 3 Special Action Tiles can be dealt to each player instead, with each player returning 2 of them.
         */
        $tokens->shuffle("deck_action");
        $dnum = $this->getVariantDraftNum();
        $n = 2;
        if ($dnum == 3 && $pnum <= 2) {
            $n = 3;
        } elseif ($dnum == 4) {
            //max possible
            $x = 8; //$this->tokens->db->countTokensInLocation("deck_action");
            $n = floor($x / $pnum);
        }

        $p = $this->getPlayerIdsInOrder($startingPlayer);
        $order = $pnum - 1;
        if ($pnum == 2) {
            $order++;
        }
        $maxdisks = $order + 1;
        foreach ($p as $player_id) {
            $color = $this->getPlayerColorById($player_id);
            $tokens->pickTokensForLocation($n, "deck_action", "hand_$color");
            $this->machine->queue("draft", $color);
            $this->tokens->dbSetTokenState(
                "turnmarker_$color",
                $order,
                clienttranslate('${player_name} initial turn order ${order}'),
                [
                    "order" => $maxdisks - $order,
                ],
                $player_id
            );
            if ($order == 2 && $pnum == 2) {
                $order--;
                $this->tokens->dbSetTokenLocation(
                    "turnmarker_000000",
                    "turndisk",
                    $order,

                    clienttranslate('neutral token initial turn order ${order}'),
                    [
                        "order" => $maxdisks - $order,
                    ]
                );
            }
            $order--;
        }

        $this->machine->queue("draftdiscard");
        $this->machine->queue("round", $this->getPlayerColorById($startingPlayer));
        return GameDispatch::class;
    }

    public function getDefaultStatValue(string $key, string $type): ?int {
        if (startsWith($key, "game_")) {
            if (!$this->isSolo()) {
                // don't init these if not solo
                if ($key === "game_vp_tasks") {
                    return null;
                }
                if ($key === "game_vp_goals") {
                    return null;
                }
            }
            return 0;
        } elseif ($key === "turns_number") {
            return 0;
        }
        return null;
    }

    function switchActivePlayer(int $playerId, bool $moreTime = true) {
        if ($playerId <= 2) {
            return;
        }

        if (!$this->gamestate->isPlayerActive($playerId)) {
            if ($this->gamestate->isMultiactiveState()) {
                $this->gamestate->setPlayersMultiactive([$playerId], "notpossible", false);
            } else {
                $this->gamestate->changeActivePlayer($playerId);
            }
            if ($moreTime) {
                $this->giveExtraTime($playerId);
            }
        }
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(): array {
        $result = [];
        $result = parent::getAllDatas();

        $result = array_merge($result, $this->tokens->getAllDatas());

        $isGameEnded = $this->isEndOfGame();
        $result["gameEnded"] = $isGameEnded;
        $result["endScores"] = $isGameEnded ? $this->getEndScores() : null;

        //tracker_hearth
        $players = $this->loadPlayersBasicInfosWithBots();

        foreach ($players as $player) {
            $color = $player["player_color"];
            $key = "tracker_hearth_$color";
            $result["tokens"][$key] = [
                "key" => $key,
                "state" => $this->getHearthLimit($color),
                "location" => "miniboard_{$color}",
            ];
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
        $round = $this->tokens->db->getTokenState(Game::ROUNDS_NUMBER_GLOBAL);
        $turn = $this->tokens->db->getTokenState(Game::TURNS_NUMBER_GLOBAL);
        if ($round == 0) {
            return 0;
        }
        if ($round >= 5) {
            return 100;
        }
        return ($round - 1) * 25 + ($turn - 1) * 8;
    }

    function isEndOfGame() {
        $num = $this->tokens->db->getTokenState(Game::ROUNDS_NUMBER_GLOBAL);
        return $num >= 5;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function effect_incCount(string $color, string $type, int $inc = 1, string $reason, array $options = []) {
        $message = array_get($options, "message", "*");
        unset($options["message"]);

        $token_id = $this->tokens->getTrackerId($color, $type);

        if ($this->getRulesFor($token_id, "s") == 1) {
            $x = $this->getTotalResCount($color) + $inc;
            $curLevel = $this->tokens->getTrackerValue($color, "slider");
            $cap = $curLevel * 3;
            if ($x > $cap) {
                $linc = ceil(($x - $cap) / 3);
                if ($curLevel + $linc > 10) {
                    $linc = 10 - $curLevel;
                }
                // does not go above 10
                $this->tokens->dbResourceInc(
                    "tracker_slider_$color",
                    $linc,
                    clienttranslate('${player_name} shifts slider ${inc} spaces to the right'),
                    $options,
                    $this->getPlayerIdByColor($color)
                );
            }
        }
        $this->tokens->dbResourceInc(
            $token_id,
            $inc,
            $message,
            ["reason" => $reason, "place_from" => $reason] + $options,
            $this->getPlayerIdByColor($color)
        );
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

        $this->playerStats->inc($stat, $inc, $player_id);

        $this->notifyWithName(
            "score",
            "",
            [
                "player_score" => $score,
                "inc" => $inc,
                "absImc" => abs((int) $inc),
                "duration" => 500,
                //"target" => $target,
            ],
            $player_id
        );
    }

    function effect_drawSimpleCard(string $color, string $type, int $inc = 1, string $reason = "", array $args = []) {
        $message = array_get($args, "message", clienttranslate('${player_name} gains ${token_name} ${reason}'));
        unset($args["message"]);
        $from = "deck_$type";
        $location = "tableau_{$color}";
        $tokens = $this->tokens->db->pickTokensForLocation($inc, $from, $location);

        $this->tokens->dbSetTokensLocation(
            $tokens,
            $location,
            0,
            $message,
            $args + ["reason" => $reason, "place_from" => $from],
            $this->getPlayerIdByColor($color)
        );

        return $tokens;
    }

    function effect_cleanCards(mixed $n) {
        $cards = $this->tokens->getTokensOfTypeInLocation(null, "cardset_$n");
        $this->tokens->dbSetTokensLocation($cards, "discard_village", 0, "");
    }

    function getRoundNumber() {
        $n = $this->tokens->db->getTokenState(Game::ROUNDS_NUMBER_GLOBAL);
        return $n;
    }

    function getTurnNumber() {
        $n = $this->tokens->db->getTokenState(Game::TURNS_NUMBER_GLOBAL);
        return $n;
    }

    function isSimultanousPlay() {
        if ($this->isSolo()) {
            return false;
        }
        return ((int) $this->getGameStateValue("variant_multi")) ? 1 : 0;
    }

    function getVariantDraftNum() {
        return (int) $this->getGameStateValue("variant_draft_num") ?: 2;
    }

    function getVariantSoloDif() {
        return (int) $this->getGameStateValue("variant_solo_dif");
    }

    function getTurnMarkerPosition(string $owner) {
        return $this->tokens->db->getTokenState("turnmarker_$owner", 0);
    }
    function setTurnMarkerPosition(string $owner, int $pos) {
        return $this->tokens->dbSetTokenState("turnmarker_$owner", $pos, "");
    }

    function getMaxTurnMarkerPosition(int $level = 1, ?string &$token = null) {
        $maxpass = $level * 10 - 1;
        $others = $this->tokens->getTokensOfTypeInLocation("turnmarker", "turndisk");
        foreach ($others as $key => $info) {
            $state = $info["state"];
            if ($state > $maxpass && $state < ($level + 1) * 10) {
                $maxpass = $state;
                $token = $key;
            }
        }
        return $maxpass;
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

    function getTrackerIdAndValue(?string $color, string $type, ?array &$arr = null) {
        return $this->tokens->getTrackerIdAndValue($color, $type, $arr);
    }

    function getTerrainNum(string $card) {
        $terr = (int) $this->getRulesFor($card, "t");
        return $terr;
    }

    function getActionTileSide(string $action_tile) {
        $state = $this->tokens->db->getTokenState($action_tile, 0);
        return $state;
    }

    function getActionRules($act) {
        $state = $this->getActionTileSide($act);

        if ($state) {
            $rules = $this->getRulesFor($act, "rb");
        } else {
            $rules = $this->getRulesFor($act, "r");
        }
        $this->systemAssert("no rules for $act $state", $rules);
        return $rules;
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
            $v = 0;
            foreach ($cards as $card => $info) {
                $r = $this->getRulesFor($card, "vp", 0);
                if ($r) {
                    $v += (int) $r;
                }
            }
            $this->effect_incVp($color, (int) $v, "game_vp_cards");
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
            if ($this->isSolo()) {
                // Tasks (negative vp)
                $cards = $this->tokens->getTokensOfTypeInLocation("card_task", "tableau_$color", 0);
                $this->effect_incVp($color, -2 * count($cards), "game_vp_tasks");
                // Goal (negative vp)
                $cards = $this->tokens->getTokensOfTypeInLocation("card_goal", "tableau_$color", 0);
                foreach ($cards as $card => $info) {
                    $r = $this->getRulesFor($card, "vp", 0);
                    if ($this->isGoalAchieved($card, $color)) {
                        $this->tokens->dbSetTokenState($card, 1, clienttranslate('${player_name} achieves goal ${token_name}'));
                    } else {
                        $this->notifyMessage(clienttranslate('${player_name} does not achieve goal ${token_name}'), [
                            "token_name" => $this->getTokenName($card),
                        ]);
                        $this->effect_incVp($color, -5, "game_vp_goals");
                    }
                }
            }
            $score = $this->playerScore->get($player_id);
            $this->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $score], $player_id);
            $this->playerStats->set("game_vp_total", $score, $player_id);
            if ($this->isSolo()) {
                $var = $this->getVariantSoloDif();
                if ($var <= 1) {
                    $goal = 45;
                } else {
                    $goal = 55;
                }
                if ($score < $goal) {
                    $this->notifyMessage(clienttranslate('${player_name} scores less than ${points}, score is negated'), [
                        "points" => $goal,
                    ]);
                    $score = $this->playerScore->set($player_id, -1);
                }
            } else {
                // tie breaker
                $this->playerScoreAux->set($player_id, $this->tokens->getTrackerValue($color, "turnmarker"));
            }
        }
        $this->notify->all("endScores", "", ["endScores" => $this->getEndScores(), "final" => true]);
    }

    function getEndScores(): array {
        // this would be filled dynamically on your game, but should have the shape of this static example
        $endScores = [];
        $players = $this->loadPlayersBasicInfos();
        $vp_stats = [
            "game_vp_setl_count",
            "game_vp_setl_sets",
            "game_vp_trade",
            "game_vp_action_tiles",
            "game_vp_cards",
            "game_vp_food",
            "game_vp_skaill",
            "game_vp_midden",
            "game_vp_slider",
        ];
        if ($this->isSolo()) {
            $vp_stats[] = "game_vp_tasks";
            $vp_stats[] = "game_vp_goals";
        }

        foreach ($players as $player_id => $player) {
            foreach ($vp_stats as $stat) {
                $endScores[$player_id][$stat] = $this->playerStats->get($stat, $player_id);
            }
            $endScores[$player_id]["total"] = $this->playerStats->get("game_vp_total", $player_id);
        }

        return $endScores;
    }

    function isGoalAchieved(string $card, string $color) {
        switch ($card) {
            case "card_goal_1":
                //Have 6 or more Settlers from 1 Environment.
                $count = 0;
                $keys = array_keys($this->tokens->getTokensOfTypeInLocation("card_setl", "tableau_$color"));
                $types = [0, 0, 0, 0, 0];
                foreach ($keys as $card) {
                    $num = $this->getTerrainNum($card);
                    $types[$num]++;
                }
                unset($types[0]);
                foreach ($types as $t => $v) {
                    if ($v >= 6) {
                        return true;
                    }
                }
                return false;
            case "card_goal_2": //Have 2 or more full sets of 4 Settlers from different Environments.
                $count = 0;
                $keys = array_keys($this->tokens->getTokensOfTypeInLocation("card_setl", "tableau_$color"));
                $types = [0, 0, 0, 0, 0];
                foreach ($keys as $card) {
                    $num = $this->getTerrainNum($card);
                    $types[$num]++;
                }
                unset($types[0]);
                $sets = min($types);
                return $sets >= 2;

            case "card_goal_3": //Have 7 or more Roof Cards.
                $cards = $this->tokens->getTokensOfTypeInLocation("card_roof%", "tableau_$color");
                return count($cards) >= 7;
            case "card_goal_4": //Have only 0-2 Midden in the Storage Area.
                $count = $this->tokens->getTrackerValue($color, "midden");
                return $count <= 2;
            case "card_goal_5": //Advance the Trade Marker 6 or more spaces.
                $count = $this->tokens->getTrackerValue($color, "trade") + 1;
                return $count >= 6;
            case "card_goal_6":
                //Advance the Furnish Marker 5 or more spaces.
                $count = $this->tokens->getTrackerValue($color, "furnish") + 1;
                return $count >= 5;
            case "card_goal_7":
                //Have 7 or more Food remaining (after feeding Settlers).
                $count = $this->tokens->getTrackerValue($color, "food");
                return $count >= 7;
            case "card_goal_8": // craft 8
                $count = 0;
                $keys = array_keys($this->tokens->getTokensOfTypeInLocation("action", "tableau_$color"));
                foreach ($keys as $act) {
                    $state = $this->getActionTileSide($act);
                    if ($state) {
                        $count++;
                    }
                }
                return $count >= 8;
        }
        return true;
    }

    function getTotalResCount(string $color) {
        $count = 0;
        foreach (Material::getAllNonPoopResources() as $res) {
            $count += $this->tokens->getTrackerValue($color, $res);
        }
        $count += $this->tokens->getTrackerValue($color, "midden");
        return $count;
    }
    public function customUndoSavepoint(int $player_id, int $barrier = 0, string $label = "undo"): void {
        $this->debugLog("customUndoSavepoint $player_id bar= $barrier");
        if ($this->gamestate->isMultiactiveState()) {
            $this->dbMultiUndo->doSaveUndoSnapshot(["barrier" => $barrier, "label" => $label], $player_id, true);
        } else {
            $this->dbMultiUndo->doSaveUndoSnapshot(["barrier" => $barrier, "label" => $label], $player_id, true);
            $this->undoSavepoint();
        }
    }

    function restorePlayerTables($table, $saved_data, $meta) {
        $player_id = (int) $meta["player_id"];
        $owner = $this->getPlayerColorById($player_id);
        if ($table == "token") {
            // filter the data
            $curtokens = $this->tokens->db->getTokensOfTypeInLocation(null, "%_{$owner}%");
            $saved_data = array_filter($saved_data, function ($row) use ($owner, $curtokens) {
                return str_contains($row["token_location"], $owner) ||
                    str_contains($row["token_key"], $owner) ||
                    array_key_exists($row["token_key"], $curtokens);
            });
            $keys = array_map(fn($row) => $row["token_key"], $saved_data);
            $this->notifyMessage(clienttranslate('${player_name} undoes their turn'), [], $player_id);
            $this->tokens->db->dbReplaceValues($saved_data);
            foreach ($keys as $token_id) {
                $info = $this->tokens->db->getTokenInfo($token_id);
                $this->tokens->dbSetTokenLocation($token_id, $info["location"], $info["state"], "", [], $player_id);
            }

            //return true;
        } elseif ($table == "machine") {
            $multi = $this->game->machine->getAllOperationsMulti();
            foreach ($multi as $dop) {
                if ($dop["owner"] == $owner) {
                    $this->game->machine->hide((int) $dop["id"]);
                }
            }
            $this->game->machine->db->normalize();
            $saved_data = array_filter($saved_data, function ($row) use ($owner) {
                return $row["owner"] == $owner && $row["rank"] >= 0;
            });
            uasort($saved_data, function ($a, $b) {
                return $a["rank"] <=> $b["rank"];
            });
            $rank = 1;
            foreach ($saved_data as $dop) {
                $dop["rank"] = $rank++;
            }
            $this->game->machine->db->interrupt(count($saved_data));
            $this->game->machine->db->insertList(null, $saved_data);
            //return true;
        }
        return false;
    }

    function multiPlayerUndo($owner) {
        if ($this->game->gamestate->isMultiactiveState()) {
            $this->dbMultiUndo->undoRestorePoint(0, true);
        } else {
            throw new BgaSystemException("Not implemented");
        }
    }

    function debug_op(string $type) {
        $color = $this->getCurrentPlayerColor();
        $this->machine->push($type, $color);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_specialCard(int $num) {
        $color = $this->getCurrentPlayerColor();
        $cards = $this->tokens->getTokensOfTypeInLocation("action_special", "tableau_{$color}");
        $this->tokens->dbSetTokensLocation($cards, "limbo", 0);
        $this->tokens->dbSetTokenLocation("action_special_$num", "tableau_{$color}", 0);
    }

    function debug_q() {
        $this->notifyWithName("test", "flip", []);
    }
    function debug_flip0() {
        $this->notifyWithName("test", "flip", ["state" => "0"]);
    }
    function debug_flip1() {
        $this->notifyWithName("test", "flip", ["state" => "1"]);
    }
    function debug_game_variant(string $type = "variant_multi", int $value = 1) {
        $this->setGameStateValue($type, $value);
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

    function debug_setupGameTables() {
        $this->DbQuery("DELETE FROM token");
        $this->DbQuery("DELETE FROM machine");
        $this->DbQuery("DELETE FROM multiundo");
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        $this->setupGameTables();
        //$newGameDatas = $this->getAllTableDatas(); // this is framework function
        //$this->notify->player($this->getActivePlayerId(), "resetInterfaceWithAllDatas", "", $newGameDatas); // this is notification to reset all data
        $this->notify->all("message", "setup is done", []); // NOI18N
        $this->notify->all("undoRestorePoint", "", []);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_dumpMachineDb() {
        $t = $this->machine->gettablearr();
        $this->debugLog("all stack " . ($t[0]["type"] ?? "halt"), $t);
        return $t;
    }
    function debugConsole($info, $args = []) {
        $this->notify->all("log", $info, $args);
        $this->warn($info);
    }
    function debugLog($info, $args = []) {
        $this->notify->all("log", "", ["log" => $info, "args" => $args]);
        //$this->warn($info . ": " . toJson($args));
    }
}
