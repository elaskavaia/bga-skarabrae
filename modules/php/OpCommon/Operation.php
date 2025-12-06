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

namespace Bga\Games\skarabrae\OpCommon;

use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\States\GameDispatch;
use Bga\Games\skarabrae\States\PlayerTurn;
use Bga\Games\skarabrae\Common\OpExpression;
use Bga\Games\skarabrae\Common\OpExpressionRanged;
use BgaSystemException;
use BgaUserException;
use Exception;

use function Bga\Games\skarabrae\array_get;

abstract class Operation {
    const ARG_TARGET = "target";
    const ARG_TOKEN = "token";
    const TTYPE_AUTO = "auto";
    const TARGET_AUTO = "auto";
    const TARGET_CONFIRM = "confirm";
    protected Game $game;
    protected int $player_id = 0;
    private mixed $data = null;
    private $cachedArgs = null;
    protected $userArgs = null;

    protected $queueRank = 1;

    public function __construct(private string $type, private string $owner, mixed $data = null, private int $id = 0) {
        $this->game = Game::$instance;
        $this->player_id = $this->game->getPlayerIdByColor($owner);
        $this->withData($data);
        if (!$owner) {
            throw new BgaSystemException("Owner must be set");
        }
    }

    function getType() {
        return $this->type;
    }
    final function getOpId() {
        return "Op_" . $this->getType();
    }
    final function getOwner() {
        return $this->owner;
    }
    final function getData() {
        return $this->data;
    }
    function withId(int $id) {
        $this->id = $id;
        return $this;
    }
    function withData($data) {
        $xdata = self::decodeData($data);
        if ($this->data === null) {
            $this->data = $xdata;
        } else {
            $this->data = array_merge($this->data, $xdata);
        }
        return $this;
    }
    static function decodeData($data) {
        if (is_string($data)) {
            $data = json_decode($data, true, 20, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } elseif (is_numeric($data)) {
            throw new BgaSystemException("Unsupported data format number");
        }
        return $data;
    }
    final function getId() {
        return $this->id;
    }
    final function getPlayerId() {
        return $this->player_id;
    }
    function withDataField(string $field, mixed $value) {
        if ($this->data === null) {
            $this->data = [];
        }
        if ($value === null) {
            unset($this->data[$field]);
        } else {
            $this->data[$field] = $value;
        }
        return $this;
    }
    final function getDataField(string $field, mixed $def = null) {
        if ($this->data === null || !array_key_exists($field, $this->data)) {
            return $def;
        }
        return $this->data[$field];
    }

    function withCounts(OpExpression $expr) {
        if ($expr instanceof OpExpressionRanged) {
            $count = $expr->to;
            $mcount = $expr->from;
            if ($count != 1) {
                $this->withDataField("count", $count);
            }
            if ($mcount != 1) {
                $this->withDataField("mcount", $mcount);
            }
        }
        return $this;
    }

    function withParams(?string $params) {
        return $this->withDataField("params", $params);
    }

    function getParams() {
        return $this->getDataField("params", null);
    }

    function getParam(int $index = 0, string $default = "") {
        $params = $this->getParams();
        if (!$params) {
            return $default;
        }
        $pargs = explode(",", $params);
        return array_get($pargs, $index, $default);
    }

    final function isTrancient() {
        return $this->id <= 0;
    }

    function expandOperation() {
        if ($this->isTrancient()) {
            $this->game->machine->put($this->getType(), $this->getOwner(), $this->getData(), 1);
            return true;
        }
        return false;
    }

    function destroy() {
        $id = $this->getId();
        if ($id > 0) {
            $this->game->machine->hide($id);
        }
        return GameDispatch::class;
    }

    function queue($type, $owner = null, $data = null, $reason = null) {
        $this->game->systemAssert("empty op pushed", $type);
        if ($owner === null) {
            $owner = $this->getOwner();
        }
        if ($reason === null) {
            $reason = $this->getOpId();
        }

        if ($reason) {
            if ($data === null) {
                $data = [];
            }
            $data["reason"] = $reason;
        }
        $this->game->machine->insert($type, $owner, $data, $this->queueRank);
        //$this->game->debugConsole("queue $type");
    }

    protected function getCheckedArg() {
        $args = $this->userArgs;
        $key = Operation::ARG_TARGET;
        $possible_targets = $this->getArgs()[$key];
        $this->game->systemAssert("ERR:getCheckedArg:1", is_array($possible_targets));

        if (count($possible_targets) == 0) {
            return false; // XXX type match
        }
        $ttype = $this->getArgType();
        $target = $args[$key] ?? null;
        if ($target !== null) {
            if ($target === $possible_targets) {
                return $possible_targets;
            }
            if ($target === Operation::TTYPE_AUTO) {
                return $possible_targets[0] ?? [];
            }
            if (count($possible_targets) == 1) {
                return $possible_targets[0];
            }
            if (is_array($target)) {
                $multi = $target;
                $res = [];

                if ($ttype == "token_array") {
                    foreach ($multi as $target) {
                        $index = array_search($target, $possible_targets);
                        $this->game->systemAssert("ERR:getCheckedArg:2", $index !== false);
                        $res[] = $possible_targets[$index];
                    }
                    return $res;
                }
                if ($ttype == "token_count") {
                    foreach ($multi as $target => $count) {
                        $index = array_search($target, $possible_targets);
                        $this->game->systemAssert("Unauthorized argument $key", $index !== false);
                        $res[$target] = (int) $count;
                    }
                    return $res;
                }

                $this->game->systemAssert("Array is passed for $ttype, but it is not supported", false);
            } else {
                $index = array_search($target, $possible_targets);
                $this->game->systemAssert("ERR:getCheckedArg:4", $index !== false);
                return $possible_targets[$index];
            }
        } else {
            if (count($possible_targets) == 1) {
                return $possible_targets[0];
            }
        }

        $this->game->userAssert(clienttranslate("Operation is not allowed by the rules"));
        return null;
    }

    protected function getUncheckedArg($args, $key = Operation::ARG_TARGET, $def = null) {
        $this->userArgs = $args;
        $target = $args[$key] ?? $def;
        return $target;
    }

    /** Get state arguments if we go to player's state */
    function getArgs() {
        if ($this->cachedArgs !== null) {
            return $this->cachedArgs;
        }
        $this->cachedArgs = [];
        $res = &$this->cachedArgs;

        $res["id"] = $this->getId();
        $res["owner"] = $this->getOwner();
        $res["data"] = $this->getData();
        $res["type"] = $this->getType();
        $res["ttype"] = $this->getArgType();

        $movesInfo = $this->getPossibleMoves();
        $this->extractPossibleMoves($res, $movesInfo);

        $res["descriptionOnMyTurn"] = $this->getPrompt();
        $res["description"] = $this->getDescription();
        $res["subtitle"] = $this->getSubTitle();

        if ($this->canSkip()) {
            $res["info"]["skip"] = [
                "name" => $this->getSkipName(),
                "o" => 1000,
                "sec" => true,
                "q" => 0,
                "color" => "alert",
            ];
        }

        $res = array_merge($res, $this->getExtraArgs());

        // cleanup nulls to optimize of data transfer
        foreach ($res as $key => $value) {
            if ($key == Operation::ARG_TARGET) {
                continue;
            }
            if ($value === null || $value === false || $value === "") {
                unset($res[$key]);
            }
        }

        return $res;
    }

    function getSkipName() {
        return clienttranslate("Skip");
    }

    function getButtonName() {
        return $this->getOpName();
    }
    function getOpName() {
        return $this->game->getTokenName($this->getOpId(), $this->getType());
    }
    private function extractPossibleMoves(array &$res, array $details) {
        $targets = [];
        $error = "";
        foreach ($details as $target => $info) {
            if ($target == "err") {
                // top level error
                $error = $info;
                unset($details[$target]);
                continue;
            }
            if ($target == "q") {
                // top level error
                $error = $this->game->getRulesFor("err_$info", "name", "code $info");
                unset($details[$target]);
                continue;
            }
            if (is_array($info)) {
                $q = $info["q"] ?? 0;
                if ($q == 0) {
                    $info["q"] = 0;
                    if ($info["sec"] ?? false) {
                        // secondary targets are not listed in main target list
                        continue;
                    }
                    $targets[] = $target;
                }
            } elseif (is_numeric($info) && is_string($target)) {
                // error code
                $details[$target] = ["q" => $info];
                if ($info == 0) {
                    $targets[] = $target;
                }
            } elseif (is_string($info) && is_numeric($target)) {
                // array value directly
                $targets[] = $info;
                unset($details[$target]);
                $details[$info] = ["q" => 0];
            } else {
                $info = json_encode($info);
                $target = json_encode($target);
                throw new Exception("invalid value $info for $target key");
            }
        }

        if (count($targets) == 0 && !$error) {
            $error = $this->extractError($details);
        }

        $res[Operation::ARG_TARGET] = $targets;
        $res["info"] = $details;
        $res["err"] = $error ?? null;
    }

    function getError(): mixed {
        $arg = $this->getArgs();
        return $arg["err"] ?: "";
    }

    function noValidTargets(): bool {
        $args = $this->getArgs();
        return count($args[Operation::ARG_TARGET]) == 0;
    }

    function isOneChoice(): bool {
        $args = $this->getArgs();
        return count($args[Operation::ARG_TARGET]) == 1;
    }

    function extractError(?array $possibleMovesInfo = null): string {
        if (!$possibleMovesInfo || count($possibleMovesInfo) == 0) {
            return $this->getNoValidTargetError();
        }
        foreach ($possibleMovesInfo as $target => $info) {
            $err = $info["err"] ?? "";
            if ($err) {
                return $err;
            }
            $err = $info["q"] ?? 0;
            if ($err) {
                return $this->game->getRulesFor("err_$err", "name", "?$err");
            }
        }

        return $this->getNoValidTargetError();
    }

    function getNoValidTargetError(): string {
        return clienttranslate("No valid targets");
    }

    function notifyMessage($message = "", $args = []) {
        $this->game->notify->all("message", $message, $args);
    }
    // overridable stuff
    function getArgType() {
        return "auto";
    }

    /** If operation require confirmation it will be sent to user and not auto-resolved */
    function requireConfirmation() {
        return false;
    }

    function getExtraArgs() {
        return [];
    }
    function getPrompt() {
        return $this->getDescription() ?: $this->getType() . "?";
    }
    function getDescription() {
        return "";
    }

    function getSubTitle() {
        return "";
    }
    function getPossibleMoves() {
        return ["confirm"];
    }

    function getReason() {
        return $this->getDataField("reason", "");
    }

    /** Operation is void is it has no valid target, however skippable operation is never void */
    function isVoid(): bool {
        if ($this->canSkip()) {
            return false;
        }

        if ($this->noValidTargets()) {
            return true;
        }
        return false;
    }

    /** Called on game state to see if we can do this one automatically and if not change players and return state we want to be in */
    function onEnteringGameState() {
        $isAuto = $this->auto();

        if (!$isAuto) {
            // switch to player state
            return $this->getNextState();
        }
        $this->destroy();
        return;
    }

    function getNextState() {
        return PlayerTurn::class;
    }

    /** Automatic action perform in game state, if cannot be done automatically turn one of player's states */
    function auto(): bool {
        if (!$this->canResolveAutomatically()) {
            return false;
        }
        $this->checkVoid();
        if ($this->noValidTargets()) {
            if ($this->canSkip()) {
                $this->action_skip();
                return true;
            }
        }
        $this->action_resolve([]);
        return true;
    }

    function checkVoid() {
        if ($this->isVoid()) {
            $this->game->userAssert($this->getError());
        }
    }

    function canResolveAutomatically() {
        if ($this->requireConfirmation()) {
            return false;
        }
        if ($this->noValidTargets()) {
            if ($this->canSkip()) {
                return true;
            }
            return false;
        }
        if ($this->canSkip()) {
            return false;
        }
        // if ($this->getArgType() == Operation::TTYPE_AUTO) {
        //     return true;
        // }
        if ($this->isOneChoice()) {
            return true;
        }
        return false;
    }

    /** Call onEnteringPlauerState if we go to player's state*/
    function onEnteringPlayerState() {
        return;
    }

    function action_resolve(mixed $data) {
        if (!is_array($data)) {
            throw new BgaSystemException("data encoding issues");
        }
        $this->userArgs = $data;
        return $this->resolve($data) ?: $this->destroy();
    }

    /** User does the action. If this return false or void or 0 we will end the operation, and return the state */
    function resolve() {
        return;
    }

    function action_skip() {
        return $this->skip() ?: $this->destroy();
    }

    /** Called on operation to see if we can skip this one */
    function canSkip() {
        return false;
    }
    /** Called on operation to skip this one */
    function skip() {
        if (!$this->canSkip()) {
            throw new BgaUserException(clienttranslate("Cannot skip this action"));
        }
    }

    function undo() {
        $this->game->undoRestorePoint();
    }

    function action_whatever() {
        return $this->whatever() ?: $this->destroy();
    }

    function whatever() {
        $args = $this->getArgs();
        $targets = $args[Operation::ARG_TARGET];
        $num = count($targets);
        if ($num == 0) {
            $state = $this->skip();
        } else {
            $state = $this->resolve([Operation::ARG_TARGET => $targets[bga_rand(0, $num - 1)]]);
        }
        return $state;
    }
}
