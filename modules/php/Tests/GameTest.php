<?php

declare(strict_types=1);

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\Notify;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\Common\Operation;
use Bga\Games\skarabrae\OpMachine;
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\PlayerTurn;
use Bga\Games\skarabrae\Tests\MachineInMem;
use PHPUnit\Framework\TestCase;

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
    }

    public function _($s): string {
        return $s;
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

    function getUserPreference(int $player_id, int $code) {
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

    public $curid;

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

    // override/stub methods here that access db and stuff
}

final class GameTest extends TestCase {
    private $game;
    function dispatchOneStep($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchOne();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        return $game->machine->getTopOperations(null);
    }
    function game(int $x = 0) {
        $game = new GameUT();
        $game->init($x);
        $this->game = $game;
        return $game;
    }

    public function testBind() {
        $game = $this->game();
        $color = PCOLOR;
        $game->machine->push("hello/pass", $color);
        $tops = $game->machine->getTopOperations($color);
        $op = array_shift($tops);
        $this->assertEquals("hello/pass", $op["type"]);

        $game->machine->dispatchOne();
        $tops = $game->machine->getTopOperations($color);
        $op = array_shift($tops);
        $this->assertEquals("hello", $op["type"]);
        $data = Operation::decodeData($op["data"]);
        $this->assertEquals("or", $data["parent"]);
        $op = array_shift($tops);
        $this->assertEquals("pass", $op["type"]);

        $op = $game->machine->createTopOperationFromDbForOwner($color);
        $this->assertEquals("or", $op->getType());
    }

    public function testGold() {
        $game = $this->game();
        $color = PCOLOR;
        $game->machine->push("[0,3]fish", $color);
        $tops = $this->dispatchOneStep(PlayerTurn::class);
        $dop = array_shift($tops);
        $op = $game->machine->instanciateOperationFromDbRow($dop);
        // simulate user action
        $state = $game->fakeUserAction($op, 2);
        $this->assertEquals(GameDispatch::class, $state);
        $tops = $this->dispatchOneStep(42);
    }

    public function testGold2() {
        $game = $this->game();
        $color = PCOLOR;
        $game->machine->push("2fish", $color);
        $this->dispatchOneStep(GameDispatch::class);
        $this->dispatchOneStep(42);
    }
}
