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
use Bga\Games\skarabrae\Common\ComplexOperation;
class Op_order extends ComplexOperation {
    function resolve(mixed $data = []) {
        $target = $this->getCheckedArg($data);
        foreach ($this->delegates as $arg) {
            if ($arg->getId() == $target) {
                // XXX
                $this->game->machine->push($arg->getType(), $arg->getOwner(), $arg->getData());
                $this->notifyMessage(clienttranslate('${player_name} selected ${opname}'), ["opname" => $arg->getOpName()]);
                $arg->destroy();
            }
        }

        return;
    }

    function getPrompt() {
        return clienttranslate("Choose first to resolve");
    }
    function getDescription() {
        return clienttranslate('${actplayer} chooses one of the options');
    }
}
