<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\OpCommon;

use Bga\Games\skarabrae\OpCommon\OpExpression;
use Bga\Games\skarabrae\OpCommon\OpExpressionRanged;
use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\Db\DbMachine;
use Bga\Games\skarabrae\Material;
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\UnresolvedOperation;
use Bga\Games\skarabrae\States\GameDispatchForced;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\MultiPlayerMaster;
use Bga\Games\skarabrae\States\MultiPlayerTurnPrivate;
use Bga\Games\skarabrae\States\PlayerTurnConfirm;

use BgaSystemException;
use Exception;
use ReflectionClass;
use Throwable;

use function Bga\Games\skarabrae\toJson;

class OpMachine {
    const MA_GAME_DISPATCH_MAX = 1000;
    protected Game $game;

    public function __construct(protected DbMachine $db = new DbMachine()) {
        $this->game = Game::$game;
    }

    function createTopOperationFromDb($player_id): ?Operation {
        if ($player_id === 0) {
            $owner = null;
        } else {
            $owner = $this->game->getPlayerColorById($player_id);
        }
        return $this->createTopOperationFromDbForOwner($owner);
    }

    function createTopOperationFromDbForOwner(?string $owner): ?Operation {
        $ops = $this->db->getTopOperations($owner);
        if (count($ops) == 0) {
            return null;
        }
        $dop = reset($ops);

        return $this->instanciateOperationFromDbRow($dop);
    }

    function instanciateOperationFromDbRow(mixed $dop): Operation {
        if (is_string($dop["data"])) {
            $data = Operation::decodeData($dop["data"]);
        } else {
            $data = $dop["data"];
        }
        $args = $data["args"] ?? [];
        if ($args) {
            unset($data["args"]);
        }
        $op = $this->instanciateOperation($dop["type"], $dop["owner"], $data, $dop["id"] ?? 0);
        if ($op instanceof ComplexOperation) {
            foreach ($args as $sub) {
                $subOp = $this->instanciateOperationFromDbRow(["owner" => $dop["owner"]] + $sub);
                $op->withDelegate($subOp);
            }
        }
        return $op;
    }

    function instanciateOperation(string $type, ?string $owner = null, mixed $data = null, mixed $id = 0): Operation {
        try {
            if ($id) {
                $id = (int) $id;
            } else {
                $id = 0;
            }

            $expr = OpExpression::parseExpression($type);
            $op = $this->exprToOperation($expr, $owner)->withId($id)->withData($data);
            return $op;
        } catch (Exception $e) {
            throw new BgaSystemException("Cannot instanciate '$type': " . $e->getMessage());
        }
    }
    function exprToOperation(OpExpression $expr, string $owner) {
        $operand = OpExpression::getop($expr);
        //[op min max arg1 arg2 arg3]...

        if (!$expr->isSimple()) {
            $mnemonic = self::opToMnemonic($operand);
            /** @var ComplexOperation */
            $op = $this->instanciateCommonOperation($mnemonic, $owner);
            foreach ($expr->args as $arg) {
                $sub = $this->exprToOperation($arg, $owner);
                $op->withDelegate($sub);
            }
            $op->withCounts($expr);
            return $op;
        }

        $unrangedType = OpExpression::str($expr->toUnranged());
        $matches = null;
        $params = null;
        if (preg_match("/^(\w+)\((.*)\)$/", $unrangedType, $matches)) {
            // function call
            $params = $matches[2];
            $unrangedType = $matches[1];
        }
        $sub = $this->instanciateSimpleOperation($unrangedType, $owner)->withParams($params);
        if ($expr instanceof OpExpressionRanged) {
            if ($sub instanceof CountableOperation) {
                $sub->withCounts($expr);
                return $sub;
            } else {
                /** @var ComplexOperation */
                $op = $this->instanciateCommonOperation("seq", $owner);
                $op->withDelegate($sub)->withCounts($expr);
                return $op;
            }
        } else {
            return $sub;
        }
    }
    static function opToMnemonic(string $operand) {
        return match ($operand) {
            "!" => "atomic",
            "+" => "order",
            "," => "seq",
            ":" => "paygain",
            ";" => "seq",
            "^" => "unique",
            "/" => "or",
            default => throw new BgaSystemException("Unknown operator $operand"),
        };
    }
    function instanciateCommonOperation(string $type, ?string $owner = null, mixed $data = null): Operation {
        $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\Operations\\Op_$type");
        $instance = $reflectionClass->newInstance($type, $owner, $data);
        return $instance;
    }

