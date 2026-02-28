<?php

declare(strict_types=1);

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\Notify;
use Bga\Games\skarabrae\OpCommon\OpExpression;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Common\PGameTokens;
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\Operations\Op_or;
use Bga\Games\skarabrae\Operations\Op_paygain;
use Bga\Games\skarabrae\Operations\Op_seq;
use Bga\Games\skarabrae\Operations\Op_cotag;
use Bga\Games\skarabrae\Operations\Op_craft;
use Bga\Games\skarabrae\Operations\Op_pay;
use Bga\Games\skarabrae\OpCommon\OpMachine;
use Bga\Games\skarabrae\Operations\Op_barrier;
use Bga\Games\skarabrae\Operations\Op_furnish;
use Bga\Games\skarabrae\Operations\Op_furnishPay;
use Bga\Games\skarabrae\Operations\Op_task;
use Bga\Games\skarabrae\Operations\Op_turn;
use Bga\Games\skarabrae\Operations\Op_turnall;
use Bga\Games\skarabrae\Operations\Op_turnpick;
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\GameDispatchForced;
use Bga\Games\skarabrae\States\MachineHalted;
use Bga\Games\skarabrae\States\MultiPlayerMaster;
use Bga\Games\skarabrae\States\PlayerTurn;
use Bga\Games\skarabrae\Tests\MachineInMem;
use Bga\Games\skarabrae\Tests\TokensInMem;
use PHPUnit\Framework\TestCase;

use function Bga\Games\skarabrae\array_get;
use function Bga\Games\skarabrae\startsWith;

//    'player_colors' => ["ef58a2", "a0d28c", "6cd0f6", "ffcc02"],
define("PCOLOR", "a0d28c");
define("BCOLOR", "6cd0f6");
define("ACOLOR", "ffcc02");

class FakeNotify extends Notify {
    public function all(string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify all: $notifName : $message\n";
    }
    public function player(int $playerId, string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify player $playerId: $notifName : $message\n";
    }
}

class GameUT extends Game {
    var $multimachine;
    var $xtable;
    var $gameap_number = 0;
    var $var_colonies = 0;
    var $_colors = [];

    function __construct() {
        parent::__construct();
        //$this->gamestate = new GameStateInMem();

        //$this->tokens = new TokensInMem($this);
        $this->xtable = [];
        $this->machine = new OpMachine(new MachineInMem($this, $this->xtable));
        $this->curid = 1;
        $this->_colors = [PCOLOR, BCOLOR];
        $this->notify = new FakeNotify();

        $tokens = new TokensInMem($this);
        $this->tokens = new PGameTokens($this, $tokens);
    }

    function getPlayersNumber(): int {
        return count($this->_colors);
    }

    function setPlayersNumber(int $num) {
        switch ($num) {
            case 2:
                $this->_colors = [PCOLOR, BCOLOR];
                break;
            case 3:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR];
                break;
            case 4:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR, "ef58a2"];
                break;
            default:
                throw new BgaVisibleSystemException("Invalid number of players");
        }
    }

    function getUserPreference(int $player_id, int $code): int {
        return 0;
    }
    function getAutomaColor() {
        return ACOLOR;
    }

    function init(int $x = 0) {
        //$this->adjustedMaterial(true);
        //$this->createTokens();
        $this->gamestate->changeActivePlayer(10);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        return $this;
    }

    function clean_cache() {}

    function getMultiMachine() {
        return $this->multimachine;
    }

    public int $curid;

    public function getCurrentPlayerId($bReturnNullIfNotLogged = false): string|int {
        return $this->curid;
    }

    public function getCurrentPlayerColor(): string {
        return $this->getPlayerColorById($this->curid);
    }

    function _getColors() {
        return $this->_colors;
    }

    function fakeUserAction(Operation $op, $target = null) {
        return $op->action_resolve([Operation::ARG_TARGET => $target]);
    }

    function getPlayerColorById($player_id): string {
        $idx = $player_id - 10;
        if ($idx >= 0 && $idx < count($this->_colors)) {
            return $this->_colors[$idx];
        }
        return "000000";
    }

    // override/stub methods here that access db and stuff
}

