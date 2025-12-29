<?php

declare(strict_types=1);
namespace Bga\Games\skarabrae\Operations;

use Bga\Games\skarabrae\OpCommon\Operation;

/**
 * Tag counter. Count specific tags to change counter of the  operation passed as second arg
 */
class Op_cotag extends Operation {
    function resolve() {
        // counter function, followed by expression
        $owner = $this->getOwner();
        $tnum = $this->getParam(0);
        $count = $this->game->countTags((int) $tnum, $owner);
        $op = $this->getParam(1, "");
        //$this->game->debugLog("-evaluted to $count");
        if ($count > 0) {
            $this->queue("$count($op)", $owner, null, $this->getReason());
        }
        if ($tnum <= 4) {
            $skaill = $this->game->tokens->getTrackerValue($owner, "skaill");
            if ($skaill) {
                $this->queue("[0,{$skaill}](n_skaill:$op)", $owner, null, $this->getReason());
            }
        }
        return 1;
    }

    function getButtonName() {
        $args = $this->getArgs();
        if ($args["count_skaill"] > 0) {
            return '${other_op} x ${mcount}..${count}';
        }
        return '${other_op} x ${mcount}';
    }

    public function getExtraArgs() {
        $owner = $this->getOwner();
        $tnum = $this->getParam(0);
        $count = $this->game->countTags((int) $tnum, $owner);
        $op = $this->getParam(1, "nop");
        $sub = $this->game->machine->instanciateOperation($op, $owner);
        $args = [];
        $args["other_op"] = ["log" => $sub->getButtonName(), "args" => $sub->getExtraArgs()];
        $args["count"] = $count;
        $args["mcount"] = $count;
        $args["count_skaill"] = 0;
        if ($tnum <= 4) {
            $skaill = $this->game->tokens->getTrackerValue($owner, "skaill");
            if ($skaill) {
                $args["count_skaill"] = $skaill;
                $args["count"] = $count + $skaill;
            }
        }
        return $args;
    }
}