    function instanciateSimpleOperation(string $type, ?string $owner = null, mixed $data = null): Operation {
        if (strlen($type) > 80) {
            throw new BgaSystemException("Cannot instantice op");
        }

        $operandclass = $this->game->getRulesFor("Op_$type", "class", "Op_$type");

        // Instantiate the class with constructor arguments
        try {
            $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\Operations\\$operandclass");
            $instance = $reflectionClass->newInstance($type, $owner, $data);
        } catch (Throwable $e) {
            throw new BgaSystemException("Cannot instanticate $type: " . $e->getMessage());
        }

        return $instance;
    }

    function getTopOperations($owner) {
        $ops = $this->db->getTopOperations($owner);
        return $ops;
    }

    function hide(int $id) {
        $this->db->hide($id);
    }

    function interrupt(int $rank = 0, int $count = 1) {
        $this->db->interrupt($rank, $count);
    }

    function push(string $type, ?string $owner = null, mixed $data = null) {
        $this->interrupt();
        return $this->put($type, $owner, $data, 1);
    }

    function queue(string $type, ?string $owner = null, mixed $data = null) {
        $rank = $this->db->getExtremeRank(true);
        $rank++;
        return $this->put($type, $owner, $data, $rank);
    }

    function put(string $type, ?string $owner = null, mixed $data = null, int $rank = 1) {
        $op = $this->db->createRow($type, $owner, $data);
        return $this->db->insertRow($rank, $op);
    }

    function insertRow(mixed $row, int $rank = 1) {
        return $this->db->insertRow($rank, $row);
    }

    function insert(string $type, ?string $owner = null, mixed $data = null, ?int &$rank = null) {
        if ($rank === null) {
            $rank = 1;
        }
        $this->interrupt($rank);
        $this->put($type, $owner, $data, $rank);
        $rank++;
    }

    //DISPATCH

    function dispatchAll(int $n = OpMachine::MA_GAME_DISPATCH_MAX) {
        // dispatch does mulple rounds without switching state, need to watch for notif limit
        for ($i = 0; $i < $n; $i++) {
            if ($this->isMultiplayerOperationMode()) {
                return MultiPlayerMaster::class;
            }
            $state = $this->dispatchOne();
            if ($state && $state !== GameDispatch::class) {
                return $state;
            }
        }
        return PlayerTurnConfirm::class;
    }
    function dispatchOne() {
        //$this->game->debugLog("- SINGLE: machine top: ", array_values($this->getTopOperations(null)));
        $op = $this->createTopOperationFromDbForOwner(null); // null means any
        if (!$op) {
            return StateConstants::STATE_MACHINE_HALTED;
        }
        if ($this->expandOperation($op)) {
            $op->destroy();
            return GameDispatch::class;
        }
        //$this->game->notify->all("message", "starting op " . $op->getType());
        return $op->onEnteringGameState();
    }

    function multiplayerDistpatch(int $n = OpMachine::MA_GAME_DISPATCH_MAX) {
        //$this->game->debugLog("- MULTI: machine top: ", array_values($this->getTopOperationsMulti()));
        if (!$this->isMultiplayerOperationMode()) {
            // fall out of multiplayer state
            return GameDispatchForced::class;
        }
        //$this->game->gamestate->setAllPlayersMultiactive();
        $this->game->dbSetPlayerMultiactive(null, 1);
        $players = $this->game->loadPlayersBasicInfos();
        $changedState = null;
        foreach ($players as $player_id => $player_info) {
            $state = $this->multiplayerDistpatchPrivate($player_id, $n);

            if ($state !== MultiPlayerMaster::class) {
                $changedState = $state;
            }
        }
        //if (!$this->game->gamestate->updateMultiactiveOrNextState($changedState))
        return $changedState;
    }

