<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae;

use Bga\Games\skarabrae\Common\OpExpression;
use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\Db\DbMachine;
use Bga\Games\skarabrae\OpCommon\ComplexOperation;
use Bga\Games\skarabrae\OpCommon\UnresolvedOperation;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\PlayerTurnConfirm;

use BgaSystemException;
use Exception;
use ReflectionClass;
use Throwable;

class OpMachine {
    protected Game $game;

    public function __construct(protected DbMachine $db = new DbMachine()) {
        $this->game = Game::$instance;
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

        if (count($ops) > 1) {
            $data = Operation::decodeData($dop["data"]);
            $operand = $data["xop"] ?? ",";
            $mnemonic = self::opToMnemonic($operand);
            unset($data["xop"]);
            /** @var ComplexOperation */
            $top = $this->instanciateCommonOperation($mnemonic, $dop["owner"], $data)->withDataField("op", $operand);
            foreach ($ops as $sub) {
                $subOp = $this->instanciateOperationFromDbRow($sub)->withDataField("xop", $operand);
                $top->withDelegate($subOp);
            }
            return $top;
        }

        return $this->instanciateOperationFromDbRow($dop);
    }

    function instanciateOperationFromDbRow($dop): Operation {
        return $this->instanciateOperation($dop["type"], $dop["owner"], $dop["data"], $dop["id"]);
    }

    function instanciateOperation(string $type, ?string $owner = null, mixed $data = null, mixed $id = 0): Operation {
        try {
            $expr = OpExpression::parseExpression($type);
            $operand = OpExpression::getop($expr);
            if ($owner === null) {
                $owner = $this->game->getActivePlayerColor();
            }

            if ($id) {
                $id = (int) $id;
            }
            //[op min max arg1 arg2 arg3]...

            if (!$expr->isSimple()) {
                $mnemonic = self::opToMnemonic($operand);
                if ($mnemonic == $type) {
                    throw new BgaSystemException("infinite rec $type");
                }
                /** @var ComplexOperation */
                $op = $this->instanciateCommonOperation($mnemonic, $owner, $data, $id)->withDataField("op", $operand);
                foreach ($expr->args as $arg) {
                    $sub = $this->instanciateOperation(OpExpression::str($arg), $owner, $data)->withDataField("xop", $operand);
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
            return $this->instanciateSimpleOperation($unrangedType, $owner, $data, $id)->withParams($params)->withCounts($expr);
        } catch (Exception $e) {
            throw new BgaSystemException("Cannot instanciate '$type': " . $e->getMessage());
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
    function instanciateCommonOperation(string $type, string $owner, mixed $data = null, int $id = 0): Operation {
        $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\OpCommon\\Op_$type");
        $instance = $reflectionClass->newInstance($type, $owner, $data);
        $instance->withId($id);
        return $instance;
    }

    function instanciateSimpleOperation(string $type, string $owner, mixed $data = null, int $id = 0): Operation {
        if (strlen($type) > 80) {
            throw new BgaSystemException("Cannot instantice op");
        }

        $expr = OpExpression::parseExpression($type);
        if ($expr->isSimple()) {
            $operandclass = array_get($this->getOperationRules($type), "class", "Op_$type");

            // Instantiate the class with constructor arguments
            try {
                $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\Operations\\$operandclass");
                $instance = $reflectionClass->newInstance($type, $owner, $data);
            } catch (Throwable $e) {
                $instance = new UnresolvedOperation($type, $owner, $data);
            }
        } else {
            $instance = new UnresolvedOperation($type, $owner, $data);
        }
        $instance->withId($id);

        return $instance;
    }

    public function getOperationRules($id, $field = "*", $def = null) {
        return $this->game->getRulesFor("Op_$id", $field, $def);
    }

    function getTopOperations($owner) {
        $ops = $this->db->getTopOperations($owner);
        return $ops;
    }

    function destroy(Operation $op) {
        $op->destroy();
    }

    function hide(int $id) {
        $this->db->hide($id);
    }

    function renice($list, $rank) {
        $this->db->renice($list, $rank);
    }

    function interrupt(int $rank = 0, int $count = 1) {
        $this->db->interrupt($rank, $count);
    }

    function push(string $type, ?string $owner = null, mixed $data = null) {
        $op = $this->db->createRow($type, $owner, $data);
        $this->interrupt();
        return $this->db->insertRow(1, $op);
    }

    function queue(string $type, ?string $owner = null, mixed $data = null) {
        $rank = $this->db->getExtremeRank(true);
        $rank++;
        $op = $this->db->createRow($type, $owner, $data);
        return $this->db->insertRow($rank, $op);
    }

    function put(string $type, ?string $owner = null, mixed $data = null, int $rank = 1) {
        $op = $this->db->createRow($type, $owner, $data);
        return $this->db->insertRow($rank, $op);
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

    function dispatchAll(int $n = 1000) {
        // dispatch does mulple rounds without switching state, need to watch for notif limit
        while ($n-- > 0) {
            $state = $this->dispatchOne();
            if ($state && $state !== GameDispatch::class) {
                return $state;
            }
        }
        return PlayerTurnConfirm::class;
    }
    function dispatchOne() {
        $op = $this->createTopOperationFromDb(0); // 0 player means we not sure yet
        if (!$op) {
            return StateConstants::STATE_MACHINE_HALTED;
        }
        if ($this->expandOperation($op)) {
            $this->destroy($op);
            return GameDispatch::class;
        }
        //$this->game->notify->all("message", "starting op " . $op->getType());
        return $op->onEnteringGameState();
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
        // if (!$this->isAtomicOperation($type) && $op["count"] == $op["mcount"]) {
        if ($op->expandOperation()) {
            //$this->machine->hide($op);
            // sanity to prevent recursion
            $operations = $this->getTopOperations(null);
            if (count($operations) == 0) {
                $this->game->systemAssert("Failed expand for $type. Halt");
            }

            // restore orignal rank
            //$this->machine->renice($operations, $op['rank']);

            // $nop = array_shift($operations);
            // if ($nop["type"] == $type && $nop["mcount"] == $op["mcount"] && $nop["count"] == $op["count"]) {
            //     $this->game->systemAssert("Failed expand for $type. Recursion");
            // }
            return true;
        }
        return false;
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
        $op = $this->createTopOperationFromDb($player_id);
        $op->undo($move_id);
        $this->push("nop", $this->game->getActivePlayerColor());
        return GameDispatch::class;
    }
}
