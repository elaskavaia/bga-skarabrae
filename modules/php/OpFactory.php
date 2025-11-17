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

use Bga\Games\skarabrae\Operations\Operation;
use Bga\Games\skarabrae\Common\OpExpression;
use Bga\Games\skarabrae\Common\OpExpressionRanged;
use BgaSystemException;
use BgaUserException;
use Exception;
use ReflectionClass;

class OpFactory {
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

        $unrangedType = $type;
        if (!$expr->isUnranged()) {
            $unrangedType = OpExpression::str($expr->toUnranged());
        }

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
            ";" => "follow",
            "^" => "unique",
            "/" => "or",
            default => throw new Exception("Unknown operator $operand"),
        };
    }

    function instanciateSimpleOperation(string $type, string $owner, mixed $data = null, int $id = 0): Operation {
        if (strlen($type) > 80) {
            throw new BgaSystemException("Cannot instantice op");
        }
        $operandclass = "Op_$type";
        $reflectionClass = new ReflectionClass("Bga\\Games\\skarabrae\\Operations\\$operandclass");
        $args["type"] = $type;

        // Instantiate the class with constructor arguments
        $instance = $reflectionClass->newInstance($type, $owner, $data);
        $instance->withId($id);

        return $instance;
    }
}
