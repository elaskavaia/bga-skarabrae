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
var Game0Basics = /** @class */ (function (_super) {
    __extends(Game0Basics, _super);
    function Game0Basics() {
        var _this = _super.call(this) || this;
        _this.defaultTooltipDelay = 800;
        console.log("game constructor");
        return _this;
    }
    // state hooks
    Game0Basics.prototype.setup = function (gamedatas) {
        console.log("Starting game setup", gamedatas);
        var first_player_id = Object.keys(gamedatas.players)[0];
        if (!this.isSpectator)
            this.player_color = gamedatas.players[this.player_id].color;
        else
            this.player_color = gamedatas.players[first_player_id].color;
    };
    Game0Basics.prototype.onEnteringState = function (stateName, eargs) {
        console.log("onEnteringState", stateName, eargs, this.debugStateInfo());
        // Call appropriate method
        var args = eargs === null || eargs === void 0 ? void 0 : eargs.args; // this method has extra wrapper for args for some reason
        this.callfn("onEnteringState_before", args);
        var methodName = "onEnteringState_" + stateName;
        // Call appropriate method
        var privates = args._private;
        var nargs = args;
        if (privates) {
            nargs = __assign(__assign({}, nargs), privates);
            delete nargs._private;
        }
        this.callfn(methodName, nargs);
    };
    Game0Basics.prototype.onLeavingState = function (stateName) {
        console.log("onLeavingState", stateName, this.debugStateInfo());
        var methodName = "onLeavingState_" + stateName;
        this.callfn(methodName, {});
    };
    Game0Basics.prototype.onUpdateActionButtons = function (stateName, args) {
        // Call appropriate method
        var privates = args._private;
        var nargs = args;
        if (privates) {
            nargs = __assign(__assign({}, nargs), privates);
            delete nargs._private;
        }
        this.callfn("onUpdateActionButtons_" + stateName, nargs);
    };
    // utils
    /**
     * Remove all listed class from all document elements
     * @param classList - list of classes  array
     */
    Game0Basics.prototype.removeAllClasses = function () {
        var classList = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            classList[_i] = arguments[_i];
        }
        if (!classList)
            return;
        classList.forEach(function (className) {
            document.querySelectorAll(".".concat(className)).forEach(function (node) {
                node.classList.remove(className);
            });
        });
    };
    Game0Basics.prototype.onCancel = function (event) {
        this.cancelLocalStateEffects();
    };
    Game0Basics.prototype.cancelLocalStateEffects = function () {
        //console.log(this.last_server_state);
        if (this.on_client_state)
            this.restoreServerGameState();
        this.updatePageTitle(this.gamedatas.gamestate);
    };
    Game0Basics.prototype.updatePageTitle = function (state) {
        if (state === void 0) { state = null; }
        //debugger;
        console.log("updatePageTitle", state);
        if (this.prevent_error_rentry === undefined)
            this.prevent_error_rentry = 11; // XXX hack to prevent popin up formatter error
        try {
            return this.inherited(arguments);
        }
        catch (e) {
            console.error("updatePageTitle", e);
        }
        finally {
            this.prevent_error_rentry = undefined;
        }
    };
    Game0Basics.prototype.addCancelButton = function (name, handler) {
        var _this = this;
        if (!name)
            name = _("Cancel");
        if (!handler)
            handler = function () { return _this.onCancel(); };
        if ($("button_cancel"))
            $("button_cancel").remove();
        this.addActionButton("button_cancel", name, handler, null, false, "red");
    };
    /** Show pop in dialog. If you need div id of dialog its `popin_${id}` where id is second parameter here */
    Game0Basics.prototype.showPopin = function (html, id, title, refresh) {
        if (id === void 0) { id = "gg_dialog"; }
        if (title === void 0) { title = undefined; }
        if (refresh === void 0) { refresh = false; }
        var content_id = "popin_".concat(id, "_contents");
        if (refresh && $(content_id)) {
            $(content_id).innerHTML = html;
            return undefined;
        }
        var dialog = new ebg.popindialog();
        dialog.create(id);
        if (title)
            dialog.setTitle(title);
        dialog.setContent(html);
        dialog.show();
        return dialog;
    };
    Game0Basics.prototype.getStateName = function () {
        return this.gamedatas.gamestate.name;
    };
    Game0Basics.prototype.getServerStateName = function () {
        return this.last_server_state.name;
    };
    Game0Basics.prototype.getPlayerColor = function (playerId) {
        var _a, _b;
        return (_b = (_a = this.gamedatas.players[playerId]) === null || _a === void 0 ? void 0 : _a.color) !== null && _b !== void 0 ? _b : "ffffff";
    };
    Game0Basics.prototype.getPlayerName = function (playerId) {
        var _a, _b;
        return (_b = (_a = this.gamedatas.players[playerId]) === null || _a === void 0 ? void 0 : _a.name) !== null && _b !== void 0 ? _b : _("Not a Player");
    };
    Game0Basics.prototype.getPlayerIdByColor = function (color) {
        for (var playerId in this.gamedatas.players) {
            var playerInfo = this.gamedatas.players[playerId];
            if (color == playerInfo.color) {
                return parseInt(playerId);
            }
        }
        return undefined;
    };
    Game0Basics.prototype.removeTooltip = function (nodeId) {
        var _a;
        // if (this.tooltips[nodeId])
        if (!nodeId)
            return;
        //console.log("removeTooltip", nodeId);
        (_a = $(nodeId)) === null || _a === void 0 ? void 0 : _a.classList.remove("withtooltip");
        this.inherited(arguments);
        delete this.tooltips[nodeId];
    };
    /**
     * setClientState and defines handler for onUpdateActionButtons and onToken for specific client state only
     * the setClientState will be called asyncroniously
     * @param name - state name i.e. client_foo
     * @param onUpdate - onUpdateActionButtons handler
     * @param onToken - onToken handler
     * @param args - args passes to setClientState
     */
    Game0Basics.prototype.setClientStateUpdOn = function (name, onUpdate, onToken, args) {
        var _this = this;
        this["onUpdateActionButtons_".concat(name)] = onUpdate;
        if (onToken)
            this["onToken_".concat(name)] = onToken;
        setTimeout(function () { return _this.setClientState(name, args); }, 1);
    };
    Game0Basics.prototype.debugStateInfo = function () {
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
    Game0Basics.prototype.callfn = function (methodName, args) {
        if (this[methodName] !== undefined) {
            console.log("Calling " + methodName, args);
            return this[methodName](args);
        }
        return undefined;
    };
    /** @Override onScriptError from gameui */
    Game0Basics.prototype.onScriptError = function (msg, url, linenumber) {
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
    Game0Basics.prototype.bgaFormatText = function (log, args) {
        if (log && args && !args.processed) {
            args.processed = true;
            if (!args.player_id) {
                args.player_id = this.bga.players.getActivePlayerId();
            }
            if (args.player_id && !args.player_name) {
                args.player_name = this.gamedatas.players[args.player_id].name;
            }
        }
        return { log: this.format_string_recursive(log, args), args: args };
    };
    Game0Basics.prototype.divYou = function () {
        var color = "black";
        var color_bg = "";
        if (this.gamedatas.players[this.player_id]) {
            color = this.gamedatas.players[this.player_id].color;
        }
        if (this.gamedatas.players[this.player_id] && this.gamedatas.players[this.player_id].color_back) {
            color_bg = "background-color:#" + this.gamedatas.players[this.player_id].color_back + ";";
        }
        var you = '<span style="font-weight:bold;color:#' + color + ";" + color_bg + '">' + _("You") + "</span>";
        return you;
    };
    Game0Basics.prototype.getTr = function (name, args) {
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
    Game0Basics.prototype.isSolo = function () {
        return this.gamedatas.playerorder.length == 1;
    };
    Game0Basics.prototype.notif_log = function (notif) {
        if (notif.log) {
            console.log(notif.log, notif.args);
            var message = this.format_string_recursive(notif.log, notif.args);
            if (message != notif.log)
                console.log(message);
        }
        else {
            console.log("hidden log", notif);
        }
    };
    Game0Basics.prototype.notif_message_warning = function (notif) {
        if (this.bgaAnimationsActive()) {
            var message = this.format_string_recursive(notif.log, notif.args);
            this.showMessage(_("Warning:") + " " + message, "warning");
        }
    };
    Game0Basics.prototype.notif_message_info = function (notif) {
        if (this.bgaAnimationsActive()) {
            var message = this.format_string_recursive(notif.log, notif.args);
            this.showMessage(_("Announcement:") + " " + message, "info");
        }
    };
    return Game0Basics;
}(GameGui));
/** This is essentically dojo.place but without dojo */
function placeHtml(html, parent, how) {
    if (how === void 0) { how = "beforeend"; }
    return $(parent).insertAdjacentHTML(how, html);
}
function getIntPart(word, i) {
    return parseInt(getPart(word, i));
}
function getPart(word, i) {
    var arr = word.split("_");
    if (i < 0)
        i = arr.length + i;
    if (arr.length <= i)
        return "";
    return arr[i];
}
function getFirstParts(word, count) {
    var arr = word.split("_");
    var res = arr[0];
    for (var i = 1; i < arr.length && i < count; i++) {
        res += "_" + arr[i];
    }
    return res;
}
function getParentParts(word) {
    var arr = word.split("_");
    if (arr.length <= 1)
        return "";
    return getFirstParts(word, arr.length - 1);
}
var HelpMode = /** @class */ (function () {
    function HelpMode(game) {
        this.game = game;
        this._helpMode = false; // help mode where tooltip shown instead of click action
        this._displayedTooltip = null; // used in help mode
        this.helpModeHandler = this.onClickForHelp.bind(this);
        this.closeHelpHandler = this.closeCurrentTooltip.bind(this);
    }
    HelpMode.prototype.toggleHelpMode = function (b) {
        if (b)
            this.activateHelpMode();
        else
            this.deactivateHelpMode();
    };
    HelpMode.prototype.activateHelpMode = function () {
        var _this = this;
        var chk = $("help-mode-switch");
        chk.setAttribute("bchecked", "true");
        this._helpMode = true;
        $("ebd-body").classList.add("help-mode");
        this._displayedTooltip = null;
        document.body.addEventListener("click", this.closeHelpHandler);
        this.game.statusBar.setTitle(_("HELP MODE Activated. Click on game elements to get tooltips"));
        dojo.empty("generalactions");
        this.game.addCancelButton(undefined, function () { return _this.deactivateHelpMode(); });
        document.querySelectorAll(".withtooltip").forEach(function (node) {
            node.addEventListener("click", _this.helpModeHandler, false);
        });
    };
    HelpMode.prototype.deactivateHelpMode = function () {
        var _this = this;
        var chk = $("help-mode-switch");
        chk.setAttribute("bchecked", "false");
        this.closeCurrentTooltip();
        this._helpMode = false;
        $("ebd-body").classList.remove("help-mode");
        document.body.removeEventListener("click", this.closeHelpHandler);
        document.querySelectorAll(".withtooltip").forEach(function (node) {
            node.removeEventListener("click", _this.helpModeHandler, false);
        });
        this.game.on_client_state = true;
        this.game.cancelLocalStateEffects();
    };
    HelpMode.prototype.closeCurrentTooltip = function () {
        if (this._displayedTooltip == null)
            return;
        this._displayedTooltip.destroy();
        this._displayedTooltip = null;
    };
    HelpMode.prototype.onClickForHelp = function (event) {
        console.trace("onhelp", event);
        if (!this._helpMode)
            return false;
        event.stopPropagation();
        event.preventDefault();
        this.showHelp(event.currentTarget.id);
        return true;
    };
    HelpMode.prototype.showHelp = function (id, force) {
        if (!force)
            if (!this._helpMode)
                return false;
        if (this.game.tooltips[id]) {
            dijit.hideTooltip(id);
            var html = this.game.tooltips[id].getContent($(id));
            this._displayedTooltip = this.game.showPopin(html, "current_tooltip");
            return true;
        }
        return false;
    };
    return HelpMode;
}());
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
var Game1Tokens = /** @class */ (function (_super) {
    __extends(Game1Tokens, _super);
    function Game1Tokens() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        _this.globlog = 1;
        _this.tokenInfoCache = {};
        _this.defaultAnimationDuration = 500;
        _this.classActiveSlot = "active_slot";
        _this.classActiveSlotHidden = "hidden_active_slot";
        _this.classButtonDisabled = "disabled";
        _this.classSelected = "gg_selected"; // for the purpose of multi-select operations
        _this.classSelectedAlt = "gg_selected_alt"; // for the purpose of multi-select operations with alternative node
        _this.game = _this;
        return _this;
    }
    Game1Tokens.prototype.setupGame = function (gamedatas) {
        var _this = this;
        this.tokenInfoCache = {};
        // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
        this.animationManager = new BgaAnimations.Manager({
            animationsActive: function () { return _this.bgaAnimationsActive(); }
        });
        this.animationLa = new LaAnimations();
        if (!this.gamedatas.tokens) {
            console.error("Missing gamadatas.tokens!");
            this.gamedatas.tokens = {};
        }
        if (!this.gamedatas.token_types) {
            console.error("Missing gamadatas.token_types!");
            this.gamedatas.token_types = {};
        }
        this.gamedatas.tokens["limbo"] = {
            key: "limbo",
            state: 0,
            location: "thething"
        };
        this.placeTokenSetup("limbo");
        placeHtml("<div id=\"oversurface\"></div>", this.getGameAreaElement());
        this.setupTokens();
    };
    Game1Tokens.prototype.onLeavingState = function (stateName) {
        // console.log("onLeavingState: " + stateName);
        //this.disconnectAllTemp();
        this.removeAllClasses(this.classActiveSlot, this.classActiveSlotHidden);
        if (!this.on_client_state) {
            this.removeAllClasses(this.classSelected, this.classSelectedAlt);
        }
        _super.prototype.onLeavingState.call(this, stateName);
    };
    Game1Tokens.prototype.cancelLocalStateEffects = function () {
        //console.log(this.last_server_state);
        this.game.removeAllClasses(this.classActiveSlot, this.classActiveSlotHidden);
        this.game.removeAllClasses(this.classSelected, this.classSelectedAlt);
        //this.restoreServerData();
        //this.updateCountersSafe(this.gamedatas.counters);
    };
    Game1Tokens.prototype.addShowMeButton = function (scroll) {
        var _this = this;
        var firstTarget = document.querySelector("." + this.classActiveSlot);
        if (!firstTarget)
            return;
        this.statusBar.addActionButton(_("Show me"), function () {
            var butt = $("button_showme");
            var firstTarget = document.querySelector("." + _this.classActiveSlot);
            if (!firstTarget)
                return;
            if (scroll)
                $(firstTarget).scrollIntoView({ behavior: "smooth", block: "center" });
            document.querySelectorAll("." + _this.classActiveSlot).forEach(function (node) {
                var elem = node;
                elem.style.removeProperty("animation");
                elem.style.setProperty("animation", "active-pulse 500ms 3");
                butt.classList.add(_this.classButtonDisabled);
                setTimeout(function () {
                    elem.style.removeProperty("animation");
                    butt.classList.remove(_this.classButtonDisabled);
                }, 1500);
            });
        }, {
            color: "secondary",
            id: "button_showme"
        });
    };
    Game1Tokens.prototype.getAllLocations = function () {
        var res = [];
        for (var key in this.gamedatas.token_types) {
            var info = this.gamedatas.token_types[key];
            if (this.isLocationByType(key) && info.scope != "player")
                res.push(key);
        }
        for (var token in this.gamedatas.tokens) {
            var tokenInfo = this.gamedatas.tokens[token];
            var location = tokenInfo.location;
            if (location && res.indexOf(location) < 0)
                res.push(location);
        }
        return res;
    };
    Game1Tokens.prototype.isLocationByType = function (id) {
        return this.getRulesFor(id, "type", "").indexOf("location") >= 0;
    };
    Game1Tokens.prototype.setupTokens = function () {
        console.log("Setup tokens");
        for (var _i = 0, _a = this.getAllLocations(); _i < _a.length; _i++) {
            var loc = _a[_i];
            this.placeTokenSetup(loc);
        }
        for (var token in this.gamedatas.tokens) {
            var tokenInfo = this.gamedatas.tokens[token];
            var location_1 = tokenInfo.location;
            if (location_1 && !this.gamedatas.tokens[location_1] && !$(location_1)) {
                this.placeTokenSetup(location_1);
            }
            this.placeTokenSetup(token);
        }
        for (var token in this.gamedatas.tokens) {
            this.updateTooltip(token);
        }
        for (var _b = 0, _c = this.getAllLocations(); _b < _c.length; _b++) {
            var loc = _c[_b];
            this.updateTooltip(loc);
        }
    };
    Game1Tokens.prototype.setTokenInfo = function (token_id, place_id, new_state, serverdata, args) {
        var token = token_id;
        if (!this.gamedatas.tokens[token]) {
            this.gamedatas.tokens[token] = {
                key: token,
                state: 0,
                location: "limbo"
            };
        }
        if (args) {
            args._prev = structuredClone(this.gamedatas.tokens[token]);
        }
        if (place_id !== undefined) {
            this.gamedatas.tokens[token].location = place_id;
        }
        if (new_state !== undefined) {
            this.gamedatas.tokens[token].state = new_state;
        }
        //if (serverdata === undefined) serverdata = true;
        //if (serverdata && this.gamedatas_server) this.gamedatas_server.tokens[token] = dojo.clone(this.gamedatas.tokens[token]);
        return this.gamedatas.tokens[token];
    };
    Game1Tokens.prototype.createToken = function (placeInfo) {
        var _a, _b;
        var tokenId = placeInfo.key;
        var location = (_b = (_a = placeInfo.place_from) !== null && _a !== void 0 ? _a : placeInfo.location) !== null && _b !== void 0 ? _b : this.getRulesFor(tokenId, "location");
        var div = document.createElement("div");
        div.id = tokenId;
        var parentNode = $(location);
        if (location && !parentNode) {
            if (location.indexOf("{") == -1)
                console.error("Cannot find location [" + location + "] for ", div);
            parentNode = $("limbo");
        }
        parentNode.appendChild(div);
        return div;
    };
    Game1Tokens.prototype.updateToken = function (tokenNode, placeInfo) {
        var _a;
        var tokenId = placeInfo.key;
        var displayInfo = this.getTokenDisplayInfo(tokenId);
        var classes = displayInfo.imageTypes.split(/  */);
        (_a = tokenNode.classList).add.apply(_a, classes);
        if (displayInfo.name)
            tokenNode.dataset.name = this.getTr(displayInfo.name);
        if (!tokenNode.getAttribute("_lis") && placeInfo.onClick) {
            tokenNode.addEventListener("click", placeInfo.onClick);
            tokenNode.setAttribute("_lis", "1");
        }
    };
    Game1Tokens.prototype.findActiveParent = function (element) {
        if (this.isActiveSlot(element))
            return element;
        var parent = element.parentElement;
        if (!parent || parent.id == "thething" || parent == element)
            return null;
        return this.findActiveParent(parent);
    };
    /**
     * This is convenient function to be called when processing click events, it - remembers id of object - stops propagation - logs to
     * console - the if checkActive is set to true check if element has active_slot class
     */
    Game1Tokens.prototype.onClickSanity = function (event, checkActiveSlot, checkActivePlayer) {
        var id = event.currentTarget.id;
        var target = event.target;
        if (id == "thething") {
            var node = this.findActiveParent(target);
            id = node === null || node === void 0 ? void 0 : node.id;
            target = node;
        }
        console.log("on slot " + id, (target === null || target === void 0 ? void 0 : target.id) || target);
        if (!id)
            return null;
        if (this.showHelp(id))
            return null;
        if (checkActiveSlot && !id.startsWith("button_") && !this.checkActiveSlot(id)) {
            return null;
        }
        if (checkActivePlayer && !this.checkActivePlayer()) {
            return null;
        }
        if (target.dataset.targetId)
            return target.dataset.targetId;
        id = id.replace("tmp_", "");
        id = id.replace("button_", "");
        return id;
    };
    // override to hook the help
    Game1Tokens.prototype.showHelp = function (id) {
        return false;
    };
    // override to prove additinal animation parameters
    Game1Tokens.prototype.getPlaceRedirect = function (tokenInfo, args) {
        if (args === void 0) { args = {}; }
        return tokenInfo;
    };
    Game1Tokens.prototype.checkActivePlayer = function () {
        if (!this.bga.players.isCurrentPlayerActive()) {
            this.bga.dialogs.showMessage(_("This is not your turn"), "error");
            return false;
        }
        return true;
    };
    Game1Tokens.prototype.isActiveSlot = function (id) {
        var node = $(id);
        if (node.classList.contains(this.classActiveSlot)) {
            return true;
        }
        if (node.classList.contains(this.classActiveSlotHidden)) {
            return true;
        }
        return false;
    };
    Game1Tokens.prototype.checkActiveSlot = function (id, showError) {
        if (showError === void 0) { showError = true; }
        if (!this.isActiveSlot(id)) {
            if (showError) {
                console.error(new Error("unauth"), id);
                this.bga.dialogs.showMoveUnauthorized();
            }
            return false;
        }
        return true;
    };
    Game1Tokens.prototype.placeTokenServer = function (tokenId, location, state, args) {
        return __awaiter(this, void 0, void 0, function () {
            var tokenInfo;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        tokenInfo = this.setTokenInfo(tokenId, location, state, true, args);
                        return [4 /*yield*/, this.placeToken(tokenId, tokenInfo, args)];
                    case 1:
                        _a.sent();
                        this.updateTooltip(tokenId);
                        this.updateTooltip(tokenInfo.location);
                        return [2 /*return*/];
                }
            });
        });
    };
    Game1Tokens.prototype.prapareToken = function (tokenId, tokenDbInfo, args) {
        var _a;
        if (args === void 0) { args = {}; }
        if (!tokenDbInfo) {
            tokenDbInfo = this.gamedatas.tokens[tokenId];
        }
        if (!tokenDbInfo) {
            var tokenNode_1 = $(tokenId);
            if (tokenNode_1) {
                var st = parseInt(tokenNode_1.dataset.state);
                tokenDbInfo = this.setTokenInfo(tokenId, tokenNode_1.parentElement.id, st, false);
            }
            else {
                console.error("Cannot setup token for " + tokenId);
                tokenDbInfo = this.setTokenInfo(tokenId, undefined, 0, false);
            }
        }
        var placeInfo = this.getPlaceRedirect(tokenDbInfo, args);
        var tokenNode = (_a = $(tokenId)) !== null && _a !== void 0 ? _a : this.createToken(placeInfo);
        tokenNode.dataset.state = String(tokenDbInfo.state);
        tokenNode.dataset.location = tokenDbInfo.location;
        this.updateToken(tokenNode, placeInfo);
        // no movement
        if (placeInfo.nop) {
            return placeInfo;
        }
        var location = placeInfo.location;
        if (!$(location)) {
            if (location)
                console.error("Unknown place ".concat(location, " for ").concat(tokenId));
            return undefined;
        }
        return placeInfo;
    };
    Game1Tokens.prototype.placeTokenSetup = function (tokenId, tokenDbInfo) {
        var _a, _b;
        var placeInfo = this.prapareToken(tokenId, tokenDbInfo);
        if (!placeInfo) {
            return;
        }
        var tokenNode = $(tokenId);
        if (!tokenNode)
            return;
        void ((_a = placeInfo.onStart) === null || _a === void 0 ? void 0 : _a.call(placeInfo, tokenNode));
        if (placeInfo.nop) {
            return;
        }
        $(placeInfo.location).appendChild(tokenNode);
        void ((_b = placeInfo.onEnd) === null || _b === void 0 ? void 0 : _b.call(placeInfo, tokenNode));
    };
    Game1Tokens.prototype.placeToken = function (tokenId, tokenDbInfo, args) {
        var _a;
        if (args === void 0) { args = {}; }
        return __awaiter(this, void 0, void 0, function () {
            var placeInfo, tokenNode, animTime, e_1;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        _b.trys.push([0, 5, , 6]);
                        placeInfo = this.prapareToken(tokenId, tokenDbInfo, args);
                        if (!placeInfo) {
                            return [2 /*return*/];
                        }
                        tokenNode = $(tokenId);
                        animTime = (_a = placeInfo.animtime) !== null && _a !== void 0 ? _a : this.defaultAnimationDuration;
                        if (this.game.bgaAnimationsActive() == false || args.noa || placeInfo.animtime === 0 || !tokenNode.parentNode) {
                            animTime = 0;
                        }
                        if (!placeInfo.onStart) return [3 /*break*/, 2];
                        return [4 /*yield*/, placeInfo.onStart(tokenNode)];
                    case 1:
                        _b.sent();
                        _b.label = 2;
                    case 2:
                        if (!!placeInfo.nop) return [3 /*break*/, 4];
                        return [4 /*yield*/, this.slideAndPlace(tokenNode, placeInfo.location, animTime, 0, undefined, placeInfo.onEnd)];
                    case 3:
                        _b.sent();
                        _b.label = 4;
                    case 4: return [3 /*break*/, 6];
                    case 5:
                        e_1 = _b.sent();
                        console.error("Exception thrown", e_1, e_1.stack);
                        return [3 /*break*/, 6];
                    case 6: return [2 /*return*/];
                }
            });
        });
    };
    Game1Tokens.prototype.updateTooltip = function (tokenId, attachTo, delay) {
        if (attachTo === undefined) {
            attachTo = tokenId;
        }
        var attachNode = $(attachTo);
        if (!attachNode)
            return;
        // attach node has to have id
        if (!attachNode.id)
            attachNode.id = "gen_id_" + Math.random() * 10000000;
        // console.log("tooltips for "+token);
        if (typeof tokenId != "string") {
            console.error("cannot calc tooltip" + tokenId);
            return;
        }
        var tokenInfo = this.getTokenDisplayInfo(tokenId);
        if (tokenInfo.name) {
            attachNode.dataset.name = this.game.getTr(tokenInfo.name);
        }
        if (tokenInfo.showtooltip == false) {
            return;
        }
        if (tokenInfo.title) {
            attachNode.setAttribute("title", this.game.getTr(tokenInfo.title));
            return;
        }
        if (!tokenInfo.tooltip && !tokenInfo.name) {
            return;
        }
        var main = this.getTooltipHtmlForTokenInfo(tokenInfo);
        if (main) {
            attachNode.classList.add("withtooltip");
            if (attachNode.id != tokenId)
                attachNode.dataset.tt = tokenId; // id of token that provides the tooltip
            //console.log("addTooltipHtml", attachNode.id);
            this.game.addTooltipHtml(attachNode.id, main, delay !== null && delay !== void 0 ? delay : this.game.defaultTooltipDelay);
            attachNode.removeAttribute("title"); // unset title so both title and tooltip do not show up
            this.handleStackedTooltips(attachNode);
        }
        else {
            attachNode.classList.remove("withtooltip");
        }
    };
    Game1Tokens.prototype.handleStackedTooltips = function (attachNode) { };
    Game1Tokens.prototype.getTooltipHtmlForToken = function (token) {
        if (typeof token != "string") {
            console.error("cannot calc tooltip" + token);
            return null;
        }
        var tokenInfo = this.getTokenDisplayInfo(token, true);
        // console.log(tokenInfo);
        if (!tokenInfo)
            return;
        return this.getTooltipHtmlForTokenInfo(tokenInfo);
    };
    Game1Tokens.prototype.getTooltipHtmlForTokenInfo = function (tokenInfo) {
        return this.getTooltipHtml(tokenInfo.name, tokenInfo.tooltip, tokenInfo.imageTypes);
    };
    Game1Tokens.prototype.getTokenName = function (tokenId, force) {
        if (force === void 0) { force = true; }
        var tokenInfo = this.getTokenDisplayInfo(tokenId);
        if (tokenInfo) {
            return this.game.getTr(tokenInfo.name);
        }
        else {
            if (!force)
                return undefined;
            return "? " + tokenId;
        }
    };
    Game1Tokens.prototype.getTooltipHtml = function (name, message, imgTypes) {
        if (name == null || message == "-")
            return "";
        if (!message)
            message = "";
        var divImg = "";
        var containerType = "tooltipcontainer ";
        if (imgTypes) {
            divImg = "<div class='tooltipimage ".concat(imgTypes, "'></div>");
            var itypes = imgTypes.split(" ");
            for (var i = 0; i < itypes.length; i++) {
                containerType += itypes[i] + "_tooltipcontainer ";
            }
        }
        var name_tr = this.game.getTr(name);
        var body = "";
        if (imgTypes.includes("_override")) {
            body = message;
        }
        else {
            var message_tr = this.game.getTr(message);
            body = "\n           <div class='tooltip-left'>".concat(divImg, "</div>\n           <div class='tooltip-right'>\n             <div class='tooltiptitle'>").concat(name_tr, "</div>\n             <div class='tooltiptext'>").concat(message_tr, "</div>\n           </div>\n    ");
        }
        return "<div class='".concat(containerType, "'>\n        <div class='tooltip-body'>").concat(body, "</div>\n    </div>");
    };
    Game1Tokens.prototype.getTokenInfoState = function (tokenId) {
        var tokenInfo = this.gamedatas.tokens[tokenId];
        return parseInt(tokenInfo.state);
    };
    Game1Tokens.prototype.getAllRules = function (tokenId) {
        return this.getRulesFor(tokenId, "*", null);
    };
    Game1Tokens.prototype.getRulesFor = function (tokenId, field, def) {
        if (field === undefined)
            field = "r";
        var key = tokenId;
        var chain = [key];
        while (key) {
            var info = this.gamedatas.token_types[key];
            if (info === undefined) {
                key = getParentParts(key);
                if (!key) {
                    //console.error("Undefined info for " + tokenId);
                    return def;
                }
                chain.push(key);
                continue;
            }
            if (field === "*") {
                info["_chain"] = chain.join(" ");
                return info;
            }
            var rule = info[field];
            if (rule === undefined)
                return def;
            return rule;
        }
        return def;
    };
    Game1Tokens.prototype.getTokenDisplayInfo = function (tokenId, force) {
        var _a, _b;
        if (force === void 0) { force = false; }
        tokenId = String(tokenId);
        var cache = this.tokenInfoCache[tokenId];
        if (!force && cache) {
            return cache;
        }
        var tokenInfo = this.getAllRules(tokenId);
        if (!tokenInfo) {
            tokenInfo = {
                key: tokenId,
                _chain: tokenId,
                name: tokenId,
                showtooltip: false
            };
        }
        else {
            tokenInfo = structuredClone(tokenInfo);
        }
        var imageTypes = (_b = (_a = tokenInfo._chain) !== null && _a !== void 0 ? _a : tokenId) !== null && _b !== void 0 ? _b : "";
        var ita = imageTypes.split(" ");
        var tokenKey = ita[ita.length - 1];
        var declaredTypes = tokenInfo.type || "token";
        tokenInfo.typeKey = tokenKey; // this is key in token_types structure
        tokenInfo.mainType = getPart(tokenId, 0); // first type
        tokenInfo.imageTypes = "".concat(tokenInfo.mainType, " ").concat(declaredTypes, " ").concat(imageTypes).trim(); // other types used for div
        var create = tokenInfo.create;
        if (create == 3 || create == 4) {
            var prefix = tokenKey.split("_").length;
            tokenInfo.color = getPart(tokenId, prefix);
            tokenInfo.imageTypes += " color_" + tokenInfo.color;
        }
        if (create == 3) {
            var part = getPart(tokenId, -1);
            tokenInfo.imageTypes += " n_" + part;
        }
        if (!tokenInfo.key) {
            tokenInfo.key = tokenId;
        }
        tokenInfo.tokenId = tokenId;
        this.updateTokenDisplayInfo(tokenInfo);
        this.tokenInfoCache[tokenId] = tokenInfo;
        //console.log("cached", tokenId);
        return tokenInfo;
    };
    Game1Tokens.prototype.getTokenPresentaton = function (type, tokenKey, args) {
        if (args === void 0) { args = {}; }
        if (type.includes("_div"))
            return this.createTokenImage(tokenKey);
        return this.getTokenName(tokenKey); // just a name for now
    };
    // override to generate dynamic tooltips and such
    Game1Tokens.prototype.updateTokenDisplayInfo = function (tokenDisplayInfo) { };
    Game1Tokens.prototype.createTokenImage = function (tokenId) {
        var div = document.createElement("div");
        div.id = tokenId + "_tt_" + this.globlog++;
        this.updateToken(div, { key: tokenId, location: "log", state: 0 });
        div.title = this.getTokenName(tokenId);
        return div.outerHTML;
    };
    Game1Tokens.prototype.isMarkedForTranslation = function (key, args) {
        if (!args.i18n) {
            return false;
        }
        else {
            var i = args.i18n.indexOf(key);
            if (i >= 0) {
                return true;
            }
        }
        return false;
    };
    Game1Tokens.prototype.bgaFormatText = function (log, args) {
        if (log && args) {
            try {
                var keys = ["token_name", "token2_name", "token_divs", "token_names", "place_name", "token_div", "token2_div", "token3_div"];
                for (var i in keys) {
                    var key = keys[i];
                    // console.log("checking " + key + " for " + log);
                    if (args[key] === undefined)
                        continue;
                    var arg_value = args[key];
                    if (key == "token_divs" || key == "token_names") {
                        var list = args[key].split(",");
                        var res = "";
                        for (var l = 0; l < list.length; l++) {
                            var value = list[l];
                            if (l > 0)
                                res += ", ";
                            res += this.getTokenPresentaton(key, value, args);
                        }
                        res = res.trim();
                        if (res)
                            args[key] = res;
                        continue;
                    }
                    if (typeof arg_value == "string" && this.isMarkedForTranslation(key, args)) {
                        continue;
                    }
                    var res = this.getTokenPresentaton(key, arg_value, args);
                    if (res)
                        args[key] = res;
                }
            }
            catch (e) {
                console.error(log, args, "Exception thrown", e.stack);
            }
        }
        return _super.prototype.bgaFormatText.call(this, log, args);
    };
    Game1Tokens.prototype.slideAndPlace = function (token, finalPlace, duration, delay, mobileStyle, onEnd) {
        var _a;
        if (delay === void 0) { delay = 0; }
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        if (!$(token))
                            console.error("token not found for ".concat(token));
                        if (((_a = $(token)) === null || _a === void 0 ? void 0 : _a.parentNode) == $(finalPlace))
                            return [2 /*return*/];
                        if (this.game.bgaAnimationsActive() == false) {
                            duration = 0;
                            delay = 0;
                        }
                        if (!delay) return [3 /*break*/, 2];
                        return [4 /*yield*/, this.wait(delay)];
                    case 1:
                        _b.sent();
                        _b.label = 2;
                    case 2:
                        this.animationLa.phantomMove(token, finalPlace, duration, mobileStyle, onEnd);
                        return [2 /*return*/, this.wait(duration)];
                }
            });
        });
    };
    Game1Tokens.prototype.notif_animate = function (args) {
        var _a;
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_b) {
                return [2 /*return*/, this.game.wait((_a = args.time) !== null && _a !== void 0 ? _a : 1)];
            });
        });
    };
    Game1Tokens.prototype.notif_tokenMovedAsync = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                void this.notif_tokenMoved(args);
                return [2 /*return*/];
            });
        });
    };
    Game1Tokens.prototype.notif_tokenMoved = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            var moves, i, one, new_state;
            return __generator(this, function (_a) {
                if (args.list !== undefined) {
                    moves = [];
                    for (i = 0; i < args.list.length; i++) {
                        one = args.list[i];
                        new_state = args.new_state;
                        if (new_state === undefined) {
                            if (args.new_states !== undefined && args.new_states.length > i) {
                                new_state = args.new_states[i];
                            }
                        }
                        moves.push(this.placeTokenServer(one, args.place_id, new_state, args));
                    }
                    return [2 /*return*/, Promise.all(moves)];
                }
                else {
                    return [2 /*return*/, this.placeTokenServer(args.token_id, args.place_id, args.new_state, args)];
                }
                return [2 /*return*/];
            });
        });
    };
    Game1Tokens.prototype.notif_counterAsync = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                void this.notif_counter(args);
                return [2 /*return*/];
            });
        });
    };
    /**
     *
     * name: the name of the counter
  value: the new value
  oldValue: the value before the update
  inc: the increment
  absInc: the absolute value of the increment, allowing you to use '...loses ${absInc} ...' in the notif message if you are incrementing with a negative value
  playerId (only for PlayerCounter)
  player_name (only for PlayerCounter)
     * @param args
     * @returns
     *
     */
    Game1Tokens.prototype.notif_counter = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            var name_1, value, node;
            return __generator(this, function (_a) {
                try {
                    name_1 = args.name;
                    value = args.value;
                    node = $(name_1);
                    console.log("** notif counter " + args.counter_name + " -> " + args.counter_value);
                    if (node && this.gamedatas.tokens[name_1]) {
                        args.nop = true; // no move animation
                        return [2 /*return*/, this.placeTokenServer(name_1, this.gamedatas.tokens[name_1].location, value, args)];
                    }
                    else if (node) {
                        node.dataset.state = value;
                    }
                }
                catch (ex) {
                    console.error("Cannot update " + args.counter_name, ex, ex.stack);
                }
                return [2 /*return*/, this.game.wait(500)];
            });
        });
    };
    return Game1Tokens;
}(Game0Basics));
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
    GameMachine.prototype.onEnteringState_PlayerTurn = function (opInfo) {
        var _this = this;
        var _a, _b, _c, _d, _e;
        if (!this.bga.players.isCurrentPlayerActive()) {
            if (opInfo === null || opInfo === void 0 ? void 0 : opInfo.description)
                this.statusBar.setTitle(opInfo.description, opInfo);
            this.setSubPrompt("");
            return;
        }
        this.completeOpInfo(opInfo);
        this.opInfo = opInfo;
        if (opInfo.descriptionOnMyTurn) {
            this.statusBar.setTitle(opInfo.descriptionOnMyTurn, opInfo);
        }
        this.setSubPrompt(opInfo.subtitle, opInfo);
        if (opInfo.err) {
            var button = this.statusBar.addActionButton(this.getTr(opInfo.err, opInfo), function () { }, {
                color: "alert",
                id: "button_err"
            });
        }
        var multiselect = this.isMultiSelectArgs(opInfo);
        var multiCount = this.isMultiCountArgs(opInfo);
        var sortedTargets = Object.keys(opInfo.info);
        sortedTargets.sort(function (a, b) { return opInfo.info[a].o - opInfo.info[b].o; });
        for (var _i = 0, sortedTargets_1 = sortedTargets; _i < sortedTargets_1.length; _i++) {
            var target = sortedTargets_1[_i];
            var paramInfo = opInfo.info[target];
            if (paramInfo.sec) {
                continue; // secondary buttons
            }
            var div = $(target);
            var q = paramInfo.q;
            var active = q == 0;
            if (div && active && !multiCount) {
                (_a = div.classList) === null || _a === void 0 ? void 0 : _a.add(this.classActiveSlot);
                div.dataset.targetOpType = opInfo.type;
            }
            var altNode = void 0;
            if (opInfo.ui.replicate == true && div) {
                var clone = div.cloneNode(true);
                clone.id = div.id + "_temp";
                $("selection_area").appendChild(clone);
                clone.addEventListener("click", function (event) { return _this.onToken(event); });
                clone.classList.remove(this.classActiveSlot);
                clone.classList.add(this.classActiveSlotHidden);
                altNode = clone;
                this.updateTooltip(div.id, clone);
            }
            else if (opInfo.ui.buttons || !div) {
                var color = (_b = paramInfo.color) !== null && _b !== void 0 ? _b : (multiselect ? "secondary" : "primary");
                var button = this.statusBar.addActionButton(this.getParamPresentation(target, paramInfo), function (event) { return _this.onToken(event); }, {
                    color: color,
                    disabled: !active,
                    id: "button_" + target
                });
                if (!active) {
                    button.title = this.getTr((_c = paramInfo.err) !== null && _c !== void 0 ? _c : _("Operation cannot be performed now"), paramInfo);
                }
                else {
                    if (paramInfo.tooltip)
                        button.title = this.getTr(paramInfo.tooltip, paramInfo);
                }
                altNode = button;
            }
            if (!altNode)
                continue;
            altNode.dataset.targetId = target;
            altNode.dataset.targetOpType = opInfo.type;
            if (multiselect) {
                if (paramInfo.max !== undefined) {
                    altNode.dataset.max = String(paramInfo.max);
                }
                else {
                    altNode.dataset.max = "1";
                }
            }
        }
        if (opInfo.ui.buttons == false || opInfo.ui.replicate) {
            this.addShowMeButton(true);
        }
        var _loop_1 = function (target) {
            var paramInfo = opInfo.info[target];
            if (paramInfo.sec) {
                // skip, whatever TODO: anytime
                var color = (_d = paramInfo.color) !== null && _d !== void 0 ? _d : "secondary";
                var call_1 = (_e = paramInfo.call) !== null && _e !== void 0 ? _e : target;
                var button = this_1.statusBar.addActionButton(this_1.getParamPresentation(target, paramInfo), function () {
                    return _this.bga.actions.performAction("action_".concat(call_1), {
                        data: JSON.stringify({ target: target })
                    });
                }, {
                    color: color,
                    id: "button_" + target
                });
                button.dataset.targetId = target;
            }
        };
        var this_1 = this;
        // secondary buttons
        for (var _f = 0, sortedTargets_2 = sortedTargets; _f < sortedTargets_2.length; _f++) {
            var target = sortedTargets_2[_f];
            _loop_1(target);
        }
        if (multiselect) {
            this.activateMultiSelectPrompt(opInfo);
        }
        // need a global condition when this can be added
        this.addUndoButton();
    };
    GameMachine.prototype.getParamPresentation = function (target, paramInfo) {
        var _a;
        var div = $(target);
        var name = paramInfo.name;
        if (!name && div) {
            name = div.dataset.name;
        }
        if (!name)
            name = target;
        return this.getTr(name, (_a = paramInfo.args) !== null && _a !== void 0 ? _a : paramInfo);
    };
    GameMachine.prototype.isMultiSelectArgs = function (args) {
        return args.ttype == "token_count" || args.ttype == "token_array";
    };
    GameMachine.prototype.isMultiCountArgs = function (args) {
        return args.ttype == "token_array";
    };
    GameMachine.prototype.onLeavingState = function (stateName) {
        var _a;
        _super.prototype.onLeavingState.call(this, stateName);
        (_a = $("button_undo")) === null || _a === void 0 ? void 0 : _a.remove();
    };
    /** default click processor */
    GameMachine.prototype.onToken = function (event, fromMethod) {
        console.log(event);
        var id = this.onClickSanity(event);
        if (!id)
            return true;
        if (!fromMethod)
            fromMethod = "onToken";
        event.stopPropagation();
        event.preventDefault();
        var methodName = fromMethod + "_" + this.getStateName();
        var ret = this.callfn(methodName, id);
        if (ret === undefined)
            return false;
        return true;
    };
    GameMachine.prototype.onToken_PlayerTurn = function (tid) {
        var _a;
        //debugger;
        if (!tid)
            return false;
        var ttype = (_a = this.opInfo) === null || _a === void 0 ? void 0 : _a.ttype;
        if (ttype) {
            var methodName = "onToken_" + ttype;
            var ret = this.callfn(methodName, tid);
            if (ret === undefined)
                return false;
            return true;
        }
        console.error("no handler for ", ttype);
        return false;
    };
    GameMachine.prototype.onToken_token = function (target) {
        if (!target)
            return false;
        this.resolveAction({ target: target });
    };
    GameMachine.prototype.onToken_token_array = function (target) {
        if (!target)
            return false;
        this.onMultiCount(target, this.opInfo);
    };
    GameMachine.prototype.onToken_token_count = function (target) {
        if (!target)
            return false;
        this.onMultiCount(target, this.opInfo);
    };
    GameMachine.prototype.activateMultiSelectPrompt = function (opInfo) {
        var _this = this;
        var ttype = opInfo.ttype;
        var buttonName = _("Submit");
        var doneButtonId = "button_done";
        var resetButtonId = "button_reset";
        this.statusBar.addActionButton(buttonName, function () {
            var res = {};
            var count = _this.getMultiSelectCountAndSync(res);
            if (opInfo.ttype == "token_count") {
                _this.resolveAction({ target: res, count: count });
            }
            else {
                _this.resolveAction({ target: Object.keys(res), count: count });
            }
        }, {
            color: "primary",
            id: doneButtonId
        });
        this.statusBar.addActionButton(_("Reset"), function () {
            var allSel = document.querySelectorAll(".".concat(_this.classSelectedAlt, ",.").concat(_this.classSelected));
            allSel.forEach(function (node) {
                delete node.dataset.count;
            });
            _this.removeAllClasses(_this.classSelected, _this.classSelectedAlt);
            _this.onMultiSelectionUpdate(opInfo);
        }, {
            color: "alert",
            id: resetButtonId
        });
        // this.replicateTokensOnToolbar(opInfo, (target) => {
        //   return this.onMultiCount(target, opInfo);
        // });
        this.onMultiSelectionUpdate(opInfo);
        this["onToken_".concat(ttype)] = function (tid) {
            return _this.onMultiCount(tid, opInfo);
        };
    };
    GameMachine.prototype.onUpdateActionButtons_PlayerTurnConfirm = function (args) {
        var _this = this;
        this.statusBar.addActionButton(_("Confirm"), function () { return _this.resolveAction(); });
        this.addUndoButton();
    };
    GameMachine.prototype.resolveAction = function (args) {
        if (args === void 0) { args = {}; }
        this.bga.actions.performAction("action_resolve", {
            data: JSON.stringify(args)
        });
    };
    GameMachine.prototype.addUndoButton = function () {
        var _this = this;
        var _a;
        if (!$("button_undo") && !this.bga.players.isCurrentPlayerSpectator() && this.bga.players.isCurrentPlayerActive()) {
            var div = this.statusBar.addActionButton(_("Undo"), function () { return _this.bga.actions.performAction("action_undo"); }, {
                color: "alert",
                id: "button_undo"
            });
            div.classList.add("button_undo");
            div.title = _("Undo all possible steps");
            (_a = $("undoredo_wrap")) === null || _a === void 0 ? void 0 : _a.appendChild(div);
            // const div2 = this.addActionButtonColor("button_undo_last", _("Undo"), () => this.sendActionUndo(-1), "red");
            // div2.classList.add("button_undo");
            // div2.title = _("Undo One Step");
            // $("undoredo_wrap")?.appendChild(div2);
        }
    };
    GameMachine.prototype.getMultiSelectCountAndSync = function (result) {
        if (result === void 0) { result = {}; }
        // sync alternative selection on toolbar
        var allSel = document.querySelectorAll(".".concat(this.classSelected));
        var selectedAlt = this.classSelectedAlt;
        this.removeAllClasses(selectedAlt);
        var totalCount = 0;
        allSel.forEach(function (node) {
            var _a;
            var altnode = document.querySelector("[data-target-id=\"".concat(node.id, "\"]"));
            if (altnode) {
                altnode.classList.add(selectedAlt);
            }
            var cnode = altnode !== null && altnode !== void 0 ? altnode : node;
            var tid = (_a = cnode.dataset.targetId) !== null && _a !== void 0 ? _a : node.id;
            var count = cnode.dataset.count === undefined ? 1 : Number(cnode.dataset.count);
            result[tid] = count;
            totalCount += count;
        });
        return totalCount;
    };
    GameMachine.prototype.onMultiCount = function (tid, opInfo) {
        var _a, _b;
        var node = $(tid);
        var altnode = document.querySelector("[data-target-id=\"".concat(tid, "\"]"));
        var cnode = altnode !== null && altnode !== void 0 ? altnode : node;
        var count = Number((_a = cnode.dataset.count) !== null && _a !== void 0 ? _a : 0);
        cnode.dataset.count = String(count + 1);
        var max = Number((_b = cnode.dataset.max) !== null && _b !== void 0 ? _b : 1);
        if (count + 1 > max) {
            cnode.dataset.count = "0";
            if (node)
                node.classList.remove(this.classSelected);
            else
                cnode.classList.remove(this.classSelected);
        }
        else {
            if (node)
                node.classList.add(this.classSelected);
            else
                cnode.classList.add(this.classSelected);
        }
        this.onMultiSelectionUpdate(opInfo);
        return;
    };
    GameMachine.prototype.onMultiSelectionUpdate = function (opInfo) {
        var _a, _b;
        var ttype = opInfo.ttype;
        var skippable = false; // XXX
        var doneButtonId = "button_done";
        var resetButtonId = "button_reset";
        var skipButton = $("button_skip");
        var buttonName = _("Submit");
        // sync real selection to alt selection on toolbar
        var count = this.getMultiSelectCountAndSync();
        var doneButton = $(doneButtonId);
        if (doneButton) {
            if ((count == 0 && skippable) || count < opInfo.mcount) {
                doneButton.classList.add(this.classButtonDisabled);
                doneButton.title = _("Cannot use this action because insuffient amount of elements selected");
            }
            else if (count > opInfo.count) {
                doneButton.classList.add(this.classButtonDisabled);
                doneButton.title = _("Cannot use this action because superfluous amount of elements selected");
            }
            else {
                doneButton.classList.remove(this.classButtonDisabled);
                doneButton.title = "";
            }
            $(doneButtonId).innerHTML = buttonName + ": " + count;
        }
        if (count > 0) {
            (_a = $(resetButtonId)) === null || _a === void 0 ? void 0 : _a.classList.remove(this.classButtonDisabled);
            if (skipButton) {
                skipButton.classList.add(this.classButtonDisabled);
                skipButton.title = _("Cannot use this action because there are some elements selected");
            }
        }
        else {
            (_b = $(resetButtonId)) === null || _b === void 0 ? void 0 : _b.classList.add(this.classButtonDisabled);
            if (skipButton) {
                skipButton.title = "";
                skipButton.classList.remove(this.classButtonDisabled);
            }
        }
    };
    GameMachine.prototype.setSubPrompt = function (text, args) {
        if (args === void 0) { args = {}; }
        if (!text)
            text = "";
        var message = this.format_string_recursive(this.getTr(text, args), args);
        // have to set after otherwise status update wipes it
        setTimeout(function () {
            $("gameaction_status").innerHTML = "<div class=\"subtitle\">".concat(message, "</div>");
        }, 100);
    };
    GameMachine.prototype.completeOpInfo = function (opInfo) {
        var _a, _b, _c;
        try {
            // server may skip sending some data, this will feel all omitted fields
            if (((_a = opInfo.data) === null || _a === void 0 ? void 0 : _a.count) !== undefined && opInfo.count === undefined)
                opInfo.count = parseInt(opInfo.data.count);
            if (((_b = opInfo.data) === null || _b === void 0 ? void 0 : _b.mcount) !== undefined && opInfo.mcount === undefined)
                opInfo.mcount = parseInt(opInfo.data.mcount);
            if (opInfo.void === undefined)
                opInfo.void = false;
            opInfo.confirm = (_c = opInfo.confirm) !== null && _c !== void 0 ? _c : false;
            if (!opInfo.info)
                opInfo.info = {};
            if (!opInfo.target)
                opInfo.target = [];
            if (!opInfo.ui)
                opInfo.ui = {};
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
            for (var _i = 0, _d = opInfo.target; _i < _d.length; _i++) {
                var target = _d[_i];
                var paramInfo = opInfo.info[target];
                if (!paramInfo.o)
                    paramInfo.o = i;
                i++;
            }
            if (opInfo.info.confirm && !opInfo.info.confirm.name) {
                opInfo.info.confirm.name = _("Confirm");
            }
            if (opInfo.info.skip && !opInfo.info.skip.name) {
                opInfo.info.skip.name = _("Skip");
            }
            if (opInfo.ui.buttons === undefined) {
                opInfo.ui.buttons = true;
            }
        }
        catch (e) {
            console.error(e);
        }
    };
    return GameMachine;
}(Game1Tokens));
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
/** Game class. Its Call XBody to be last in alphabetical order */
var GameXBody = /** @class */ (function (_super) {
    __extends(GameXBody, _super);
    function GameXBody() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        _this.inSetup = true;
        _this.gameTemplate = "\n<div id=\"thething\">\n\n<div id=\"round_banner\">\n  <span id='tracker_nrounds'> </span>\n  <span id='tracker_nturns'> </span>\n  <span id='round_banner_text'></span>\n</div>\n<div id='selection_area' class='selection_area'></div>\n<div id=\"game-score-sheet\"></div>\n<div id='tasks_area' class='tasks_area'></div>\n<div id=\"players_panels\"></div>\n<div id=\"mainarea\">\n <div id=\"turnover\" class=\"turnover\">\n    <div id=\"turndisk\" class=\"turndisk\"></div>\n </div>\n\n <div id=\"cardset_1\" class=\"cardset cardset_1\"></div>\n <div id=\"cardset_2\" class=\"cardset cardset_2\"></div>\n <div id=\"cardset_3\" class=\"cardset cardset_3\"></div>\n </div>\n <div id=\"supply\">\n <div id=\"discard_village\" class=\"discard village\"></div>\n <div id=\"deck_village\" class=\"deck village\"></div>\n <div id=\"deck_roof\" class=\"deck roof\"></div>\n</div>\n\n\n\n";
        return _this;
    }
    GameXBody.prototype.setup = function (gamedatas) {
        var _a;
        try {
            _super.prototype.setup.call(this, gamedatas);
            placeHtml(this.gameTemplate, this.bga.gameArea.getElement());
            // Setting up player boards
            for (var _i = 0, _b = gamedatas.playerorder; _i < _b.length; _i++) {
                var playerId = _b[_i];
                var playerInfo = gamedatas.players[playerId];
                this.setupPlayer(playerInfo);
            }
            _super.prototype.setupGame.call(this, gamedatas);
            this.setupNotifications();
            this.setupScoreSheet();
            this.updateBanner();
            (_a = document.rootElement) === null || _a === void 0 ? void 0 : _a.classList.add("bgaext_cust_back");
        }
        catch (e) {
            console.error("Exception during game setup", e.stack);
        }
        console.log("Ending game setup");
        this.inSetup = false;
    };
    GameXBody.prototype.updateBanner = function () {
        $("round_banner_text").innerHTML = "";
        if (this.gamedatas.gameEnded) {
            $("round_banner_text").innerHTML = _("Game Over");
            //this.bga.gameArea.addLastTurnBanner(_("Game is ended"));
        }
        else if (this.gamedatas.tokens.tracker_nrounds.state == 4 && this.gamedatas.tokens.tracker_nturns.state == 3) {
            $("round_banner_text").innerHTML = _("This is Last Turn of Last Round");
        }
        else if (this.gamedatas.tokens.tracker_nturns.state == 3) {
            $("round_banner_text").innerHTML = _("This is Last Turn before End of Round");
        }
        else if (!this.bga.players.isCurrentPlayerSpectator()) {
            var room = $("breakroom_".concat(this.player_color));
            if (room) {
                var count = room.querySelectorAll(".worker[data-state='1']").length;
                $("round_banner_text").innerHTML = this.getTr(_("Workers ${count}"), { count: count });
            }
        }
    };
    GameXBody.prototype.setupPlayer = function (playerInfo) {
        console.log("player info " + playerInfo.id, playerInfo);
        var pp = "player_panel_content_".concat(playerInfo.color);
        document.querySelectorAll("#".concat(pp, ">.miniboard")).forEach(function (node) { return node.remove(); });
        placeHtml("<div id='miniboard_".concat(playerInfo.color, "' class='miniboard'></div>"), pp);
        placeHtml("\n      <div id='tableau_".concat(playerInfo.color, "' class='tableau' data-player-name='").concat(playerInfo.name, "' style='--player-color: #").concat(playerInfo.color, "'>\n        <div class='pboard_area'>\n           <div id='pboard_").concat(playerInfo.color, "' class='pboard'>\n                 <div id='track_furnish_").concat(playerInfo.color, "' class='track_furnish track'></div>\n                 <div id='track_trade_").concat(playerInfo.color, "' class='track_trade track'></div>\n                 <div id='breakroom_").concat(playerInfo.color, "' class='breakroom'></div>\n                 <div id='storage_").concat(playerInfo.color, "' class='storage'></div>\n           </div>\n           <div id='cards_area_").concat(playerInfo.color, "' class='cards_area'>\n           </div>\n         </div>\n         <div class='village_area'>\n            <div id='action_area_").concat(playerInfo.color, "' class='action_area'></div>\n            <div id='settlers_area_").concat(playerInfo.color, "' class='settlers_area'>\n               <div id='settlers_col_").concat(playerInfo.color, "_1' class='settlers_col_1'></div>\n               <div id='settlers_col_").concat(playerInfo.color, "_2' class='settlers_col_2'></div>\n               <div id='settlers_col_").concat(playerInfo.color, "_3' class='settlers_col_3'></div>\n               <div id='settlers_col_").concat(playerInfo.color, "_4' class='settlers_col_4'></div>\n            </div>\n         </div>\n      </div>"), "players_panels");
        for (var i = 0; i <= 6; i++) {
            placeHtml("<div id='slot_furnish_".concat(i, "_").concat(playerInfo.color, "' class='slot_furnish slot_furnish_").concat(i, "'></div>"), "track_furnish_".concat(playerInfo.color));
        }
        for (var i = 0; i <= 7; i++) {
            placeHtml("<div id='slot_trade_".concat(i, "_").concat(playerInfo.color, "' class='slot_trade slot_trade_").concat(i, "'></div>"), "track_trade_".concat(playerInfo.color));
        }
    };
    GameXBody.prototype.setupScoreSheet = function () {
        var _this = this;
        // this.gamedatas.endScores = {};
        // this.gamedatas.endScores[this.player_id] = {
        //   game_vp_setl_count: 5,
        //   game_vp_setl_sets: 8,
        //   game_vp_trade: 3,
        //   game_vp_action_tiles: 4,
        //   game_vp_cards: 6,
        //   game_vp_food: 2,
        //   game_vp_skaill: 3,
        //   game_vp_midden: -2,
        //   game_vp_slider: -1,
        //   game_vp_tasks: -3,
        //   game_vp_goals: -1,
        //   total: 24
        // };
        var entries = [
            { property: "game_vp_setl_count", label: _("VP for settlers cards") },
            { property: "game_vp_setl_sets", label: _("VP for settler sets") },
            { property: "game_vp_trade", label: _("VP from trade track") },
            { property: "game_vp_action_tiles", label: _("VP from action tiles") },
            { property: "game_vp_cards", label: _("VP from cards") },
            { property: "game_vp_food", label: _("VP from food") },
            { property: "game_vp_skaill", label: _("VP from skaill knives") },
            { property: "game_vp_midden", label: _("VP penalty from midden") },
            { property: "game_vp_slider", label: _("VP penlty from slider") },
            { property: "game_vp_tasks", label: _("VP penalty from tasks") },
            { property: "game_vp_goals", label: _("VP penalty from goals") },
            { property: "total", label: _("Total"), scoresClasses: "total", width: 80, height: 40 }
        ];
        if (!this.isSolo()) {
            entries.splice(9, 2);
        }
        this.scoreSheet = new BgaScoreSheet.ScoreSheet(document.getElementById("game-score-sheet"), {
            animationsActive: function () { return _this.gameAnimationsActive(); },
            playerNameWidth: 80,
            playerNameHeight: 30,
            entryLabelWidth: 180,
            entryLabelHeight: 20,
            classes: "score-sheet",
            players: this.gamedatas.players,
            entries: entries,
            scores: this.gamedatas.endScores,
            onScoreDisplayed: function (property, playerId, score) {
                // if (property === "total") {
                //   gameui.scoreCtrl[playerId].setValue(score);
                // }
            }
        });
    };
    GameXBody.prototype.onEnteringState_PlayerTurn = function (opInfo) {
        _super.prototype.onEnteringState_PlayerTurn.call(this, opInfo);
        switch (opInfo.type) {
            case "turn":
                $("selection_area").appendChild($("mainarea"));
                break;
            case "act":
                //if ((opInfo as any).turn == 3) this.bga.gameArea.addLastTurnBanner(_("This is the last turn before you need to feed the settlers"));
                break;
        }
    };
    GameXBody.prototype.onLeavingState_PlayerTurn = function () {
        var _a;
        var opInfo = this.opInfo;
        if ((opInfo === null || opInfo === void 0 ? void 0 : opInfo.type) == "turn")
            $("thething").appendChild($("mainarea"));
        if ((_a = opInfo === null || opInfo === void 0 ? void 0 : opInfo.ui) === null || _a === void 0 ? void 0 : _a.replicate) {
            $("selection_area")
                .querySelectorAll("& > *")
                .forEach(function (element) {
                element.remove();
            });
        }
    };
    GameXBody.prototype.showHelp = function (id) {
        return false;
    };
    GameXBody.prototype.hideCard = function (tokenId) {
        var _a;
        (_a = $("limbo")) === null || _a === void 0 ? void 0 : _a.appendChild($(tokenId));
    };
    GameXBody.prototype.getPlaceRedirect = function (tokenInfo, args) {
        var _this = this;
        var _a;
        if (args === void 0) { args = {}; }
        var location = (_a = tokenInfo.location) !== null && _a !== void 0 ? _a : "limbo";
        var tokenId = tokenInfo.key;
        var result = {
            location: location,
            key: tokenId,
            state: tokenInfo.state
        };
        if (args.place_from)
            result.place_from = args.place_from;
        if (args.inc)
            result.inc = args.inc;
        if (!this.gameAnimationsActive()) {
            result.animtime = 0;
        }
        if (tokenId.startsWith("action") && location.startsWith("tableau")) {
            var color = getPart(location, 1);
            result.location = "action_area_".concat(color);
            result.onClick = function (x) { return _this.onToken(x); };
        }
        else if (tokenId.startsWith("action") && location.startsWith("hand")) {
            var color = getPart(location, 1);
            result.location = "selection_area";
            result.onClick = function (x) { return _this.onToken(x); };
        }
        else if (tokenId.startsWith("card")) {
            result.onClick = function (x) { return _this.onToken(x); };
            if (tokenId.startsWith("card_setl") && location.startsWith("tableau")) {
                var color = getPart(location, 1);
                var t = this.getRulesFor(tokenId, "t");
                result.location = "settlers_col_".concat(color, "_").concat(t);
            }
            else if ((tokenId.startsWith("card_task") || tokenId.startsWith("card_goal")) && location.startsWith("tableau")) {
                var color = getPart(location, 1);
                result.location = "tasks_area";
                result.onClick = function (x) { return _this.onToken(x); };
            }
            else if (location.startsWith("hand")) {
                var color = getPart(location, 1);
                result.location = "selection_area";
                result.onClick = function (x) { return _this.onToken(x); };
            }
            else if (tokenId.startsWith("card") && location.startsWith("tableau")) {
                var color = getPart(location, 1);
                result.location = "cards_area_".concat(color);
            }
        }
        else if (location.startsWith("discard")) {
            //result.onEnd = (node) => this.hideCard(node);
        }
        else if (location.startsWith("deck")) {
            result.onEnd = function (node) { return _this.hideCard(node); };
        }
        else if (tokenId.startsWith("tableau")) {
            result.nop = true;
        }
        else if (tokenId.startsWith("hand")) {
            result.nop = true;
        }
        else if (tokenId.startsWith("slot") || tokenId == "round_banner") {
            result.nop = true; // do not move slots
        }
        else if (tokenId.startsWith("tracker_slider")) {
            var color = getPart(location, 1);
            result.location = "pboard_".concat(color);
        }
        else if (tokenId.startsWith("tracker")) {
            if (this.getRulesFor(tokenId, "s") == 1) {
                result.onStart = function () { return __awaiter(_this, void 0, void 0, function () {
                    return __generator(this, function (_a) {
                        return [2 /*return*/, this.syncStorage(result)];
                    });
                }); };
            }
            if (tokenId == "tracker_nturns" || tokenId == "tracker_nrounds") {
                result.nop = true;
                this.updateBanner();
            }
        }
        else if (location.startsWith("miniboard") && $(tokenId)) {
            result.nop = true; // do not move
        }
        else if (tokenId.startsWith("worker") && location.startsWith("tableau")) {
            var color = getPart(location, 1);
            result.location = "breakroom_".concat(color);
            this.updateBanner();
        }
        return result;
    };
    GameXBody.prototype.syncStorage = function (result) {
        return __awaiter(this, void 0, void 0, function () {
            var tokenId, tokenNode, count, color, promisses, placeFrom, i_1, item, itemNode, targetLoc, div, i, _loop_2, this_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        tokenId = result.key;
                        tokenNode = $(result.key);
                        count = result.state;
                        color = getPart(tokenId, 2);
                        promisses = [];
                        placeFrom = tokenId;
                        if (result.place_from) {
                            if (!$(result.place_from)) {
                                console.error("missing location " + placeFrom);
                            }
                            else {
                                placeFrom = result.place_from;
                            }
                        }
                        for (i_1 = 0; i_1 < count; i_1++) {
                            item = "item_".concat(tokenId, "_").concat(i_1);
                            itemNode = $(item);
                            if (!itemNode) {
                                targetLoc = "storage_".concat(color);
                                div = document.createElement("div");
                                div.id = item;
                                this.updateToken(div, { key: tokenId, location: placeFrom, state: 0 });
                                div.title = this.getTokenName(tokenId);
                                if (this.gameAnimationsActive()) {
                                    $(placeFrom).appendChild(div);
                                    promisses.push(this.slideAndPlace(item, targetLoc, 500, i_1 * 100));
                                }
                                else {
                                    $(targetLoc).appendChild(div);
                                }
                            }
                        }
                        i = count;
                        _loop_2 = function () {
                            var itemNode = $("item_".concat(tokenId, "_").concat(i));
                            if (itemNode) {
                                // remove
                                if (this_2.gameAnimationsActive()) {
                                    promisses.push(this_2.slideAndPlace(itemNode, placeFrom, 500, i * 100, undefined, function () { return itemNode.remove(); }));
                                }
                                else {
                                    itemNode.remove();
                                }
                            }
                            i++;
                        };
                        this_2 = this;
                        while (i < 100) {
                            _loop_2();
                        }
                        return [4 /*yield*/, Promise.allSettled(promisses)];
                    case 1:
                        _a.sent();
                        return [2 /*return*/];
                }
            });
        });
    };
    GameXBody.prototype.gameAnimationsActive = function () {
        return gameui.bgaAnimationsActive() && !this.inSetup;
    };
    GameXBody.prototype.updateTokenDisplayInfo = function (tokenInfo) {
        var _a, _b;
        // override to generate dynamic tooltips and such
        var mainType = tokenInfo.mainType;
        var token = $(tokenInfo.tokenId);
        var parentId = (_a = token === null || token === void 0 ? void 0 : token.parentElement) === null || _a === void 0 ? void 0 : _a.id;
        var state = parseInt(token === null || token === void 0 ? void 0 : token.dataset.state);
        switch (mainType) {
            case "worker":
                {
                    var tokenId = tokenInfo.key;
                    var name_2 = tokenInfo.name;
                    tokenInfo.tooltip = {
                        log: "${name} (${color_name})",
                        args: {
                            name: this.getTr(name_2),
                            color_name: this.getTr(this.getColorName(getPart(tokenId, 2)))
                        }
                    };
                }
                return;
            case "slot":
                {
                    var tokenId = tokenInfo.key;
                    var slotNum = getPart(tokenId, 2);
                    var name_3 = (_b = tokenInfo.name) !== null && _b !== void 0 ? _b : _("Slot") + " #" + slotNum;
                    if (tokenId.startsWith("slot_furnish")) {
                        name_3 = _("Furnish Slot") + " #" + slotNum;
                    }
                    tokenInfo.tooltip += "tbd";
                    tokenInfo.name = name_3;
                }
                return;
            case "card":
                {
                    var tokenId = tokenInfo.key;
                    var name_4 = tokenInfo.name;
                    var tooltip = tokenInfo.tooltip;
                    if (tokenId.startsWith("card_setl")) {
                        tokenInfo.tooltip = _("When gaining this card you must resolve top harvest and you may resolve bottom effect");
                        tokenInfo.tooltip += this.ttSection(_("Environment"), this.getTokenName("env_".concat(tokenInfo.t)));
                        tokenInfo.tooltip += this.ttSection(_("Bottom Effect"), tooltip);
                    }
                    else if (tokenId.startsWith("card_ball")) {
                        tokenInfo.tooltip = _("Gain skaill knife for each Stone Ball you have");
                    }
                    else if (tokenId.startsWith("card_spin")) {
                        tokenInfo.tooltip = _("Gain wool for each Spindle you have");
                    }
                    else if (tokenId.startsWith("card_roof")) {
                        tokenInfo.tooltip = _("No immediate effect. Provides a Roof during end of round. Each roof reduces amount of food you need to pay by one");
                    }
                    else if (tokenId.startsWith("card_util")) {
                        tokenInfo.tooltip = _("Gain Hide. Increase your Hearth by one. Decrease you Midden production by one");
                    }
                    else if (tokenId.startsWith("card_goal")) {
                        tokenInfo.tooltip += this.ttSection(undefined, _("If you have NOT met the condition shown on the Focus Card, you lose 5VP. Condition evaluated at the end of the game"));
                    }
                }
                return;
            case "cardset":
                tokenInfo.showtooltip = false;
                return;
            case "deck":
                tokenInfo.showtooltip = true;
                tokenInfo.tooltip = _("Village Deck contains village cards");
                //tokenInfo.name = "XXX";
                return;
        }
    };
    GameXBody.prototype.ttSection = function (prefix, text) {
        if (prefix)
            return "<p><b>".concat(prefix, "</b>: ").concat(text, "</p>");
        else
            return "<p>".concat(text, "</p>");
    };
    GameXBody.prototype.getColorName = function (color) {
        switch (color) {
            case "ff0000":
                return _("Red");
            case "ffcc02":
                return _("Yellow");
            case "982fff":
                return _("Purple");
            case "6cd0f6":
                return _("Blue");
            default:
                return _("Black");
        }
    };
    GameXBody.prototype.setupNotifications = function () {
        var _this = this;
        console.log("notifications subscriptions setup");
        // automatically listen to the notifications, based on the `notif_xxx` function on this class.
        this.bgaSetupPromiseNotifications({
            minDuration: 1,
            minDurationNoText: 1,
            logger: console.log,
            //handlers: [this, this.tokens],
            onStart: function (notifName, msg, args) { return _this.setSubPrompt(msg, args); }
            // onEnd: (notifName, msg, args) => this.setSubPrompt("", args)
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
    GameXBody.prototype.notif_endScores = function (args) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        // setting scores will make the score sheet visible if it isn't already
                        if (args.final) {
                            $("round_banner").innerHTML = _("Game Over");
                        }
                        return [4 /*yield*/, this.scoreSheet.setScores(args.endScores, {
                                startBy: this.bga.players.getCurrentPlayerId()
                            })];
                    case 1:
                        _a.sent();
                        return [2 /*return*/];
                }
            });
        });
    };
    /** @Override */
    GameXBody.prototype.bgaFormatText = function (log, args) {
        try {
            if (log && args && !args.processed) {
                args.processed = true;
                if (!args.player_id) {
                    args.player_id = this.bga.players.getActivePlayerId();
                }
                if (args.player_id && !args.player_name) {
                    args.player_name = this.gamedatas.players[args.player_id].name;
                }
                if (args.you)
                    args.you = this.divYou(); // will replace ${you} with colored version
                args.You = this.divYou(); // will replace ${You} with colored version
                if (args.reason) {
                    args.reason = "(" + this.getTokenName(args.reason) + ")";
                }
                var res = _super.prototype.bgaFormatText.call(this, log, args);
                log = res.log;
                args = res.args;
            }
        }
        catch (e) {
            console.error(log, args, "Exception thrown", e.stack);
        }
        return { log: log, args: args };
    };
    return GameXBody;
}(GameMachine));
var LaAnimations = /** @class */ (function () {
    function LaAnimations() {
        this.defaultAnimationDuration = 500;
    }
    LaAnimations.prototype.phantomMove = function (mobileId, newparentId, duration, mobileStyle, onEnd) {
        var _a, _b, _c;
        var mobileNode = $(mobileId);
        if (!mobileNode)
            throw new Error("Does not exists ".concat(mobileId));
        var newparent = $(newparentId);
        if (!newparent)
            throw new Error("Does not exists ".concat(newparentId));
        if (duration === undefined)
            duration = this.defaultAnimationDuration;
        if (!duration || duration < 0)
            duration = 0;
        var noanimation = duration <= 0 || !mobileNode.parentNode;
        var oldParent = mobileNode.parentElement;
        var clone = null;
        if (!noanimation) {
            // do animation
            clone = this.projectOnto(mobileNode, "_temp");
            mobileNode.style.opacity = "0"; // hide original
        }
        var rel = mobileStyle === null || mobileStyle === void 0 ? void 0 : mobileStyle.relation;
        if (rel) {
            delete mobileStyle.relation;
        }
        if (rel == "first") {
            newparent.insertBefore(mobileNode, null);
        }
        else {
            newparent.appendChild(mobileNode); // move original
        }
        setStyleAttributes(mobileNode, mobileStyle);
        newparent.classList.add("move_target");
        oldParent === null || oldParent === void 0 ? void 0 : oldParent.classList.add("move_source");
        mobileNode.offsetHeight; // recalc
        if (noanimation) {
            setTimeout(function () {
                newparent.offsetHeight;
                newparent.classList.remove("move_target");
                oldParent === null || oldParent === void 0 ? void 0 : oldParent.classList.remove("move_source");
                if (onEnd)
                    onEnd(mobileNode);
            }, 0);
            return;
        }
        var desti = this.projectOnto(mobileNode, "_temp2"); // invisible destination on top of new parent
        try {
            //setStyleAttributes(desti, mobileStyle);
            clone.style.transitionDuration = duration + "ms";
            clone.style.transitionProperty = "all";
            clone.style.visibility = "visible";
            clone.style.opacity = "1";
            // that will cause animation
            clone.style.left = desti.style.left;
            clone.style.top = desti.style.top;
            clone.style.transform = desti.style.transform;
            // now we don't need destination anymore
            (_a = desti.parentNode) === null || _a === void 0 ? void 0 : _a.removeChild(desti);
            setTimeout(function () {
                var _a;
                newparent.classList.remove("move_target");
                oldParent === null || oldParent === void 0 ? void 0 : oldParent.classList.remove("move_source");
                mobileNode.style.removeProperty("opacity"); // restore visibility of original
                (_a = clone.parentNode) === null || _a === void 0 ? void 0 : _a.removeChild(clone); // destroy clone
                if (onEnd)
                    onEnd(mobileNode);
            }, duration);
        }
        catch (e) {
            // if bad thing happen we have to clean up clones
            console.error("ERR:C01:animation error", e);
            (_b = desti.parentNode) === null || _b === void 0 ? void 0 : _b.removeChild(desti);
            (_c = clone.parentNode) === null || _c === void 0 ? void 0 : _c.removeChild(clone); // destroy clone
            //if (onEnd) onEnd(mobileNode);
        }
    };
    LaAnimations.prototype.getFulltransformMatrix = function (from, to) {
        var fullmatrix = "";
        var par = from;
        while (par != to && par != null && par != document.body) {
            var style = window.getComputedStyle(par);
            var matrix = style.transform; //|| "matrix(1,0,0,1,0,0)";
            if (matrix && matrix != "none")
                fullmatrix += " " + matrix;
            par = par.parentNode;
            // console.log("tranform  ",fullmatrix,par);
        }
        return fullmatrix;
    };
    LaAnimations.prototype.projectOnto = function (from, postfix, ontoWhat) {
        var elem = $(from);
        var over;
        if (ontoWhat)
            over = $(ontoWhat);
        else
            over = $("oversurface"); // this div has to exists with pointer-events: none and cover all area with high zIndex
        var elemRect = elem.getBoundingClientRect();
        //console.log("elemRect", elemRect);
        var newId = elem.id + postfix;
        var old = $(newId);
        if (old)
            old.parentNode.removeChild(old);
        var clone = elem.cloneNode(true);
        clone.id = newId;
        clone.classList.add("phantom");
        clone.classList.add("phantom" + postfix);
        clone.style.transitionDuration = "0ms"; // disable animation during projection
        if (elemRect.width > 1) {
            clone.style.width = elemRect.width + "px";
            clone.style.height = elemRect.height + "px";
        }
        var fullmatrix = this.getFulltransformMatrix(elem.parentNode, over.parentNode);
        over.appendChild(clone);
        var cloneRect = clone.getBoundingClientRect();
        var centerY = elemRect.y + elemRect.height / 2;
        var centerX = elemRect.x + elemRect.width / 2;
        // centerX/Y is where the center point must be
        // I need to calculate the offset from top and left
        // Therefore I remove half of the dimensions + the existing offset
        var offsetX = centerX - cloneRect.width / 2 - cloneRect.x;
        var offsetY = centerY - cloneRect.height / 2 - cloneRect.y;
        // Then remove the clone's parent position (since left/top is from tthe parent)
        //console.log("cloneRect", cloneRect);
        // @ts-ignore
        clone.style.left = offsetX + "px";
        clone.style.top = offsetY + "px";
        clone.style.transform = fullmatrix;
        clone.style.transitionDuration = undefined;
        return clone;
    };
    return LaAnimations;
}());
function setStyleAttributes(element, attrs) {
    if (attrs !== undefined) {
        Object.keys(attrs).forEach(function (key) {
            element.style.setProperty(key, attrs[key]);
        });
    }
}
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
    getLibUrl("bga-cards", "1.x"),
    getLibUrl("bga-score-sheet", "1.x")
], function (dojo, declare, gamegui, BgaAnimations, BgaCards, BgaScoreSheet) {
    window.BgaAnimations = BgaAnimations; //trick
    window.BgaCards = BgaCards;
    window.BgaScoreSheet = BgaScoreSheet;
    declare("bgagame.skarabrae", ebg.core.gamegui, new GameXBody());
});
