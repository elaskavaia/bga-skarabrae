/**
 * Interface that mimics token datatabase object
 */
interface Token {
  key: string;
  location: string;
  state: number;
}

interface TokenDisplayInfo {
  key: string; // token id
  tokenId: string; // original id of html node
  typeKey: string; // this is key in token_types structure
  mainType: string; // first type
  imageTypes: string; // all classes
  name?: string | NotificationMessage;
  tooltip?: string;
  showtooltip?: boolean;
  [key: string]: any;
}

interface TokenMoveInfo extends Token {
  x?: number;
  y?: number;
  position?: string;
  onEnd?: (node: Element) => void;
  onClick?: (event?: any) => void;
  animtime?: number;
  relation?: string;
  nop?: boolean;
  from?: string;
}

type StringProperties = { [key: string]: string };

class Game1Tokens extends Game0Basics {
  player_color: string;
  original_click_id: any;
  globlog: number = 1;
  tokenInfoCache: { [key: string]: TokenDisplayInfo } = {};

  defaultAnimationDuration: number = 500;

  classActiveSlot: string = "active_slot";
  classButtonDisabled: string = "disabled";
  classSelected: string = "gg_selected"; // for the purpose of multi-select operations
  classSelectedAlt: string = "gg_selected_alt"; // for the purpose of multi-select operations with alternative node
  game: Game1Tokens = this;
  animationManager: AnimationManager;

