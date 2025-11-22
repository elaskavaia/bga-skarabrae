<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae;

use Bga\Games\skarabrae\Common\Operation;
use Bga\Games\skarabrae\Common\ComplexOperation;
use Bga\Games\skarabrae\Common\OpExpression;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\StateConstants;
use Bga\Games\skarabrae\Db\DbMachine;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\PlayerTurnConfirm;

use BgaSystemException;

use ReflectionClass;

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
            $mnemonic = $data["parent"] ?? "seq";
            /** @var ComplexOperation */
            $top = $this->instanciateSimpleOperation($mnemonic, $dop["owner"]);
            foreach ($ops as $sub) {
                $subOp = $this->instanciateOperationFromDbRow($sub);
                $top->withSub($subOp);
            }
            return $top;
        }

        return $this->instanciateOperationFromDbRow($dop);
    }

    function instanciateOperationFromDbRow($dop): Operation {
        return $this->instanciateOperation($dop["type"], $dop["owner"], $dop["data"], $dop["id"]);
    }

    function instanciateOperation(string $type, string $owner, mixed $data = null, mixed $id = 0): Operation {
        $expr = OpExpression::parseExpression($type);
        $operand = OpExpression::getop($expr);

        if ($id) {
            $id = (int) $id;
        }
        //[op min max arg1 arg2 arg3]...

        if (!$expr->isSimple()) {
            $mnemonic = self::opToMnemonic($operand);
            if ($mnemonic == $type) {
                throw new BgaSystemException("infinite rec $type");
            }
            return $this->instanciateSimpleOperation($mnemonic, $owner, $data, $id)->withExpr($expr);
        }

        $unrangedType = OpExpression::str($expr->toUnranged());
        $matches = null;
        $params = null;
        if (preg_match("/^(\w+)\((.*)\)$/", $unrangedType, $matches)) {
            // function call
            $params = $matches[2];
            $unrangedType = $matches[1];
        }
        return $this->instanciateSimpleOperation($unrangedType, $owner, $data, $id)->withParams($params)->withExpr($expr);
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

    function instanciateSimpleOperation(string $type, string $owner, mixed $data = null, int $id = 0): Operation {
        if (strlen($type) > 80) {
            throw new BgaSystemException("Cannot instantice op");
        }

        $operandclass = array_get($this->getOperationRules($type), "class", "Op_$type");

        $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\Operations\\$operandclass");
        $args["type"] = $type;

        // Instantiate the class with constructor arguments
        $instance = $reflectionClass->newInstance($type, $owner, $data);
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

    function store(Operation $op, int $rank) {
        $oprow = $this->db->createRow($op->getType(), $op->getOwner(), $op->getData());
        $this->db->insertRow($rank, $oprow);
    }

    function destroy(Operation $op) {
        $id = $op->getId();
        if ($id > 0) {
            $this->db->hide($id);
        }
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
        //$this->game->notify->all("message", "starting op " . $op->getType());
        return $op->onEnteringGameState();
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
