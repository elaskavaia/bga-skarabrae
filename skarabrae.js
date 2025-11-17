var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GalacticCruise implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */
// @ts-ignore
GameGui = /** @class */ (function () {
    function GameGui() { }
    return GameGui;
})();
/** Class that extends default bga core game class with more functionality
 */
var GameBasics = /** @class */ (function (_super) {
    __extends(GameBasics, _super);
    function GameBasics() {
        var _this = _super.call(this) || this;
        console.log("game constructor");
        _this.curstate = null;
        _this.pendingUpdate = false;
        _this.currentPlayerWasActive = false;
        return _this;
    }
    // state hooks
    GameBasics.prototype.setup = function (gamedatas) {
        console.log("Starting game setup", gamedatas);
    };
    GameBasics.prototype.onEnteringState = function (stateName, eargs) {
        console.log("onEnteringState", stateName, eargs, this.debugStateInfo());
        this.curstate = stateName;
        // Call appropriate method
        var args = eargs === null || eargs === void 0 ? void 0 : eargs.args; // this method has extra wrapper for args for some reason
        this.callfn("onEnteringState_before", args);
        var methodName = "onEnteringState_" + stateName;
        this.callfn(methodName, args);
        if (this.pendingUpdate) {
            this.onUpdateActionButtons(stateName, args);
            this.pendingUpdate = false;
        }
    };
    GameBasics.prototype.onLeavingState = function (stateName) {
        console.log("onLeavingState", stateName, this.debugStateInfo());
        this.currentPlayerWasActive = false;
        var methodName = "onLeavingState_" + stateName;
        this.callfn(methodName, {});
    };
    GameBasics.prototype.onUpdateActionButtons = function (stateName, args) {
        if (this.curstate != stateName) {
            // delay firing this until onEnteringState is called so they always called in same order
            this.pendingUpdate = true;
            //console.log('   DELAYED onUpdateActionButtons');
            return;
        }
        this.pendingUpdate = false;
        if (gameui.isCurrentPlayerActive() && this.currentPlayerWasActive == false) {
            console.log("onUpdateActionButtons: " + stateName, args, this.debugStateInfo());
            this.currentPlayerWasActive = true;
            // Call appropriate method
            var privates = args._private;
            var nargs = args;
            if (privates) {
                delete nargs._private;
                nargs = __assign(__assign({}, nargs), privates);
            }
            this.callfn("onUpdateActionButtons_" + stateName, nargs);
        }
        else {
            this.currentPlayerWasActive = false;
        }
    };
    // utils
    GameBasics.prototype.debugStateInfo = function () {
        var replayMode = false;
        if (typeof g_replayFrom != "undefined") {
            replayMode = true;
        }
        var res = {
            isCurrentPlayerActive: gameui.isCurrentPlayerActive(),
            animationsActive: gameui.bgaAnimationsActive(),
            replayMode: replayMode
        };
        return res;
    };
    GameBasics.prototype.callfn = function (methodName, args) {
        if (this[methodName] !== undefined) {
            console.log("Calling " + methodName, args);
            return this[methodName](args);
        }
        return undefined;
    };
    /** @Override onScriptError from gameui */
    GameBasics.prototype.onScriptError = function (msg, url, linenumber) {
        if (gameui.page_is_unloading) {
            // Don't report errors during page unloading
            return;
        }
        // In anycase, report these errors in the console
        console.error(msg);
        // cannot call super - dojo still have to used here
        //super.onScriptError(msg, url, linenumber);
        return this.inherited(arguments);
    };
    GameBasics.prototype.bgaFormatText = function (log, args) {
        if (log && args && !args.processed) {
            args.processed = true;
            if (!args.player_id) {
                args.player_id = this.getActivePlayerId();
            }
            if (args.player_id && !args.player_name) {
                args.player_name = this.gamedatas.players[args.player_id].name;
            }
        }
        return { log: log, args: args };
    };
    GameBasics.prototype.getTr = function (name, args) {
        if (args === void 0) { args = {}; }
        if (name === undefined)
            return null;
        if (name.log !== undefined) {
            var notif = name;
            var log = this.bgaFormatText(notif.log, notif.args).log;
            return this.clienttranslate_string(log);
        }
        else {
            var log = this.bgaFormatText(name, args).log;
            return this.clienttranslate_string(log);
        }
    };
    return GameBasics;
}(GameGui));
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GalacticCruise implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */
/**  Generic processing related to Operation Machine */
var GameMachine = /** @class */ (function (_super) {
    __extends(GameMachine, _super);
    function GameMachine() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    GameMachine.prototype.onEnteringState_auto = function (args) {
        if ((args === null || args === void 0 ? void 0 : args.description) && !this.isCurrentPlayerActive()) {
            this.statusBar.setTitle(args.description, args.args);
        }
    };
    GameMachine.prototype.onUpdateActionButtons_PlayerTurn = function (args) {
        var _this = this;
        this.completeOpInfo(args);
        if (args.descriptionOnMyTurn) {
            this.statusBar.setTitle(args.descriptionOnMyTurn, args.args);
        }
        var _loop_1 = function (target) {
            var paramInfo = args.info[target];
            this_1.statusBar.addActionButton(this_1.getTr(paramInfo.name), function () { return _this.resolveAction({ target: target }); });
        };
        var this_1 = this;
        for (var _i = 0, _a = args.target; _i < _a.length; _i++) {
            var target = _a[_i];
            _loop_1(target);
        }
        var _loop_2 = function (target) {
            var paramInfo = args.info[target];
            if (paramInfo.sec) {
                // skip, whatever TODO: anytime
                this_2.statusBar.addActionButton(this_2.getTr(paramInfo.name), function () { return _this.bgaPerformAction("action_".concat(target), {}); });
            }
        };
        var this_2 = this;
        for (var target in args.info) {
            _loop_2(target);
        }
        // need a global condition when this can be added
        this.statusBar.addActionButton(_("Undo"), function () { return _this.bgaPerformAction("action_undo"); }, { color: "alert" });
    };
    GameMachine.prototype.onUpdateActionButtons_PlayerTurnConfirm = function (args) {
        var _this = this;
        this.statusBar.addActionButton(_("Confirm"), function () { return _this.resolveAction(); });
        this.statusBar.addActionButton(_("Undo"), function () { return _this.bgaPerformAction("action_undo"); }, { color: "alert" });
    };
    GameMachine.prototype.resolveAction = function (args) {
        if (args === void 0) { args = {}; }
        this.bgaPerformAction("action_resolve", {
            data: JSON.stringify(args)
        });
    };
    GameMachine.prototype.completeOpInfo = function (opInfo) {
        var _a, _b;
        try {
            // server may skip sending some data, this will feel all omitted fields
            if (!opInfo.args)
                opInfo.args = [];
            if (((_a = opInfo.data) === null || _a === void 0 ? void 0 : _a.count) !== undefined)
                opInfo.args.count = parseInt(opInfo.data.count);
            if (opInfo.void === undefined)
                opInfo.void = false;
            opInfo.confirm = (_b = opInfo.confirm) !== null && _b !== void 0 ? _b : false;
            if (!opInfo.info)
                opInfo.info = {};
            if (!opInfo.target)
                opInfo.target = [];
            var infokeys = Object.keys(opInfo.info);
            if (infokeys.length == 0 && opInfo.target.length > 0) {
                opInfo.target.forEach(function (element) {
                    opInfo.info[element] = { q: 0 };
                });
            }
            else if (infokeys.length > 0 && opInfo.target.length == 0) {
                infokeys.forEach(function (element) {
                    if (opInfo.info[element].q == 0)
                        opInfo.target.push(element);
                });
            }
            // set default order
            var i = 1;
            for (var _i = 0, _c = opInfo.target; _i < _c.length; _i++) {
                var target = _c[_i];
                var paramInfo = opInfo.info[target];
                if (!paramInfo.o)
                    paramInfo.o = i;
                i++;
            }
        }
        catch (e) {
            console.error(e);
        }
    };
    return GameMachine;
}(GameBasics));
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GalacticCruise implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
/** Game class. Its Call XBody to be last in alphabetical order */
var GameXBody = /** @class */ (function (_super) {
    __extends(GameXBody, _super);
    function GameXBody() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    GameXBody.prototype.setup = function (gamedatas) {
        _super.prototype.setup.call(this, gamedatas);
        //super.setup(gamedatas);
        this.getGameAreaElement().insertAdjacentHTML("beforeend", " \n<div id=\"thething\">\n  <div class=\"whiteblock cow\">".concat(_("Should we eat the cow now?"), "</div>\n</div>\n      "));
        this.setupNotifications();
        console.log("Ending game setup");
    };
    GameXBody.prototype.setupNotifications = function () {
        var _this = this;
        console.log("notifications subscriptions setup");
        // automatically listen to the notifications, based on the `notif_xxx` function on this class.
        this.bgaSetupPromiseNotifications({
            minDuration: 1,
            minDurationNoText: 1,
            logger: console.log,
            onStart: function (notifName, msg, args) { return _this.statusBar.setTitle(msg, args); },
            onEnd: function (notifName, msg, args) { return _this.statusBar.setTitle("", args); }
        });
    };
    GameXBody.prototype.notif_message = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                //console.log("notif", args);
                return [2 /*return*/, this.wait(10)];
            });
        });
    };
    return GameXBody;
}(GameMachine));
/**
 * This is only code that has to use dojo
 * Note: this only works when targeting ES5
 */
define([
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    // libs
    getLibUrl("bga-animations", "1.x"),
    getLibUrl("bga-cards", "1.x")
], function (dojo, declare, gamegui, BgaAnimations, BgaCards) {
    window.BgaAnimations = BgaAnimations; //trick
    window.BgaCards = BgaCards;
    declare("bgagame.skarabrae", ebg.core.gamegui, new GameXBody());
});
