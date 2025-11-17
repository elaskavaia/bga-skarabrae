<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae;

class Material {
    private array $token_types;
    private bool $adjusted = false;
    public function __construct() {
        $this->token_types = [
            /* --- gen php begin op_material --- */
    "Op_shell" => [ 
        "class" => "Op_gain",
        "type" => "shell",
        "name" => clienttranslate("Gain Shell"),
],
    "Op_rabbit" => [ 
        "class" => "Op_gain",
        "type" => "rabbit",
        "name" => clienttranslate("Gain Rabbit"),
],
    "Op_barley" => [ 
        "class" => "Op_gain",
        "type" => "barley",
        "name" => clienttranslate("Gain Barley"),
],
    "Op_fish" => [ 
        "class" => "Op_gain",
        "type" => "fish",
        "name" => clienttranslate("Gain Fish"),
],
    "Op_seaweed" => [ 
        "class" => "Op_gain",
        "type" => "seaweed",
        "name" => clienttranslate("Gain Seaweed"),
],
    "Op_sheep" => [ 
        "class" => "Op_gain",
        "type" => "sheep",
        "name" => clienttranslate("Gain Sheep"),
],
    "Op_wool" => [ 
        "class" => "Op_gain",
        "type" => "wool",
        "name" => clienttranslate("Gain Wool"),
],
    "Op_deer" => [ 
        "class" => "Op_gain",
        "type" => "deer",
        "name" => clienttranslate("Gain Deer"),
],
    "Op_stone" => [ 
        "class" => "Op_gain",
        "type" => "stone",
        "name" => clienttranslate("Gain Stone"),
],
    "Op_cow" => [ 
        "class" => "Op_gain",
        "type" => "cow",
        "name" => clienttranslate("Gain Cow"),
],
    "Op_wood" => [ 
        "class" => "Op_gain",
        "type" => "wood",
        "name" => clienttranslate("Gain Wood"),
],
    "Op_skaill" => [ 
        "class" => "Op_gain",
        "type" => "skaill",
        "name" => clienttranslate("Gain Skaill"),
],
    "Op_hide" => [ 
        "class" => "Op_gain",
        "type" => "hide",
        "name" => clienttranslate("Gain Hide"),
],
    "Op_food" => [ 
        "class" => "Op_gain",
        "type" => "food",
        "name" => clienttranslate("Gain Food"),
],
    "Op_bone" => [ 
        "class" => "Op_gain",
        "type" => "bone",
        "name" => clienttranslate("Gain Bone"),
],
    "Op_midden" => [ 
        "class" => "Op_gain",
        "type" => "midden",
        "name" => clienttranslate("Gain Midden"),
],
    "Op_nop" => [ 
        "class" => "Operation_nop",
        "type" => "nop",
        "name" => clienttranslate("None"),
],
            /* --- gen php end op_material --- */
        ];
    }

    public function get() {
        return $this->token_types;
    }

    /**
     * This has to be called from "initTable" method of game which is when db is conected but action is not started yet
     */
    public function adjustMaterial(Game $game) {
        if ($this->adjusted) {
            return $this->token_types;
        }
        $this->adjusted = true;
        // ... do something reading number or palyer of game options with material
        return $this->token_types;
    }

    function getRulesFor($token_id, $field = "r", $default = "") {
        $tt = $this->token_types;
        $key = $token_id;
        while ($key) {
            $data = $tt[$key] ?? null;
            if ($data) {
                if ($field === "*") {
                    $data["_key"] = $key;
                    return $data;
                }
                return $data[$field] ?? $default;
            }
            $new_key = $this->getPartsPrefix($key, -1);
            if ($new_key == $key) {
                break;
            }
            $key = $new_key;
        }
        //$this->systemAssertTrue("bad token $token_id for rule $field", false);
        return $default;
    }

    /** Find stuff in material file */
    function find(string $field, ?string $value, bool $ignorecase = true) {
        foreach ($this->token_types as $key => $rules) {
            $cur = $rules[$field] ?? null;
            if ($cur == $value) {
                return $key;
            }
            if ($ignorecase && is_string($cur) && strcasecmp($cur, $value) == 0) {
                return $key;
            }
        }
        return null;
    }
    function findByName(string $value, bool $ignorecase = true) {
        return $this->find("name", $value, $ignorecase);
    }

    /**
     * Return $i parts of string (part is chunk separated by _
     * I.e.
     * getPartsPrefix("a_b_c",2)=="a_b"
     *
     * If $i is negative - it will means how much remove from tail, i.e
     * getPartsPrefix("a_b_c",-1)=="a_b"
     */
    static function getPartsPrefix($haystack, $i) {
        $parts = explode("_", $haystack);
        $len = count($parts);
        if ($i < 0) {
            $i = $len + $i;
        }
        if ($i <= 0) {
            return "";
        }
        for (; $i < $len; $i++) {
            unset($parts[$i]);
        }
        return implode("_", $parts);
    }
}