  setupGame(gamedatas: any): void {
    this.tokenInfoCache = {};

    // create the animation manager, and bind it to the `game.bgaAnimationsActive()` function
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bgaAnimationsActive()
    });

    const first_player_id = Object.keys(gamedatas.players)[0];
    if (!this.game.isSpectator) this.player_color = gamedatas.players[this.game.player_id].color;
    else this.player_color = gamedatas.players[first_player_id].color;
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
    this.placeToken("limbo");

    this.setupTokens();
  }

  onLeavingState(stateName: string): void {
    // console.log("onLeavingState: " + stateName);
    //this.disconnectAllTemp();
    this.removeAllClasses(this.classActiveSlot, "hidden_" + this.classActiveSlot);
    if (!this.on_client_state) {
      this.removeAllClasses(this.classSelected, this.classSelectedAlt);
    }
  }

  cancelLocalStateEffects() {
    //console.log(this.last_server_state);

    this.game.removeAllClasses(this.classActiveSlot, "hidden_" + this.classActiveSlot);
    this.game.removeAllClasses(this.classSelected, this.classSelectedAlt);
    //this.restoreServerData();
    //this.updateCountersSafe(this.gamedatas.counters);
  }

  getAllLocations() {
    const res = [];
    for (const key in this.gamedatas.token_types) {
      const info = this.gamedatas.token_types[key];
      if (this.isLocationByType(key) && info.scope != "player") res.push(key);
    }
    for (var token in this.gamedatas.tokens) {
      var tokenInfo = this.gamedatas.tokens[token];
      var location = tokenInfo.location;
      if (location && res.indexOf(location) < 0) res.push(location);
    }
    return res;
  }

  isLocationByType(id: string) {
    return this.hasType(id, "location");
  }

  hasType(id: string, type: string): boolean {
    const loc = this.getRulesFor(id, "type", "");
    const split = loc.split(" ");
    return split.indexOf(type) >= 0;
  }

  setupTokens() {
    console.log("Setup tokens");

    for (let loc of this.getAllLocations()) {
      this.placeToken(loc);
    }

    for (let token in this.gamedatas.tokens) {
      const tokenInfo = this.gamedatas.tokens[token];
      const location = tokenInfo.location;
      if (location && !this.gamedatas.tokens[location] && !$(location)) {
        this.placeToken(location);
      }
      this.placeToken(token);
    }

    for (let loc of this.getAllLocations()) {
      this.updateTooltip(loc);
    }
    for (let token in this.gamedatas.tokens) {
      this.updateTooltip(token);
    }
  }

  setTokenInfo(token_id: string, place_id?: string, new_state?: number, serverdata?: boolean, args?: any): Token {
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
  }

  setDomTokenState(tokenId: ElementOrId, newState: any) {
    var node = $(tokenId);
    // console.log(token + "|=>" + newState);
    if (!node) return;
    node.dataset.state = newState;
  }

  getDomTokenLocation(tokenId: ElementOrId) {
    return ($(tokenId).parentNode as HTMLElement).id;
  }

  getDomTokenState(tokenId: ElementOrId) {
    return parseInt(($(tokenId).parentNode as HTMLElement).getAttribute("data-state") || "0");
  }

  createToken(placeInfo: TokenMoveInfo) {
    const tokenId = placeInfo.key;
    const location = placeInfo.from ?? placeInfo.location ?? this.getRulesFor(tokenId, "location");

    const div = document.createElement("div");
    div.id = tokenId;

    let parentNode = $(location);

    if (location && !parentNode) {
      if (location.indexOf("{") == -1) console.error("Cannot find location [" + location + "] for ", div);
      parentNode = $("limbo");
    }
    if (parentNode) parentNode.appendChild(div);
    return div;
  }

  updateToken(tokenNode: HTMLElement, placeInfo: TokenMoveInfo) {
    const tokenId = placeInfo.key;
    const displayInfo = this.getTokenDisplayInfo(tokenId);
    const classes = displayInfo.imageTypes.split(/  */);
    tokenNode.classList.add(...classes);
    if (displayInfo.name) tokenNode.dataset.name = this.getTr(displayInfo.name);

    if (!tokenNode.getAttribute("_lis") && placeInfo.onClick) {
      tokenNode.addEventListener("click", placeInfo.onClick);
      tokenNode.setAttribute("_lis", "1");
    }
  }

  findActiveParent(element: HTMLElement): HTMLElement | null {
    if (this.isActiveSlot(element)) return element;
    const parent = element.parentElement;
    if (!parent || parent.id == "thething" || parent == element) return null;
    return this.findActiveParent(parent);
  }

  /**
   * This is convenient function to be called when processing click events, it - remembers id of object - stops propagation - logs to
   * console - the if checkActive is set to true check if element has active_slot class
   */
  onClickSanity(event: Event, checkActiveSlot?: boolean, checkActivePlayer?: boolean): string {
    let id = (event.currentTarget as HTMLElement).id;
    let target = event.target as HTMLElement;
    if (id == "thething") {
      let node = this.findActiveParent(target);
      id = node?.id;
    }

    console.log("on slot " + id, target?.id || target);
    if (!id) return null;
    if (this.showHelp(id)) return null;

    if (checkActiveSlot && !id.startsWith("button_") && !this.checkActiveSlot(id)) {
      return null;
    }
    if (checkActivePlayer && !this.checkActivePlayer()) {
      return null;
    }
    id = id.replace("tmp_", "");
    id = id.replace("button_", "");
    return id;
  }

  // override to hook the help
  showHelp(id: string) {
    return false;
  }

  // override to get token update "notification"
  onUpdateTokenInDom(tokenNode: HTMLElement, tokenInfo: Token, tokenInfoBefore: Token, animationDuration: number = 0) {
    return;
  }

  // override to prove additinal animation parameters
  getPlaceRedirect(tokenInfo: Token): TokenMoveInfo {
    return tokenInfo;
  }

  checkActivePlayer(): boolean {
    if (!this.game.isCurrentPlayerActive()) {
      this.game.showMessage(_("This is not your turn"), "error");
      return false;
    }
    return true;
  }
  isActiveSlot(id: ElementOrId): boolean {
    const node = $(id);
    if (node.classList.contains(this.classActiveSlot)) {
      return true;
    }
    if (node.classList.contains("hidden_" + this.classActiveSlot)) {
      return true;
    }

    return false;
  }
  checkActiveSlot(id: ElementOrId, showError: boolean = true) {
    if (!this.isActiveSlot(id)) {
      if (showError) {
        console.error(new Error("unauth"), id);
        this.game.showMoveUnauthorized();
      }
      return false;
    }
    return true;
  }

  placeTokenServer(tokenId: string, location: string, state?: number, args?: any) {
    const tokenInfo = this.setTokenInfo(tokenId, location, state, true, args);
    return this.placeTokenWithTips(tokenId, tokenInfo, args);
  }

  placeToken(token: string, tokenDbInfo?: Token, args?: any) {
    try {
      if (args === undefined) {
        args = {};
      }
      let noAnnimation = false;
      if (args.noa) {
        noAnnimation = true;
      }

      let tokenInfoBefore = args?._prev;

      if (!tokenDbInfo) {
        tokenDbInfo = this.gamedatas.tokens[token];
      }

      var tokenNode = $(token);
      if (!tokenDbInfo) {
        const rules = this.getAllRules(token);
        if (rules) tokenDbInfo = this.setTokenInfo(token, rules.location, rules.state, false);
        else tokenDbInfo = this.setTokenInfo(token, undefined, undefined, false);

        if (tokenNode) {
          tokenDbInfo = this.setTokenInfo(token, this.getDomTokenLocation(tokenNode), this.getDomTokenState(tokenNode), false);
        }
        noAnnimation = true;
      }
      if (!tokenDbInfo.location) {
        if (!token.startsWith("counter")) console.log(token + ": " + " -place-> undefined " + tokenDbInfo.state);
      }

      const placeInfo = args.placeInfo ?? this.getPlaceRedirect(tokenDbInfo);
      const location = placeInfo.location;

      //console.log(token + ": " + " -place-> " + location + " " + tokenInfo.state);

      //this.saveRestore(token);

      if (tokenNode == null) {
        //debugger;
        if (!placeInfo.from && args.place_from) placeInfo.from = args.place_from;
        tokenNode = this.createToken(placeInfo);
      }
      this.setDomTokenState(tokenNode, tokenDbInfo.state);
      tokenNode.dataset.location = tokenDbInfo.location;
      this.updateToken(tokenNode, placeInfo);

      if (placeInfo.nop) {
        // no movement
        return this.onUpdateTokenInDom(tokenNode, tokenDbInfo, tokenInfoBefore, 0);
      }
      if (!$(location)) {
        if (location) console.error("Unknown place '" + location + "' for '" + tokenDbInfo.key + "' " + token);
        return;
      }

      if (this.game.bgaAnimationsActive() == false || args.noa || placeInfo.animtime == 0) {
        noAnnimation = true;
      }
      if (!tokenNode.parentNode) noAnnimation = true;
      // console.log(token + ": " + tokenInfo.key + " -move-> " + place + " " + tokenInfo.state);

      let animTime: number;
      if (noAnnimation) animTime = 0;
      else animTime = placeInfo.animtime ?? this.defaultAnimationDuration;

      let mobileStyle = undefined;
      if (placeInfo.x !== undefined || placeInfo.y !== undefined) {
        mobileStyle = {
          position: placeInfo.position || "absolute",
          left: placeInfo.x + "px",
          top: placeInfo.y + "px"
        };
      }

      this.slideAndPlace(tokenNode, location, animTime, mobileStyle, placeInfo.onEnd);
      //if (animTime == 0) $(location).appendChild(tokenNode);
      //else void this.animationManager.slideAndAttach(tokenNode, $(location));
      void this.onUpdateTokenInDom(tokenNode, tokenDbInfo, tokenInfoBefore, animTime);
    } catch (e) {
      console.error("Exception thrown", e, e.stack);
      // this.showMessage(token + " -> FAILED -> " + place + "\n" + e, "error");
    }
    return tokenNode;
  }

  placeTokenWithTips(token: string, tokenInfo?: Token, args?: any) {
    if (!tokenInfo) {
      tokenInfo = this.gamedatas.tokens[token];
    }
    this.placeToken(token, tokenInfo, args);
    this.updateTooltip(token);
    if (tokenInfo) this.updateTooltip(tokenInfo.location);
  }

  updateTooltip(tokenId: string, attachTo?: ElementOrId, delay?: number) {
    if (attachTo === undefined) {
      attachTo = tokenId;
    }
    let attachNode = $(attachTo);

    if (!attachNode) return;

    // attach node has to have id
    if (!attachNode.id) attachNode.id = "gen_id_" + Math.random() * 10000000;

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
      if (attachNode.id != tokenId) attachNode.dataset.tt = tokenId; // id of token that provides the tooltip

      //console.log("addTooltipHtml", attachNode.id);
      this.game.addTooltipHtml(attachNode.id, main, delay ?? this.game.defaultTooltipDelay);
      attachNode.removeAttribute("title"); // unset title so both title and tooltip do not show up

      this.handleStackedTooltips(attachNode);
    } else {
      attachNode.classList.remove("withtooltip");
    }
  }

  handleStackedTooltips(attachNode: HTMLElement) {}

  getTooltipHtmlForToken(token: string) {
    if (typeof token != "string") {
      console.error("cannot calc tooltip" + token);
      return null;
    }
    var tokenInfo = this.getTokenDisplayInfo(token, true);
    // console.log(tokenInfo);
    if (!tokenInfo) return;
    return this.getTooltipHtmlForTokenInfo(tokenInfo);
  }

  getTooltipHtmlForTokenInfo(tokenInfo: TokenDisplayInfo) {
    return this.getTooltipHtml(tokenInfo.name, tokenInfo.tooltip, tokenInfo.imageTypes);
  }

  getTokenName(tokenId: string, force: boolean = true): string {
    var tokenInfo = this.getTokenDisplayInfo(tokenId);
    if (tokenInfo) {
      return this.game.getTr(tokenInfo.name);
    } else {
      if (!force) return undefined;
      return "? " + tokenId;
    }
  }

  getTooltipHtml(name: string | NotificationMessage, message: string | NotificationMessage, imgTypes?: string) {
    if (name == null || message == "-") return "";
    if (!message) message = "";
    var divImg = "";
    var containerType = "tooltipcontainer ";
    if (imgTypes) {
      divImg = `<div class='tooltipimage ${imgTypes}'></div>`;
      var itypes = imgTypes.split(" ");
      for (var i = 0; i < itypes.length; i++) {
        containerType += itypes[i] + "_tooltipcontainer ";
      }
    }
    const name_tr = this.game.getTr(name);

    let body: any = "";
    if (imgTypes.includes("_override")) {
      body = message;
    } else {
      const message_tr = this.game.getTr(message);
      body = `
           <div class='tooltip-left'>${divImg}</div>
           <div class='tooltip-right'>
             <div class='tooltiptitle'>${name_tr}</div>
             <div class='tooltiptext'>${message_tr}</div>
           </div>
    `;
    }

    return `<div class='${containerType}'>
        <div class='tooltip-body'>${body}</div>
    </div>`;
  }

  getTokenInfoState(tokenId: string) {
    var tokenInfo = this.gamedatas.tokens[tokenId];
    return parseInt(tokenInfo.state);
  }

  getAllRules(tokenId: string) {
    return this.getRulesFor(tokenId, "*", null);
  }

  getRulesFor(tokenId: string, field?: string, def?: any) {
    if (field === undefined) field = "r";
    var key = tokenId;
    let chain = [key];
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
      if (rule === undefined) return def;
      return rule;
    }
    return def;
  }

  getTokenDisplayInfo(tokenId: string, force: boolean = false): TokenDisplayInfo {
    tokenId = String(tokenId);
    const cache = this.tokenInfoCache[tokenId];
    if (!force && cache) {
      return cache;
    }
    let tokenInfo = this.getAllRules(tokenId);

    if (!tokenInfo) {
      tokenInfo = {
        key: tokenId,
        _chain: tokenId,
        name: tokenId,
        showtooltip: false
      };
    } else {
      tokenInfo = structuredClone(tokenInfo);
    }

    const imageTypes = tokenInfo._chain ?? tokenId ?? "";
    const ita = imageTypes.split(" ");
    const tokenKey = ita[ita.length - 1];
    const declaredTypes = tokenInfo.type || "token";

    tokenInfo.typeKey = tokenKey; // this is key in token_types structure
    tokenInfo.mainType = getPart(tokenId, 0); // first type
    tokenInfo.imageTypes = `${tokenInfo.mainType} ${declaredTypes} ${imageTypes}`.trim(); // other types used for div
    const create = tokenInfo.create;
    if (create == 3 || create == 4) {
      const prefix = tokenKey.split("_").length;
      tokenInfo.color = getPart(tokenId, prefix);
      tokenInfo.imageTypes += " color_" + tokenInfo.color;
    }

    if (create == 3) {
      const part = getPart(tokenId, -1);
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
  }

  getTokenPresentaton(type: string, tokenKey: string, args: any = {}): string {
    if (type.includes("_div")) return this.createTokenImage(tokenKey);
    return this.getTokenName(tokenKey); // just a name for now
  }
  // override to generate dynamic tooltips and such
  updateTokenDisplayInfo(tokenDisplayInfo: TokenDisplayInfo) {}

  createTokenImage(tokenId: string) {
    const div = document.createElement("div");
    div.id = tokenId + "_tt_" + this.globlog++;
    this.updateToken(div, { key: tokenId, location: "log", state: 0 });
    return div.outerHTML;
  }

  isMarkedForTranslation(key: string, args: any) {
    if (!args.i18n) {
      return false;
    } else {
      var i = args.i18n.indexOf(key);
      if (i >= 0) {
        return true;
      }
    }
    return false;
  }
  bgaFormatText(log: string, args: any) {
    if (log && args) {
      try {
        var keys = ["token_name", "token2_name", "token_divs", "token_names", "place_name", "token_div", "token2_div", "token3_div"];
        for (var i in keys) {
          const key = keys[i];
          // console.log("checking " + key + " for " + log);
          if (args[key] === undefined) continue;
          const arg_value = args[key];

          if (key == "token_divs" || key == "token_names") {
            var list = args[key].split(",");
            var res = "";
            for (let l = 0; l < list.length; l++) {
              const value = list[l];
              if (l > 0) res += ", ";
              res += this.getTokenPresentaton(key, value, args);
            }
            res = res.trim();
            if (res) args[key] = res;
            continue;
          }
          if (typeof arg_value == "string" && this.isMarkedForTranslation(key, args)) {
            continue;
          }
          var res = this.getTokenPresentaton(key, arg_value, args);
          if (res) args[key] = res;
        }
      } catch (e) {
        console.error(log, args, "Exception thrown", e.stack);
      }
    }
    return { log, args };
  }

  slideAndPlace(
    token: ElementOrId,
    finalPlace: ElementOrId,
    tlen?: number,
    mobileStyle?: StringProperties,
    onEnd?: (node?: HTMLElement) => void
  ) {
    if (!$(token)) console.error(`token not found for ${token}`);
    if ($(token)?.parentNode == $(finalPlace)) return;

    this.phantomMove(token, finalPlace, tlen, mobileStyle, onEnd);
  }

  getFulltransformMatrix(from: Element, to: Element) {
    let fullmatrix = "";
    let par = from;

    while (par != to && par != null && par != document.body) {
      var style = window.getComputedStyle(par as Element);
      var matrix = style.transform; //|| "matrix(1,0,0,1,0,0)";

      if (matrix && matrix != "none") fullmatrix += " " + matrix;
      par = par.parentNode as Element;
      // console.log("tranform  ",fullmatrix,par);
    }

    return fullmatrix;
  }

  projectOnto(from: ElementOrId, postfix: string, ontoWhat?: ElementOrId) {
    const elem: Element = $(from);
    let over: Element;
    if (ontoWhat) over = $(ontoWhat);
    else over = $("oversurface"); // this div has to exists with pointer-events: none and cover all area with high zIndex
    var elemRect = elem.getBoundingClientRect();

    //console.log("elemRect", elemRect);

    var newId = elem.id + postfix;
    var old = $(newId);
    if (old) old.parentNode.removeChild(old);

    var clone = elem.cloneNode(true) as HTMLElement;
    clone.id = newId;
    clone.classList.add("phantom");
    clone.classList.add("phantom" + postfix);
    clone.style.transitionDuration = "0ms"; // disable animation during projection
    if (elemRect.width > 1) {
      clone.style.width = elemRect.width + "px";
      clone.style.height = elemRect.height + "px";
    }

    var fullmatrix = this.getFulltransformMatrix(elem.parentNode as Element, over.parentNode as Element);

    over.appendChild(clone);
    var cloneRect = clone.getBoundingClientRect();

    const centerY = elemRect.y + elemRect.height / 2;
    const centerX = elemRect.x + elemRect.width / 2;
    // centerX/Y is where the center point must be
    // I need to calculate the offset from top and left
    // Therefore I remove half of the dimensions + the existing offset
    const offsetX = centerX - cloneRect.width / 2 - cloneRect.x;
    const offsetY = centerY - cloneRect.height / 2 - cloneRect.y;

    // Then remove the clone's parent position (since left/top is from tthe parent)
    //console.log("cloneRect", cloneRect);

    // @ts-ignore
    clone.style.left = offsetX + "px";
    clone.style.top = offsetY + "px";
    clone.style.transform = fullmatrix;
    clone.style.transitionDuration = undefined;

    return clone;
  }

  phantomMove(
    mobileId: ElementOrId,
    newparentId: ElementOrId,
    duration?: number,
    mobileStyle?: StringProperties,
    onEnd?: (node?: HTMLElement) => void
  ) {
    var mobileNode = $(mobileId) as HTMLElement;

    if (!mobileNode) throw new Error(`Does not exists ${mobileId}`);
    var newparent = $(newparentId);
    if (!newparent) throw new Error(`Does not exists ${newparentId}`);
    if (duration === undefined) duration = this.defaultAnimationDuration;
    if (!duration || duration < 0) duration = 0;
    const noanimation = duration <= 0 || !mobileNode.parentNode;
    const oldParent = mobileNode.parentElement;
    var clone = null;
    if (!noanimation) {
      // do animation
      clone = this.projectOnto(mobileNode, "_temp");
      mobileNode.style.opacity = "0"; // hide original
    }

    const rel = mobileStyle?.relation;
    if (rel) {
      delete mobileStyle.relation;
    }
    if (rel == "first") {
      newparent.insertBefore(mobileNode, null);
    } else {
      newparent.appendChild(mobileNode); // move original
    }

    setStyleAttributes(mobileNode, mobileStyle);
    newparent.classList.add("move_target");
    oldParent?.classList.add("move_source");
    mobileNode.offsetHeight; // recalc

    if (noanimation) {
      setTimeout(() => {
        newparent.offsetHeight;
        newparent.classList.remove("move_target");
        oldParent?.classList.remove("move_source");
        if (onEnd) onEnd(mobileNode);
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
      desti.parentNode?.removeChild(desti);
      setTimeout(() => {
        newparent.classList.remove("move_target");
        oldParent?.classList.remove("move_source");
        mobileNode.style.removeProperty("opacity"); // restore visibility of original
        clone.parentNode?.removeChild(clone); // destroy clone
        if (onEnd) onEnd(mobileNode);
      }, duration);
    } catch (e) {
      // if bad thing happen we have to clean up clones
      console.error("ERR:C01:animation error", e);
      desti.parentNode?.removeChild(desti);
      clone.parentNode?.removeChild(clone); // destroy clone
      //if (onEnd) onEnd(mobileNode);
    }
  }

  async notif_animate(args: any) {
    return this.game.wait(args.time ?? 1);
  }

  async notif_tokenMovedAsync(args: any) {
    void this.notif_tokenMoved(args);
  }

  async notif_tokenMoved(args: any) {
    if (args.list !== undefined) {
      // move bunch of tokens

      for (var i = 0; i < args.list.length; i++) {
        var one = args.list[i];
        var new_state = args.new_state;
        if (new_state === undefined) {
          if (args.new_states !== undefined && args.new_states.length > i) {
            new_state = args.new_states[i];
          }
        }
        this.placeTokenServer(one, args.place_id, new_state, args);
      }
      return this.game.wait(500);
    } else {
      this.placeTokenServer(args.token_id, args.place_id, args.new_state, args);
      return this.game.wait(500);
    }
  }
  async notif_counterAsync(args: any) {
    void this.notif_counter(args);
  }

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
  async notif_counter(args: any) {
    try {
      const name = args.name;
      const value = args.value;
      const node = $(name);

      if (node && this.gamedatas.tokens[name]) {
        args.nop = true; // no move animation
        this.placeTokenServer(name, this.gamedatas.tokens[name].location, value, args);
      } else if (node) {
        this.setDomTokenState(name, value);
      }
      console.log("** notif counter " + args.counter_name + " -> " + args.counter_value);
    } catch (ex) {
      console.error("Cannot update " + args.counter_name, ex, ex.stack);
    }
    return this.game.wait(500);
  }

  // HTML MANIPULATIONS
}
function setStyleAttributes(element: HTMLElement, attrs: { [key: string]: string }): void {
  if (attrs !== undefined) {
    Object.keys(attrs).forEach((key: string) => {
      element.style.setProperty(key, attrs[key]);
    });
  }
}