final class GameTest extends TestCase {
    private GameUT $game;
    function dispatchOneStep($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchOne();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function dispatch($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchAll();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function game(int $x = 0) {
        $game = new GameUT();
        $game->init($x);
        $this->game = $game;
        return $game;
    }

    protected function setUp(): void {
        $this->game();
    }
    public function testInstanciateAllOperations() {
        $this->game();
        $token_types = $this->game->material->get();
        $tested = [];
        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "Op_")) {
                continue;
            }
            echo "testing op $key\n";
            $this->subTestOp($key, $info);
            $tested[$key] = 1;
        }

        $dir = dirname(dirname(__FILE__));
        $files = glob("$dir/Operations/*.php");

        foreach ($files as $file) {
            $base = basename($file);
            $this->assertTrue(!!$base);
            if (!startsWith($base, "Op_")) {
                continue;
            }
            $mne = preg_replace("/Op_(.*).php/", "\\1", $base);
            $key = "Op_{$mne}";
            if (array_key_exists($key, $tested)) {
                continue;
            }
            echo "testing op $key\n";
            $this->subTestOp($key, ["type" => $mne]);
        }
    }

    function subTestOp($key, $info = []) {
        $type = array_get($info, "type", substr($key, 3));
        $this->assertTrue(!!$type);

        /** @var Operation */
        $op = $this->game->machine->instanciateOperation($type, PCOLOR);

        $args = $op->getArgs();
        $ttype = array_get($args, "ttype");
        $this->assertTrue($ttype != "", "empty ttype for $key");

        $propt = array_get($args, "prompt");
        if (isset($info["prompt"])) {
            $this->assertEquals($info["prompt"], $propt, $type);
        }

        $this->assertFalse(str_contains($op->getOpName(), "?"), $op->getOpName());
        $this->assertFalse($op->getOpName() == $op->getType(), $op->getType());
        return $op;
    }

    public function testAllActions() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "action_")) {
                continue;
            }
            echo "testing action $key\n";
            $r = $info["r"] ?? "";
            $this->assertTrue($r != "", "empty r for $key");
            $this->game->machine->instanciateOperation($r, PCOLOR);
            $r = $info["rb"] ?? "";
            $this->assertTrue($r != "", "empty rb for $key");
            $this->game->machine->instanciateOperation($r, PCOLOR);
        }
    }
    public function testBind() {
        $game = $this->game;
        $color = PCOLOR;
        $game->machine->push("fish/wood", $color);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);

        $this->assertEquals("fish/wood", $op->getTypeFullExpr());

        $game->machine->dispatchOne();

        $op = $game->machine->createTopOperationFromDbForOwner($color);
        $this->assertEquals("or", $op->getType());
        $this->assertEquals("fish/wood", $op->getTypeFullExpr());
    }

    public function testFish() {
        $game = $this->game;
        $color = PCOLOR;
        $game->machine->push("[0,3]fish", $color);
        $op = $this->dispatchOneStep(PlayerTurn::class);
        // simulate user action
        $state = $game->fakeUserAction($op, 2);
        $this->assertEquals(GameDispatch::class, $state);
        $tops = $this->dispatchOneStep(42);
    }

    public function testGold2() {
        $color = PCOLOR;
        $this->game->machine->push("2fish", $color);
        $this->dispatchOneStep(GameDispatch::class);
        $this->dispatchOneStep(42);
    }

    public function testFishSoup() {
        $op = $this->game->machine->instanciateOperation("n_fish:food", PCOLOR);
        $this->assertTrue($op->isVoid());
    }

    public function testOr() {
        $op = $this->game->machine->instanciateCommonOperation("or", PCOLOR);
        $this->assertTrue($op->canSkip());
    }

    public function testTradeGood() {
        $rule = "?(n_skaill:cow)";
        $color = PCOLOR;
        $this->game->machine->push($rule, PCOLOR);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_paygain);
        $this->assertTrue($op->canSkip());
        $this->assertFalse($op->canResolveAutomatically());
        $this->assertFalse($op->expandOperation());
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertEquals("paygain", $op->getType());
        $this->game->tokens->createTokens();
        $this->game->effect_incCount(PCOLOR, "skaill", 1, "");
        $this->assertEquals(1, $this->game->tokens->getTrackerValue(PCOLOR, "skaill"));
        $op->action_resolve([
            Operation::ARG_TARGET => "confirm",
        ]);

        $this->dispatch(StateConstants::STATE_MACHINE_HALTED);
        $this->assertEquals(1, $this->game->tokens->getTrackerValue(PCOLOR, "cow"));
        $this->assertEquals(0, $this->game->tokens->getTrackerValue(PCOLOR, "skaill"));
    }

    public function testCoTag() {
        $rule = "cotag(1,wool/stone)";
        $color = PCOLOR;
        $this->game->tokens->dbSetTokenLocation("action_main_6_$color", "tableau_$color", 1);
        $this->game->machine->push($rule, $color);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_cotag);
        $this->assertEquals("wool/stone", $op->getParam(1, ""));
        $this->dispatchOneStep(GameDispatch::class);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_or);
        $this->dispatchOneStep(PlayerTurn::class);
    }

    public function testRangedOr() {
        $rule = "4(wool/stone)";
        $color = PCOLOR;
        $this->game->machine->push($rule, $color, ["reason" => "xxx"]);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals("xxx", $op->getReason());
        $this->assertEquals("4(wool/stone)", $op->getTypeFullExpr());
        $this->dispatchOneStep();
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals("xxx", $op->getReason());
        $this->assertEquals("4(wool/stone)", $op->getTypeFullExpr());
        $this->dispatchOneStep(PlayerTurn::class);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $t = $op->getArgs()["target"];
        $op->action_resolve([
            Operation::ARG_TARGET => [$t[0] => 3],
        ]);
        $this->dispatchOneStep(GameDispatch::class);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals("wool/stone", $op->getTypeFullExpr());
    }

    public function testSeq() {
        $rule = "4(cow,wood)";
        $color = PCOLOR;
        $this->game->tokens->createTokens();
        $this->assertEquals(0, $this->game->tokens->getTrackerValue(PCOLOR, "cow"));
        $this->game->machine->push($rule, PCOLOR);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_seq);
        $this->assertFalse($op->canSkip());
        $this->assertTrue($op->canResolveAutomatically());
        $this->dispatchOneStep(GameDispatch::class);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertEquals("cow", $op->getType());

        $this->dispatch(StateConstants::STATE_MACHINE_HALTED);
        $this->assertEquals(4, $this->game->tokens->getTrackerValue(PCOLOR, "cow"));
        $this->assertEquals(4, $this->game->tokens->getTrackerValue(PCOLOR, "wood"));
    }

    public function testFurnish() {
        $rule = "furnish,(barley/skaill)";
        $color = PCOLOR;
        $res = OpExpression::parseExpression($rule);
        $this->assertEquals($rule, OpExpression::str($res));
        $this->game->tokens->createTokens();
        $this->game->effect_incCount(PCOLOR, "hide", 1, "");
        $this->game->machine->push($rule, PCOLOR);
        $op = $this->dispatchOneStep();
        $this->assertTrue($op instanceof Op_furnish);
        $op = $this->dispatchOneStep();
        $this->assertTrue($op instanceof Op_furnishPay);
        $op = $this->dispatchOneStep();
        $this->assertTrue($op instanceof Op_or);
        $op = $this->dispatchOneStep();
        $this->assertTrue($op instanceof Op_pay);
        $this->assertEquals("n_hide", $op->getTypeFullExpr());
        $this->dispatch();
    }

    public function testData() {
        $color = PCOLOR;
        $this->game->machine->push("craft", PCOLOR, ["paid" => true, "card" => "action_main_3_$color"]);
        /** @var Op_craft */
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_craft);
        $this->assertTrue($op->isPaid());
        $this->assertFalse($op->requireConfirmation());
        $this->dispatchOneStep(GameDispatch::class);
    }

    public function testGatherWood() {
        $rule = "(2wood)/(n_wood:deer)";
        $color = PCOLOR;
        $res = OpExpression::parseExpression($rule);
        $this->assertEquals("(2wood)/(n_wood:deer)", OpExpression::str($res));

        $this->game->tokens->createTokens();
        $this->game->effect_incCount(PCOLOR, "wood", 1, "");

        $this->game->machine->push($rule, PCOLOR);
        $this->dispatchOneStep(PlayerTurn::class);
        /** @var ComplexOperation */
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals("2(wood)/(n_wood:deer)", $op->getTypeFullExpr(true));
        $this->assertEquals("2(wood)", $op->delegates[0]->getTypeFullExpr());
        $this->assertEquals("n_wood:deer", $op->delegates[1]->getTypeFullExpr());
    }

    public function testTask() {
        $this->game->tokens->createTokens();
        $this->game->effect_incCount(PCOLOR, "wood", 1, "");

        $action_tile = "card_task_4";
        $this->game->machine->push("task", PCOLOR, ["card" => $action_tile]);
        /** @var Op_task */
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertFalse($op->requireConfirmation());
        $this->assertTrue($op instanceof Op_task);
        $this->assertEquals($action_tile, $op->getCard());
        $this->assertEquals([$action_tile], $op->getPossibleMoves());

        /** @var Op_task */
        $op = $this->dispatchOneStep();
        $this->assertTrue($op instanceof Op_paygain);
    }

    public function testTurnall() {
        if (!$this->game->machine->isMultiplayerSupported()) {
            $this->assertTrue(true);
            return;
        }
        $this->game->tokens->createTokens();

        $this->game->machine->queue("turnall", OpMachine::GAME_MULTI_COLOR, ["num" => 1]);
        $this->game->machine->queue("barrier", OpMachine::GAME_BARIER_COLOR);

        $op = $this->dispatch(MultiPlayerMaster::class);
        $this->assertTrue($op instanceof Op_turnall);
        //$this->assertTrue($op instanceof Op_turnpick);
        $state = $this->game->machine->multiplayerDistpatch();
        $this->assertEquals(null, $state);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_turn);
        $op->destroy();
        $state = $this->game->machine->multiplayerDistpatch();
        $this->assertEquals(GameDispatchForced::class, $state);
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertTrue($op instanceof Op_barrier);
        $op->destroy();
        $state = $this->game->machine->multiplayerDistpatch();
        $this->assertEquals(GameDispatchForced::class, $state);
        $this->dispatch(GameDispatchForced::class);
        $this->dispatch(StateConstants::STATE_MACHINE_HALTED);
    }

    public function testFurnishAutoPayWoolOnly() {
        // Regression test for bug: when 2(n_hide/2n_wool) is auto-resolved with only wool
        // available (no hide), should spend 4 wool, not 3.
        $color = PCOLOR;
        $this->game->tokens->createTokens();
        // Give player 4 wool, no hide
        $this->game->effect_incCount($color, "wool", 4, "");
        $this->assertEquals(4, $this->game->tokens->getTrackerValue($color, "wool"));
        $this->assertEquals(0, $this->game->tokens->getTrackerValue($color, "hide"));
        // The furnish cost at position 1 is 2(n_hide/2n_wool): 2x (1 hide OR 2 wool)
        // With no hide, the or auto-resolves to wool; should spend 4 total
        $this->game->machine->push("2(n_hide/2n_wool)", $color);
        $this->dispatch(StateConstants::STATE_MACHINE_HALTED);
        $this->assertEquals(0, $this->game->tokens->getTrackerValue($color, "wool"), "Should have spent all 4 wool");
    }

    public function testFurnishPayWoolWithChoice() {
        // Test: player has 2 hide and 4 wool, manually selects 1 wool unit (=2 wool) for first choice.
        // Should spend 2 wool and then offer the remaining 1 choice (n_hide/2n_wool).
        $color = PCOLOR;
        $this->game->tokens->createTokens();
        $this->game->effect_incCount($color, "hide", 2, "");
        $this->game->effect_incCount($color, "wool", 4, "");

        $this->game->machine->push("2(n_hide/2n_wool)", $color);

        // Both options available — should stop for player input
        $op = $this->dispatch(PlayerTurn::class);
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals(2, $op->getCount());

        // Player selects 1 wool unit (choice_1=1 means 2n_wool × 1 = 2 wool)
        $this->game->fakeUserAction($op, ["choice_1" => 1]);

        // Dispatch resolves n_wool payment, then stops at the remaining OR choice
        $op = $this->dispatch(PlayerTurn::class);

        $this->assertEquals(2, $this->game->tokens->getTrackerValue($color, "wool"), "Should have spent 2 wool");
        $this->assertEquals(2, $this->game->tokens->getTrackerValue($color, "hide"), "Hide should be untouched");
        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals(1, $op->getCount(), "One remaining choice");
        $this->assertEquals("n_hide/2(n_wool)", $op->getTypeFullExpr(), "Remainder should offer same options");
    }

    public function testOpActRecruitWorkerPlacement() {
        $game = $this->game();
        $owner = PCOLOR;

        // Setup: Create all tokens from material
        $game->tokens->createTokens();

        // Move action tiles and workers to appropriate locations
        $game->tokens->dbSetTokenLocation("action_main_1_$owner", "tableau_$owner", 0);
        $game->tokens->dbSetTokenLocation("action_main_2_$owner", "tableau_$owner", 0);
        $game->tokens->dbSetTokenLocation("action_special_3", "tableau_$owner", 0);
        $game->tokens->dbSetTokenLocation("worker_0_$owner", "tableau_$owner", 1);
        $game->tokens->dbSetTokenLocation("worker_1_$owner", "tableau_$owner", 1);
        $game->tokens->dbSetTokenLocation("worker_2_$owner", "tableau_$owner", 1);
        $game->tokens->dbSetTokenLocation("worker_3_$owner", "tableau_$owner", 1); // Extra worker to check occupied status
        $game->tokens->dbSetTokenLocation("worker_0_000000", "limbo", 1);

        // Add resources so actions can be performed without cost errors
        $game->effect_incCount($owner, "wool", 10, "");
        $game->effect_incCount($owner, "hide", 10, "");
        $game->effect_incCount($owner, "food", 10, "");

        // Test: THE BUG - Recruit flipped BUT action tile NOT flipped - should only allow 2 workers total
        // This was the bug: it was allowing 3 workers (2 small + 1 large) on ALL actions when recruit was flipped
        $game->tokens->dbSetTokenState("action_special_3", 1); // Recruit flipped
        $game->tokens->dbSetTokenState("action_main_2_$owner", 0); // Action tile NOT flipped

        // Place 1 small worker
        $game->tokens->dbSetTokenLocation("worker_0_$owner", "action_main_2_$owner", 1);

        // Check possible moves - should show that we need a large worker next (workersPerAction = 2, not 3)
        $op = $game->machine->instanciateOperation("act", $owner);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("action_main_2_$owner", $moves);
        // Should require large worker for second slot (not allow another small worker)
        $this->assertEquals("worker_1_$owner", $moves["action_main_2_$owner"]["worker"]);

        // Place large worker - should now be full
        $game->tokens->dbSetTokenLocation("worker_1_$owner", "action_main_2_$owner", 1);

        $op = $game->machine->instanciateOperation("act", $owner);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("action_main_2_$owner", $moves);
        $this->assertEquals(3, $moves["action_main_2_$owner"]["q"]); // MA_ERR_OCCUPIED - only 2 workers allowed

        // Reset for second test
        $game->tokens->dbSetTokenLocation("worker_0_$owner", "tableau_$owner", 1);
        $game->tokens->dbSetTokenLocation("worker_1_$owner", "tableau_$owner", 1);

        // Test 2: Recruit AND action tile BOTH flipped - should allow 3 workers (2 small + 1 large)
        $game->tokens->dbSetTokenState("action_special_3", 1); // Recruit flipped
        $game->tokens->dbSetTokenState("action_main_1_$owner", 1); // Action tile ALSO flipped

        // Place 1 small worker
        $game->tokens->dbSetTokenLocation("worker_0_$owner", "action_main_1_$owner", 1);

        // Place 2nd small worker
        $game->tokens->dbSetTokenLocation("worker_2_$owner", "action_main_1_$owner", 1);

        // Should NOT be occupied yet (workersPerAction = 3 when both flipped)
        $op = $game->machine->instanciateOperation("act", $owner);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("action_main_1_$owner", $moves);
        $this->assertNotEquals(3, $moves["action_main_1_$owner"]["q"]); // Not occupied, can place large worker

        // Place large worker
        $game->tokens->dbSetTokenLocation("worker_1_$owner", "action_main_1_$owner", 1);

        // Now should be occupied (3 workers placed)
        $op = $game->machine->instanciateOperation("act", $owner);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("action_main_1_$owner", $moves);
        $this->assertEquals(3, $moves["action_main_1_$owner"]["q"]); // MA_ERR_OCCUPIED
    }

    public function testRestorePlayerTablesRestoresDiscardedVillageCard() {
        // Regression test: player selects a village card (shepherd), plays through the
        // turn, then discards shepherd due to insufficient food. On "undo turn", the
        // snapshot pre-dates the village selection (shepherd was in shared cardset_N).
        // The fix must restore shepherd from discard_village back to cardset_1.
        $game = $this->game;
        $color = PCOLOR;
        $player_id = 10; // maps to PCOLOR via getPlayerColorById

        $shepherd = "card_setl_shepherd";
        $otherCard = "card_setl_other";
        $foodKey = "tracker_{$color}_food";

        // --- Set up current state (after player's turn, before undo) ---
        // Shepherd was selected then discarded: now in discard_village
        $game->tokens->db->moveToken($shepherd, "discard_village", 0);
        // Another card that was already in discard_village at snapshot time (should NOT be restored)
        $game->tokens->db->moveToken($otherCard, "discard_village", 0);
        // Player's food tracker has been spent (state=2, down from snapshot state=5)
        $game->tokens->db->moveToken($foodKey, "tracker_{$color}", 2);
        // Another player's token — should not be touched
        $otherPlayerFood = "tracker_" . BCOLOR . "_food";
        $game->tokens->db->moveToken($otherPlayerFood, "tracker_" . BCOLOR, 8);

        // --- Snapshot (saved_data): state at start of turn, before village selection ---
        // shepherd was in cardset_1 (shared location, no owner color in key or location)
        // otherCard was ALREADY in discard_village (so it must not be restored)
        $saved_data = [
            ["token_key" => $shepherd,       "token_location" => "cardset_1",          "token_state" => 0],
            ["token_key" => $otherCard,      "token_location" => "discard_village",     "token_state" => 0],
            ["token_key" => $foodKey,        "token_location" => "tracker_{$color}",    "token_state" => 5],
            ["token_key" => $otherPlayerFood,"token_location" => "tracker_" . BCOLOR,  "token_state" => 10],
        ];

        $game->restorePlayerTables("token", $saved_data, ["player_id" => $player_id]);

        // Shepherd must be restored to cardset_1 (the fix: 4th filter condition)
        $info = $game->tokens->db->getTokenInfo($shepherd);
        $this->assertEquals("cardset_1", $info["location"], "Shepherd must be restored from discard_village to cardset_1");

        // Card already in discard at snapshot time must remain in discard (not moved)
        $info = $game->tokens->db->getTokenInfo($otherCard);
        $this->assertEquals("discard_village", $info["location"], "Card already in discard at snapshot time must stay in discard");

        // Player's own tracker must be restored to snapshot value
        $info = $game->tokens->db->getTokenInfo($foodKey);
        $this->assertEquals(5, (int) $info["state"], "Food tracker must be restored to snapshot state");

        // Other player's tokens must not be touched
        $info = $game->tokens->db->getTokenInfo($otherPlayerFood);
        $this->assertEquals(8, (int) $info["state"], "Other player's tokens must not be affected");
    }
}