    function multiplayerDistpatchPrivate(int $player_id, int $n) {
        $state = GameDispatchForced::class;
        $color = $this->game->getPlayerColorById($player_id);
        for ($i = 0; $i < $n; $i++) {
            $operations = $this->getTopOperationsMulti($color);
            $isMulti = count($operations) > 0; // $this->hasMultiPlayerOperations($operations);
            // $this->game->debugLog("- MULTI SINGLE $i: machine top for $color: ", array_values($operations));
            if (!$isMulti) {
                $this->game->gamestate->unsetPrivateState($player_id);
                $this->game->dbSetPlayerMultiactive($player_id, 0);
                //$this->game->gamestate->setPlayerNonMultiactive($player_id, "notpossible");
                return GameDispatchForced::class;
            }
            //$this->game->dbSetPlayerMultiactive($player_id, 1);
            $this->game->gamestate->setPlayersMultiactive([$player_id], "notpossible", false);

            $dop = reset($operations);
            $op = $this->instanciateOperationFromDbRow($dop);
            if ($this->expandOperation($op)) {
                $op->destroy();
                continue;
            }

            $state = $op->onEnteringGameState();
            if ($state === null || $state === GameDispatch::class) {
                continue;
            }
            break;
        }
        if ($i >= OpMachine::MA_GAME_DISPATCH_MAX) {
            return PlayerTurnConfirm::class;
        }
        if ($state === MultiPlayerTurnPrivate::class) {
            $this->game->gamestate->setPrivateState($player_id, StateConstants::STATE_MULTI_PLAYER_TURN_PRIVATE);
            return MultiPlayerMaster::class;
        }
        if ($state === GameDispatch::class || $state === null) {
            return MultiPlayerMaster::class;
        }
        return $state;
    }

    function expandOperation(Operation $op, $count = null) {
        $type = $op->getType();
        if ($count !== null) {
            // user resolved the count
            // $this->machine->checkValidCountForOp($op, $count);
            // $op["count"] = $count;
            // $op["mcount"] = $count;
            $this->game->systemAssert("Not implemented");
        }

        if ($op->expandOperation()) {
            $operations = $this->getTopOperations(null);
            if (count($operations) == 0) {
                $this->game->systemAssert("Failed expand for $type. Halt");
            }
            return true;
        }
        return false;
    }

    function getTopOperationsMulti($owner = null) {
        $operations = $this->getTopOperations($owner);
        if (count($operations) > 0) {
            $op = reset($operations);
            if ($op["type"] == "barrier") {
                return [];
            }
            if ($op["owner"] === null) {
                return [];
            }
            $barrank = $this->getBarrierRank();
            if ($barrank > 0) {
                if ($op["rank"] > $barrank) {
                    return [];
                }
            } else {
                return [];
            }
        }
        return $operations;
    }
    function isMultiplayerOperationMode() {
        return count($this->getTopOperationsMulti()) > 0;
    }

    function getBarrierRank() {
        $ops = $this->db->getOperations(null, "barrier");
        if (count($ops) == 0) {
            return -1;
        }
        $op = reset($ops);
        $barrank = $op["rank"];
        return $barrank;
    }

    /** Debug functions */

    function gettablearr() {
        return $this->db->gettablearr();
    }

    // STATE FUNCTIONS

    function getArgs(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);

        return $op->getArgs();
    }

    function onEnteringPlayerState(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->onEnteringPlayerState();
    }

    function action_resolve(int $player_id, mixed $data) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_resolve($data);
    }

    function action_skip(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_skip();
    }
    function action_whatever(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_whatever();
    }

    function action_undo(int $player_id, int $move_id = 0) {
        //$op = $this->createTopOperationFromDb($player_id);
        //$op->undo($move_id);
        //$this->push("nop", $this->game->getActivePlayerColor());
        $this->game->undoRestorePoint();
        return GameDispatch::class;
    }
}
