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

namespace Bga\Games\skarabrae\Operations;

use Bga\Games\skarabrae\Material;
use Bga\Games\skarabrae\OpCommon\Operation;

use function Bga\Games\skarabrae\getPart;

/** Optional bottom effect of a settler card. Stores the effect expression as params. */
class Op_bottomeffect extends Operation {
    function canSkip() {
        return true;
    }

    function requireConfirmation() {
        return true; //always confirm
    }

    function getPrompt() {
        $cotag = $this->getCotagInfo();
        if ($cotag !== null) {
            [, , , $skaill] = $cotag;
            if ($skaill > 0) {
                return clienttranslate('Resolve bottom effect ${count} ${name} plus spend N ${skaill_trade}');
            }
            return clienttranslate('Resolve bottom effect ${count} ${name}');
        }
        return clienttranslate('Resolve bottom effect ${name}');
    }

    public function getSubTitle() {
        $cotag = $this->getCotagInfo();
        if ($cotag !== null) {
            [, , , $skaill] = $cotag;
            if ($skaill > 0) {
                return clienttranslate("You can spend Skaill to gain extra resources. Use Skip to decline the bottom effect");
            }
        }
        return clienttranslate("Use Skip to decline the bottom effect");
    }

    function getOpName() {
        return clienttranslate("Bottom Effect");
    }

    function getBottomEffect(): string {
        $r = $this->getParams();
        $this->game->systemAssert("bottomeffect has no effect expression", $r);
        return $r;
    }

    /**
     * If the bottom effect is a cotag operation, returns [tnum, op, settlerCount, skaill].
     * Returns null for non-cotag effects.
     */
    private function getCotagInfo(): ?array {
        $sub = $this->game->machine->instanciateOperation($this->getBottomEffect(), $this->getOwner());
        if (!($sub instanceof Op_cotag)) {
            return null;
        }
        $tnum = (int) $sub->getParam(0);
        $op = $sub->getParam(1, "");
        $owner = $this->getOwner();
        $count = $this->game->countTags($tnum, $owner);
        $skaill = 0;
        if ($tnum <= 4) {
            $skaill = $this->game->tokens->getTrackerValue($owner, "skaill");
        }
        return [$tnum, $op, $count, $skaill];
    }

    private function getResourceArgs(string $op, string $argname = "other_op"): array {
        $resSub = $this->game->machine->instanciateOperation($op, $this->getOwner());
        return [$argname => ["log" => $resSub->getButtonName(), "args" => $resSub->getExtraArgs()]];
    }

    function getPossibleMoves() {
        $cotag = $this->getCotagInfo();
        if ($cotag === null) {
            return ["confirm"];
        }

        [, , $count, $skaill] = $cotag;

        if ($count == 0 && $skaill == 0) {
            return []; // no valid targets -> auto-skip
        }

        if ($skaill == 0) {
            return ["confirm"];
        }

        $res = [];

        $res["resolve_base"] = [
            "name" => clienttranslate("None"),
            "q" => $count > 0 ? 0 : Material::MA_ERR_NOT_ENOUGH,
        ];

        $maxNum = 2;
        $maxExplicit = min($skaill, $maxNum);
        for ($k = 1; $k <= $maxExplicit; $k++) {
            // $total = $count + $k;
            $res["resolve_$k"] = [
                "name" => "$k",
                "q" => 0,
            ];
        }

        if ($skaill > $maxNum) {
            $res["resolve_n"] = [
                "name" => "{$maxNum}+",
                "q" => 0,
            ];
        }

        return $res;
    }

    function getExtraArgs() {
        $cotag = $this->getCotagInfo();
        if ($cotag !== null) {
            [$tnum, $op, $count, $skaill] = $cotag;
            $resArgs = $this->getResourceArgs($op);
            $skaillTrade = $this->getResourceArgs("n_skaill:$op");
            return [
                "name" => $resArgs["other_op"],
                "skaill_trade" => $skaillTrade["other_op"],
                "count" => $count,
                "count_skaill" => $skaill,
            ];
        }

        $sub = $this->game->machine->instanciateOperation($this->getBottomEffect(), $this->getOwner());
        return [
            "name" => ["log" => $sub->getButtonName(), "args" => $sub->getExtraArgs()],
        ];
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $cotag = $this->getCotagInfo();

        if ($cotag === null) {
            // Non-cotag: queue the entire effect expression
            $this->queue($this->getBottomEffect(), $owner, null, $this->getReason());
            return;
        }

        [$tnum, $op, $count, $skaill] = $cotag;
        $target = $this->getCheckedArg();
        $reason = $this->getReason();

        if ($count > 0) {
            $this->queue("$count($op)", $owner, null, $reason);
        }

        if ($target === "resolve_base" || $target === "confirm") {
            // settlers only, no skaill
        } elseif ($target === "resolve_n") {
            // generic range selector
            $this->queue("[0,{$skaill}](n_skaill:$op)", $owner, null, $reason);
        } elseif (str_starts_with($target, "resolve_")) {
            // resolve_1, resolve_2 - spend exact amount of skaill
            $k = (int) getPart($target, 1);
            $this->queue("$k(n_skaill:$op)", $owner, null, $reason);
        }
    }
}
