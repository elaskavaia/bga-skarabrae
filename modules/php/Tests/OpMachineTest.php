<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\Tests;

use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\OpCommon\CountableOperation;
use Bga\Games\skarabrae\OpCommon\OpMachine;
use Bga\Games\skarabrae\Operations\Op_gain;
use Bga\Games\skarabrae\Operations\Op_or;

use PHPUnit\Framework\TestCase;

class OpMachineTest extends TestCase {
    static Game $game;
    private OpMachine $factory;
    public static function setUpBeforeClass(): void {
        // Ensure Game instance is created
        self::$game = new Game();
    }

    public function setup(): void {
        $this->factory = new OpMachine();
    }

    public function testInstanciateOperation() {
        $op = $this->factory->instanciateOperation("fish", "aaa");

        $this->assertTrue($op instanceof Op_gain);
        $this->assertEquals($op->getId(), 0);
        $this->assertEquals($op->getType(), "fish");
    }

    public function testInstanciateComplexOperation() {
        $op = $this->factory->instanciateOperation("fish/pass", "aaa");

        $this->assertTrue($op instanceof Op_or);
        $this->assertEquals($op->getId(), 0);
        $this->assertEquals($op->getType(), "or");
    }

    public function testInstanciateCountableOperation() {
        $op = $this->factory->instanciateOperation("[0,5]shell", "aaa");

        $this->assertTrue($op instanceof CountableOperation);
        $this->assertEquals($op->getId(), 0);
        $this->assertEquals($op->getType(), "shell");
        $this->assertEquals($op->getDataField("count"), "5");
    }

    public function testInstanciateCountableOperationWithParam() {
        $op = $this->factory->instanciateOperation("[0,5]fish(a)", "aaa");

        $this->assertTrue($op instanceof CountableOperation);
        $this->assertEquals($op->getId(), 0);
        $this->assertEquals($op->getType(), "fish");
        $this->assertEquals($op->getDataField("params"), "a");
        $this->assertEquals($op->getDataField("count"), "5");
    }
}
