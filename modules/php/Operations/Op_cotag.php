<?php

declare(strict_types=1);
namespace Bga\Games\skarabrae\Operations;

use Bga\Games\skarabrae\OpCommon\Operation;
use Bga\Games\skarabrae\Material;
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
        return '${count} x ${p1}';
    }

    public function getExtraArgs() {
        $owner = $this->getOwner();
        $tnum = $this->getParam(0);
        $count = $this->game->countTags((int) $tnum, $owner);
        $op = $this->getParam(1, "nop");
        $sub = $this->game->machine->instanciateOperation($op, $owner);
        $args = [];
        $args["p1"] = ["log" => $sub->getButtonName(), "args" => $sub->getExtraArgs()];
        $args["count"] = $count;
        return $args;
    }
}
