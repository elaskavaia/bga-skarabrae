<?php

declare(strict_types=1);

namespace Bga\Games\skarabrae;

class Material {
    const MA_OK = 0;
    const MA_ERR_COST = 1;
    const MA_ERR_PREREQ = 2;
    const MA_ERR_OCCUPIED = 3;
    const MA_ERR_MAX = 4;
    const MA_ERR_NOT_ENOUGH = 5;
    const MA_ERR_NOT_APPLICABLE = 6;

    private array $token_types;
    private bool $adjusted = false;
    public function __construct() {
        $this->token_types = [
            // #error codes - MANUAL ENTRY
            "err_0" => [
                //
                "code" => Material::MA_OK,
                "type" => "err",
                "name" => clienttranslate("Ok"),
            ],
            "err_1" => [
                //
                "code" => Material::MA_ERR_COST,
                "type" => "err",
                "name" => clienttranslate("Insufficient Resources"),
            ],

            "err_2" => [
                //
                "code" => Material::MA_ERR_PREREQ,
                "type" => "err",
                "name" => clienttranslate("Prerequisites are not fullfilled"),
            ],
            "err_3" => [
                //
                "code" => Material::MA_ERR_OCCUPIED,
                "type" => "err",
                "name" => clienttranslate("Location is occupied"),
            ],
            "err_4" => [
                //
                "code" => Material::MA_ERR_MAX,
                "type" => "err",
                "name" => clienttranslate("Maximum capacity is reached"),
            ],
            "err_5" => [
                //
                "code" => Material::MA_ERR_NOT_ENOUGH,
                "type" => "err",
                "name" => clienttranslate("Not enough"),
            ],
            "err_6" => [
                //
                "code" => Material::MA_ERR_NOT_APPLICABLE,
                "type" => "err",
                "name" => clienttranslate("Not applicable"),
            ],

            /* --- gen php begin loc_material --- */
            "deck_village" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Village Deck"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "discard_village" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Village Discard"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "limbo" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Limbo"),
                "scope" => "global",
                "counter" => "hidden",
                "content" => "hidden",
            ],
            "tableau" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Player Area"),
                "location" => "players_panels",
                "scope" => "player",
                "counter" => "hidden",
                "content" => "public",
            ],
            /* --- gen php end loc_material --- */
            /* --- gen php begin op_material --- */
    "Op_furnish" => [ 
        "type" => "furnish",
        "name" => clienttranslate("Furnish"),
],
    "Op_furnishPay" => [ 
        "type" => "furnishPay",
        "name" => clienttranslate("Furnish"),
],
    "Op_cook" => [ 
        "type" => "cook",
        "name" => clienttranslate("Cook"),
],
    "Op_craft" => [ 
        "type" => "craft",
        "name" => clienttranslate("Craft"),
],
    "Op_clean" => [ 
        "type" => "clean",
        "name" => clienttranslate("Clean"),
],
    "Op_trade" => [ 
        "type" => "trade",
        "name" => clienttranslate("Trade"),
],
    "Op_tradePay" => [ 
        "type" => "tradePay",
        "name" => clienttranslate("Trade"),
],
    "Op_build" => [ 
        "type" => "build",
        "name" => clienttranslate("Build"),
],
    "Op_endOfRound" => [ 
        "type" => "endOfRound",
        "name" => clienttranslate("End of Round"),
],
    "Op_cotag" => [ 
        "type" => "cotag",
        "name" => clienttranslate("Count of Tags"),
],
    "Op_roof" => [ 
        "type" => "roof",
        "name" => clienttranslate("Gain Roof Card"),
],
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
    "Op_boar" => [ 
        "class" => "Op_gain",
        "type" => "boar",
        "name" => clienttranslate("Gain Boar"),
],
    "Op_n_shell" => [ 
        "class" => "Op_pay",
        "type" => "n_shell",
        "name" => clienttranslate("Pay Shell"),
],
    "Op_n_rabbit" => [ 
        "class" => "Op_pay",
        "type" => "n_rabbit",
        "name" => clienttranslate("Pay Rabbit"),
],
    "Op_n_barley" => [ 
        "class" => "Op_pay",
        "type" => "n_barley",
        "name" => clienttranslate("Pay Barley"),
],
    "Op_n_fish" => [ 
        "class" => "Op_pay",
        "type" => "n_fish",
        "name" => clienttranslate("Pay Fish"),
],
    "Op_n_seaweed" => [ 
        "class" => "Op_pay",
        "type" => "n_seaweed",
        "name" => clienttranslate("Pay Seaweed"),
],
    "Op_n_sheep" => [ 
        "class" => "Op_pay",
        "type" => "n_sheep",
        "name" => clienttranslate("Pay Sheep"),
],
    "Op_n_wool" => [ 
        "class" => "Op_pay",
        "type" => "n_wool",
        "name" => clienttranslate("Pay Wool"),
],
    "Op_n_deer" => [ 
        "class" => "Op_pay",
        "type" => "n_deer",
        "name" => clienttranslate("Pay Deer"),
],
    "Op_n_stone" => [ 
        "class" => "Op_pay",
        "type" => "n_stone",
        "name" => clienttranslate("Pay Stone"),
],
    "Op_n_cow" => [ 
        "class" => "Op_pay",
        "type" => "n_cow",
        "name" => clienttranslate("Pay Cow"),
],
    "Op_n_wood" => [ 
        "class" => "Op_pay",
        "type" => "n_wood",
        "name" => clienttranslate("Pay Wood"),
],
    "Op_n_skaill" => [ 
        "class" => "Op_pay",
        "type" => "n_skaill",
        "name" => clienttranslate("Pay Skaill"),
],
    "Op_n_hide" => [ 
        "class" => "Op_pay",
        "type" => "n_hide",
        "name" => clienttranslate("Pay Hide"),
],
    "Op_n_food" => [ 
        "class" => "Op_pay",
        "type" => "n_food",
        "name" => clienttranslate("Pay Food"),
],
    "Op_n_bone" => [ 
        "class" => "Op_pay",
        "type" => "n_bone",
        "name" => clienttranslate("Pay Bone"),
],
    "Op_n_boar" => [ 
        "class" => "Op_pay",
        "type" => "n_boar",
        "name" => clienttranslate("Pay Boar"),
],
    "Op_nop" => [ 
        "type" => "nop",
        "name" => clienttranslate("None"),
],
    "Op_n_midden" => [ 
        "type" => "n_midden",
        "name" => clienttranslate("Clean Midden"),
],
    "Op_or" => [ 
        "type" => "or",
        "name" => clienttranslate("Choice"),
],
    "Op_unique" => [ 
        "type" => "unique",
        "name" => clienttranslate("Unique Choice"),
],
    "Op_order" => [ 
        "type" => "order",
        "name" => clienttranslate("Choose Order"),
],
    "Op_seq" => [ 
        "type" => "seq",
        "name" => clienttranslate("Execution"),
],
    "Op_payAny" => [ 
        "type" => "payAny",
        "name" => clienttranslate("Pay Any Resource"),
],
    "Op_act" => [ 
        "type" => "act",
        "name" => clienttranslate("Worker Action"),
],
    "Op_gain" => [ 
        "type" => "gain",
        "name" => clienttranslate("Gain"),
],
    "Op_pay" => [ 
        "type" => "pay",
        "name" => clienttranslate("Pay"),
],
    "Op_paygain" => [ 
        "type" => "paygain",
        "name" => clienttranslate("Trade"),
],
    "Op_recall" => [ 
        "type" => "recall",
        "name" => clienttranslate("Return Workers"),
],
    "Op_pass" => [ 
        "type" => "pass",
        "name" => clienttranslate("Delay"),
],
    "Op_round" => [ 
        "type" => "round",
        "name" => clienttranslate("Round"),
],
    "Op_turn" => [ 
        "type" => "turn",
        "name" => clienttranslate("Turn"),
],
    "Op_turnall" => [ 
        "type" => "turnall",
        "name" => clienttranslate("Turn"),
],
    "Op_village" => [ 
        "type" => "village",
        "name" => clienttranslate("Gain Village Card"),
],
    "Op_tradeGood" => [ 
        "type" => "tradeGood",
        "name" => clienttranslate("Trade Market Resource"),
],
    "Op_tradeInc" => [ 
        "type" => "tradeInc",
        "name" => clienttranslate("Move up on Trade track"),
],
            /* --- gen php end op_material --- */
            /* --- gen php begin token_material --- */
// # create is one of the numbers
// # 0 - do not create token
// # 1 - the token with id $id will be created, count must be set to 1 if used
// # 2 - the token with id "${id}_{INDEX}" will be created, using count starting from 1
// # 3 - the token with id "${id}_{COLOR}_{INDEX}" will be created, using count, per player
// # 4 - the token with id "${id}_{COLOR}" for each player will be created, count must be 1
// # 5 - the token with id "${id}_{INDEX}_{COLOR}" for each player will be created
// # 6 - custom placeholders
// #4 Large Workers (1 per player colour)
    "worker_1" => [ 
        "name" => clienttranslate("Large Worker"),
        "count" => 1,
        "type" => "worker wooden large",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
// #15 Small Workers (3 Black + 3 per player colour)
    "worker_2" => [ 
        "name" => clienttranslate("Small Worker"),
        "count" => 1,
        "type" => "worker wooden small",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "worker_3" => [ 
        "name" => clienttranslate("Small Worker"),
        "count" => 1,
        "type" => "worker wooden small",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "worker_4" => [ 
        "name" => clienttranslate("Small Worker"),
        "count" => 1,
        "type" => "worker wooden small",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "worker_{INDEX}_000000" => [ 
        "name" => clienttranslate("Small Black Worker"),
        "count" => 3,
        "type" => "worker wooden small color_000000",
        "create" => 6,
        "location" => "supply",
        'start'=>2,
],
// #4 Turn Markers (1 per player colour)
    "turnmarker" => [ 
        "name" => clienttranslate("Turn Marker"),
        "count" => 1,
        "type" => "wooden",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
// #4 Furnish Markers
// #4 Trade Markers
    "tracker_furnish" => [ 
        "name" => clienttranslate("Furnish Marker"),
        "count" => 1,
        "type" => "wooden tracker furnish",
        "create" => 4,
        "location" => "slot_furnish_0_{COLOR}",
],
    "tracker_trade" => [ 
        "name" => clienttranslate("Trade Marker"),
        "count" => 1,
        "type" => "wooden tracker trade",
        "create" => 4,
        "location" => "slot_trade_0_{COLOR}",
],
    "tracker_hearth" => [ 
        "name" => clienttranslate("Hearth Limit"),
        "count" => 1,
        "type" => "wooden tracker hearth",
        "create" => 4,
        "location" => "miniboard_{COLOR}",
],
// #3 Boar
// #cards
// #80 Village Cards 40 Roof Cards 10 Spindle Whorl Cards 8 Focus Cards 10 Task Cards
    "card_setl" => [ 
        "name" => clienttranslate("Village Settler Card"),
        "count" => 60,
        "type" => "card village setl",
        "create" => 2,
        "location" => "deck_village",
        'r'=>'fish',
],
    "card_roof" => [ 
        "name" => clienttranslate("Village Roof Card"),
        "count" => 8,
        "type" => "card village roof",
        "create" => 2,
        "location" => "deck_village",
        'vp'=>2,
],
    "card_ball" => [ 
        "name" => clienttranslate("Village Stone Ball Card"),
        "count" => 8,
        "type" => "card village ball",
        "create" => 2,
        "location" => "deck_village",
        'vp'=>2,
],
    "card_util" => [ 
        "name" => clienttranslate("Village Utencil Card"),
        "count" => 4,
        "type" => "card village util",
        "create" => 2,
        "location" => "deck_village",
        'vp'=>1,
],
    "card_roofi" => [ 
        "name" => clienttranslate("Roof Card"),
        "count" => 40,
        "type" => "card roofi",
        "create" => 2,
        "location" => "deck_roof",
        'vp'=>1,
],
    "card_spin" => [ 
        "name" => clienttranslate("Spindle Card"),
        "count" => 10,
        "type" => "card spin",
        "create" => 2,
        "location" => "deck_spin",
        'vp'=>1,
],
            /* --- gen php end token_material --- */
            /* --- gen php begin action_material --- */
// #general actions
    "action_main_1" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 1,
        "name" => clienttranslate("Furnish"),
        "r" => "furnishPay:furnish",
        "rb" => "furnishPay:(furnish,(barley/skaill))",
        "craft" => "n_stone,n_wood",
        "tooltip" => clienttranslate("Spend Wool and Hides to move the Furnish Marker 1 space to the right on the Furnish Track. Cost: 1, 2, or 3 portions (each portion = 2 Wool or 1 Hide). Once flipped: Produces either 1 Barley or 1 Skaill Knife."),
],
    "action_main_2" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 2,
        "name" => clienttranslate("Cook"),
        "r" => "cook",
        "rb" => "cook",
        "craft" => "n_stone,n_seaweed",
        "tooltip" => clienttranslate("Cook (spend) specific Resources to gain Food, Bones, Hides, and Wool. Hearth limit: Starts at 4 (can be increased). Once flipped: Hearth limit increases by 2."),
],
    "action_main_3" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 3,
        "name" => clienttranslate("Craft"),
        "r" => "craft",
        "rb" => "craft",
        "craft" => "n_stone,n_wool",
        "tooltip" => clienttranslate("Spend 2 Resources shown on the tan banner of any 1 Action Tile to turn it over. Once flipped: Players may take a Cook Action at the end of each Round."),
],
    "action_main_4" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 4,
        "name" => clienttranslate("Clean"),
        "r" => "clean",
        "rb" => "clean",
        "craft" => "n_stone,n_shell",
        "tooltip" => clienttranslate("Spend 2 or more different Resources (Hides, Barley, Seaweed, Wood) to clear Midden and gain Roof Cards. Once flipped: Clean becomes more effective, and Food can be spent as 1 of the Resource types."),
],
    "action_main_5" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 5,
        "name" => clienttranslate("Trade"),
        "r" => "tradePay:trade",
        "rb" => "tradePay:trade",
        "craft" => "n_stone,n_hide",
        "tooltip" => clienttranslate("Spend a certain amount of a single Resource type (2-6) to advance the Trade Marker 1 space to the right on the Trade Track. After advancing, players can purchase other Resources for 1 Skaill Knife. Once flipped: Players can make purchases using any 1 Resource instead of a Skaill Knife."),
],
    "action_main_6" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 6,
        "name" => clienttranslate("Gather Shore"),
        "r" => "shell/seaweed",
        "rb" => "(shell,seaweed)/fish",
        "craft" => "n_bone,n_wood",
        "tooltip" => clienttranslate("Gain Resources by placing Workers on Gather Action Tiles. Shore area provides either 1 Shell or Seaweed. Once flipped: Shore provides 1 Shell and 1 Seaweed or 1 Fish."),
],
    "action_main_7" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 7,
        "name" => clienttranslate("Gather Hills"),
        "r" => "wool/stone",
        "rb" => "(wool,stone)/rabbit",
        "craft" => "n_bone,n_seaweed",
        "tooltip" => clienttranslate("Gain Resources by placing Workers on Gather Action Tiles. Hills area provides Wool or Stone resources. Once flipped: Options improve and gives 1 more Environment icon when harvesting."),
],
    "action_main_8" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 8,
        "name" => clienttranslate("Gather Thickets"),
        "r" => "wood",
        "rb" => "2wood/(n_wood:deer)",
        "craft" => "n_bone,n_wool",
        "tooltip" => clienttranslate("Gain Resources by placing Workers on Gather Action Tiles. Thickets area provides Wood resources. Once flipped: Options improve and gives 1 more Environment icon when harvesting."),
],
    "action_main_9" => [ 
        "create" => 4,
        "type" => "action main",
        "location" => "tableau_{COLOR}",
        "num" => 9,
        "name" => clienttranslate("Gather Fields"),
        "r" => "barley",
        "rb" => "2barley/(2n_barley:cow)",
        "craft" => "n_bone,n_shell",
        "tooltip" => clienttranslate("Gain Resources by placing Workers on Gather Action Tiles. Fields area provides Barley resources. Once flipped: Options improve and gives 1 more Environment icon when harvesting."),
],
    "action_special_8" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 8,
        "name" => clienttranslate("Build"),
        "r" => "2(n_bone/n_food):build",
        "tooltip" => clienttranslate("Take a Furnish or Trade Action, spending 2 fewer Resources. This is resolved just as if a Worker had been placed on that Action Tile. Once flipped, a player can instead spend 3 fewer Resources for the chosen action."),
],
    "action_special_6" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 6,
        "name" => clienttranslate("Explore"),
        "r" => "2(n_bone/n_food):nop",
        "tooltip" => clienttranslate("Draw 2 Village Cards from the top of the Draw Pile. Select 1 to keep and discard the other. If this was a Settler Card, do not resolve the top harvest or bottom effect. If this was a Stone Ball or Utensils Card, gain the immediate Resources as normal. Once flipped, a player can instead draw 3 Village Cards, discarding 2. Also, if they select a Settler Card, they may resolve the bottom effect after placing it."),
],
    "action_special_7" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 7,
        "name" => clienttranslate("Hunt Boar"),
        "r" => "2(n_bone/n_food):boar",
        "tooltip" => clienttranslate("Gain 1 Boar. This Action Tile also shows the weight of Boar, and Resources gained when cooked. Players can never have more than 3 Boar at the same time. Once flipped, this action also provides 1 Skaill Knife, and Boars produce 1 more Hide when cooked."),
],
    "action_special_2" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 2,
        "name" => clienttranslate("Innovate"),
        "r" => "2(n_bone/n_food):craft",
        "tooltip" => clienttranslate("Take a Craft Action, spending 2 fewer Resources (ignoring the usual costs). Once flipped, this action also allows a player to spend 1 Food to immediately take the action that was just turned over, just as if they had placed a Worker there."),
],
    "action_special_5" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 5,
        "name" => clienttranslate("Muster"),
        "r" => "2(n_bone/n_food):nop",
        "tooltip" => clienttranslate("Resolve either the top harvest or bottom effect of 1 Environment. This follows all the same rules as when placing a new Settler Card. The player with the Muster Action Tile also has an increase of 1 on their Hearth limit. Once flipped, this action resolves both the top harvest and bottom effect, rather than the choice of either. As with placing new Settlers, the bottom effect is still optional."),
],
    "action_special_3" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 3,
        "name" => clienttranslate("Recruit"),
        "r" => "2(n_bone/n_food):nop",
        "tooltip" => clienttranslate("Immediately gain 1 Black Worker. A player can never have more than 3 Black Workers at the same time. Black Workers must be returned at the Round's end, ready to be recruited again in the next Round. Black Workers act just like regular Small Workers. Once flipped, Action Tiles that have been turned over may now have up to 2 Small Workers placed on them per turn (and still 1 Large Worker as well)."),
],
    "action_special_1" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 1,
        "name" => clienttranslate("Spin Wool"),
        "r" => "2(n_bone/n_food):nop",
        "tooltip" => clienttranslate("Gain 1 Spindle Whorl Card. These should be taken from those stored nearby the Player Board during Setup. Spindle Whorl Cards should be placed below the Player Board, much like Roofs, Stone Balls, and Utensils. After doing so, the player immediately gains 1 Wool per Spindle Whorl they have, including from the Card they just placed. Spindle Whorl Cards are worth 1VP at the game's end. Once flipped, after resolving the Spindle Whorl, a player may optionally spend 3 Wool to gain 1 Roof Card. This is resolved in the same way as gaining Roof Cards from the Clean Action Tile."),
],
    "action_special_4" => [ 
        "create" => 1,
        "type" => "action special",
        "location" => "deck_action",
        "craft" => "n_bone,n_hide",
        "rb" => "nop",
        "num" => 4,
        "name" => clienttranslate("Tend Land"),
        "r" => "2(n_bone/n_food):nop",
        "tooltip" => clienttranslate("Resolve up to 3 different Gather Action Tiles, just as if Workers had been placed there. Alternatively this player may forgo any of these Gather actions to instead clear 1 Midden. As with the Clean action, they may either discard 1 Midden from their Storage Area, or move their Slider 1 column to the left. For example, a player might decide to Gather from the Hills and Fields, and clear 1 Midden, or simply just clear 3 Midden. Once flipped, this action allows players to do this up to 4 times, instead of 3."),
],
// #cooking Options
    "recipe_1" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 1,
        "rb" => "nop",
        "num" => 1,
        "name" => clienttranslate("Shell Chowder"),
        "r" => "n_shell:food",
],
    "recipe_2" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 1,
        "rb" => "nop",
        "num" => 2,
        "name" => clienttranslate("Barley Mow"),
        "r" => "n_barley:food",
],
    "recipe_3" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 2,
        "rb" => "nop",
        "num" => 3,
        "name" => clienttranslate("Fish Soup"),
        "r" => "n_fish:food,bone",
],
    "recipe_4" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 2,
        "rb" => "nop",
        "num" => 4,
        "name" => clienttranslate("Rabbit Stew"),
        "r" => "n_rabbit:food,hide",
],
    "recipe_5" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 3,
        "rb" => "nop",
        "num" => 5,
        "name" => clienttranslate("Sheep Kebobs"),
        "r" => "n_sheep:food,hide,wool,bone",
],
    "recipe_6" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 3,
        "rb" => "nop",
        "num" => 6,
        "name" => clienttranslate("Deer Roast"),
        "r" => "n_deer:food,hide,2bone",
],
    "recipe_7" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 4,
        "rb" => "nop",
        "num" => 7,
        "name" => clienttranslate("Beef Steak"),
        "r" => "n_cow:2food,hide,2bone",
],
    "recipe_8" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => 3,
        "rb" => "nop",
        "num" => 8,
        "name" => clienttranslate("Boar Bites"),
        "r" => "n_boar:2food,hide,bone",
        "tooltip" => clienttranslate("Cook 1 Boar to gain 2 Food, 1 Bone, and 1 Hide."),
],
    "slot_trade_0" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "nop",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 0"),
        "num" => 0,
        "r" => 0,
],
    "slot_trade_1" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "cow",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 1"),
        "num" => 1,
        "r" => 1,
],
    "slot_trade_2" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "deer",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 2"),
        "num" => 2,
        "r" => 3,
],
    "slot_trade_3" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "sheep",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 3"),
        "num" => 3,
        "r" => 5,
],
    "slot_trade_4" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "rabbit",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 4"),
        "num" => 4,
        "r" => 8,
],
    "slot_trade_5" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "fish",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 5"),
        "num" => 5,
        "r" => 11,
],
    "slot_trade_6" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "2shell",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 6"),
        "num" => 6,
        "r" => 15,
],
    "slot_trade_7" => [ 
        "create" => 0,
        "type" => "recipe",
        "location" => "limbo",
        "craft" => "2barley",
        "rb" => "nop",
        "name" => clienttranslate("Trade level 7"),
        "num" => 7,
        "r" => 20,
],
            /* --- gen php end action_material --- */
            /* --- gen php begin tracker_material --- */
            // #player counters
            "tracker_shell" => [
                "type" => "tracker wooden shell",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "shell",
                "name" => clienttranslate("Shell"),
            ],
            "tracker_rabbit" => [
                "type" => "tracker wooden rabbit",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "rabbit",
                "name" => clienttranslate("Rabbit"),
            ],
            "tracker_barley" => [
                "type" => "tracker wooden barley",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "barley",
                "name" => clienttranslate("Barley"),
            ],
            "tracker_fish" => [
                "type" => "tracker wooden fish",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "fish",
                "name" => clienttranslate("Fish"),
            ],
            "tracker_seaweed" => [
                "type" => "tracker wooden seaweed",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "seaweed",
                "name" => clienttranslate("Seaweed"),
            ],
            "tracker_sheep" => [
                "type" => "tracker wooden sheep",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "sheep",
                "name" => clienttranslate("Sheep"),
            ],
            "tracker_wool" => [
                "type" => "tracker wooden wool",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "wool",
                "name" => clienttranslate("Wool"),
            ],
            "tracker_deer" => [
                "type" => "tracker wooden deer",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "deer",
                "name" => clienttranslate("Deer"),
            ],
            "tracker_stone" => [
                "type" => "tracker wooden stone",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "stone",
                "name" => clienttranslate("Stone"),
            ],
            "tracker_cow" => [
                "type" => "tracker wooden cow",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "cow",
                "name" => clienttranslate("Cow"),
            ],
            "tracker_wood" => [
                "type" => "tracker wooden wood",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "wood",
                "name" => clienttranslate("Wood"),
            ],
            "tracker_skaill" => [
                "type" => "tracker wooden skaill",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "skaill",
                "name" => clienttranslate("Skaill"),
            ],
            "tracker_hide" => [
                "type" => "tracker wooden hide",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "hide",
                "name" => clienttranslate("Hide"),
            ],
            "tracker_food" => [
                "type" => "tracker wooden food",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "food",
                "name" => clienttranslate("Food"),
            ],
            "tracker_bone" => [
                "type" => "tracker wooden bone",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "bone",
                "name" => clienttranslate("Bone"),
            ],
            "tracker_midden" => [
                "type" => "tracker wooden midden",
                "create" => 4,
                "location" => "miniboard_{COLOR}",
                "state" => 0,
                "mtype" => "midden",
                "name" => clienttranslate("Midden"),
            ],
            /* --- gen php end tracker_material --- */
        ];
    }

    public function get(): array {
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

    static function getAllNonPoopResources(): array {
        $ress = [
            "shell",
            "rabbit",
            "barley",
            "fish",
            "seaweed",
            "sheep",
            "wool",
            "deer",
            "stone",
            "cow",
            "wood",
            "skaill",
            "hide",
            "food",
            "bone",
            "boar",
        ];
        return $ress;
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
