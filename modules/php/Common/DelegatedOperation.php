<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae\Common;

use BgaSystemException;

/**
 * Delegate operation is only need to resolve operations with count, like 2m
 * In this case mnemonic of this one is "2m" and count is 1, and delegate is "m" with count of "2"
 * It is needed to be able to store the complex expression is db if not ready to act on it yet
 */
class DelegatedOperation extends CountableOperation {
    public Operation $delegate;
    public function __construct(private string $type, private string $owner, mixed $data = null, private int $id = 0) {
        parent::__construct($type, $owner, $data, $id);

        $this->delegate = $this->game->machine->instanciateOperation($this->getType(), $this->getOwner(), $this->getData());
        if ($this->getType() == $this->delegate->getType()) {
            throw new BgaSystemException("Cannot create delegate for $type");
        }
    }

    function expandOperation() {
        $stored = false;

        $sub = $this->delegate;
        if ($sub->isTrancient() && $this->isTrancient()) {
            $this->game->machine->put($this->getType(), $this->getOwner(), $this->getData(), 1);
            $stored = true;
        }

        return $stored;
    }

    function getType() {
        return $this->delegate->getType();
    }

    function getArgs() {
        return $this->delegate->getArgs();
    }

    function action_resolve(mixed $data) {
        $this->userArgs = $data;

        if ($this->getUserCount() > 0 || !$this->canSkip()) {
            $this->delegate->action_resolve($data);
        } else {
            $this->game->notifyWithName(
                "message",
                clienttranslate('${player_name} skips ${name}'),
                $this->getArgs()["args"],
                $this->getPlayerId()
            );
        }
        return;
    }

    function auto(): bool {
        return $this->delegate->auto();
    }

    function isVoid(): bool {
        return $this->delegate->isVoid();
    }

    function noValidTargets(): bool {
        return $this->delegate->noValidTargets();
    }

    function getNoValidTargetError(): string {
        return $this->delegate->getNoValidTargetError();
    }

    function canSkip() {
        return $this->delegate->canSkip();
    }

    function requireConfirmation() {
        return false; // this has to be send to server to expand before confirmation
    }

    function canResolveAutomatically() {
        return $this->delegate->canResolveAutomatically();
    }

    public function getDelegate() {
        return $this->delegate;
    }
}
