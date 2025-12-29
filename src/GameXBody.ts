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
class GameXBody extends GameMachine {
  private scoreSheet: any;
  private inSetup = true;
  readonly gameTemplate = `
<div id="thething">

<div id="round_banner">
  <span id='tracker_nrounds'> </span>
  <span id='tracker_nturns'> </span>
  <span id='round_banner_text'></span>
</div>
<div id='selection_area' class='selection_area'></div>
<div id="game-score-sheet"></div>
<div id='tasks_area' class='tasks_area'></div>
<div id="players_panels"></div>
<div id="mainarea">
 <div id="turnover" class="turnover">
    <div id="turndisk" class="turndisk"></div>
 </div>

 <div id="cardset_1" class="cardset cardset_1"></div>
 <div id="cardset_2" class="cardset cardset_2"></div>
 <div id="cardset_3" class="cardset cardset_3"></div>
 <div id="discard_village" class="discard village"></div>
 <div id="deck_village" class="deck village"></div>
 <div id="deck_roof" class="deck roof"></div>
</div>

</div>

`;
  setup(gamedatas) {
    try {
      super.setup(gamedatas);

      placeHtml(this.gameTemplate, this.bga.gameArea.getElement());
      // Setting up player boards
      for (const playerId of gamedatas.playerorder) {
        const playerInfo = gamedatas.players[playerId];
        this.setupPlayer(playerInfo);
      }

      super.setupGame(gamedatas);

      this.setupNotifications();
      this.setupScoreSheet();
      //this.updateTooltip("deck_village");
      if (gamedatas.gameEnded) {
        $("round_banner").innerHTML = _("Game Over");
        //this.bga.gameArea.addLastTurnBanner(_("Game is ended"));
      } else {
        if (gamedatas.tokens.tracker_nrounds.state == 4 && gamedatas.tokens.tracker_nturns.state == 3) {
          $("round_banner_text").innerHTML = _("This is Last Turn of Last Round");
        }
      }
    } catch (e) {
      console.error("Exception during game setup", e.stack);
    }

    console.log("Ending game setup");
    this.inSetup = false;
  }
  setupPlayer(playerInfo: any) {
    console.log("player info " + playerInfo.id, playerInfo);
    const pp = `player_panel_content_${playerInfo.color}`;
    document.querySelectorAll(`#${pp}>.miniboard`).forEach((node) => node.remove());
    placeHtml(`<div id='miniboard_${playerInfo.color}' class='miniboard'></div>`, pp);
    placeHtml(
      `
      <div id='tableau_${playerInfo.color}' class='tableau' data-player-name='${playerInfo.name}' style='--player-color: #${playerInfo.color}'>
        <div class='pboard_area'>
           <div id='pboard_${playerInfo.color}' class='pboard'>
                 <div id='track_furnish_${playerInfo.color}' class='track_furnish track'></div>
                 <div id='track_trade_${playerInfo.color}' class='track_trade track'></div>
                 <div id='breakroom_${playerInfo.color}' class='breakroom'></div>
                 <div id='storage_${playerInfo.color}' class='storage'></div>
           </div>
           <div id='cards_area_${playerInfo.color}' class='cards_area'>
           </div>
         </div>
         <div class='village_area'>
            <div id='action_area_${playerInfo.color}' class='action_area'></div>
            <div id='settlers_area_${playerInfo.color}' class='settlers_area'>
               <div id='settlers_col_${playerInfo.color}_1' class='settlers_col_1'></div>
               <div id='settlers_col_${playerInfo.color}_2' class='settlers_col_2'></div>
               <div id='settlers_col_${playerInfo.color}_3' class='settlers_col_3'></div>
               <div id='settlers_col_${playerInfo.color}_4' class='settlers_col_4'></div>
            </div>
         </div>
      </div>`,
      "players_panels"
    );

    for (let i = 0; i <= 6; i++) {
      placeHtml(
        `<div id='slot_furnish_${i}_${playerInfo.color}' class='slot_furnish slot_furnish_${i}'></div>`,
        `track_furnish_${playerInfo.color}`
      );
    }
    for (let i = 0; i <= 7; i++) {
      placeHtml(
        `<div id='slot_trade_${i}_${playerInfo.color}' class='slot_trade slot_trade_${i}'></div>`,
        `track_trade_${playerInfo.color}`
      );
    }
  }

  setupScoreSheet() {
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
    const entries = [
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
    this.scoreSheet = new BgaScoreSheet.ScoreSheet(document.getElementById(`game-score-sheet`), {
      animationsActive: () => this.gameAnimationsActive(),
      playerNameWidth: 80,
      playerNameHeight: 30,
      entryLabelWidth: 180,
      entryLabelHeight: 20,
      classes: "score-sheet",
      players: this.gamedatas.players,
      entries,
      scores: this.gamedatas.endScores,
      onScoreDisplayed: (property, playerId, score) => {
        // if (property === "total") {
        //   gameui.scoreCtrl[playerId].setValue(score);
        // }
      }
    });
  }

  onEnteringState_PlayerTurn(opInfo: OpInfo) {
    super.onEnteringState_PlayerTurn(opInfo);
    switch (opInfo.type) {
      case "turn":
        const div = $("turnover");
        const clone = div.cloneNode(true) as HTMLElement;
        clone.querySelectorAll("*").forEach((x) => (x.id = x.id + "_temp"));
        clone.id = clone.id + "_temp";
        $("selection_area").appendChild(clone);
        break;
      case "act":
        //if ((opInfo as any).turn == 3) this.bga.gameArea.addLastTurnBanner(_("This is the last turn before you need to feed the settlers"));
        break;
    }
  }

  onLeavingState_PlayerTurn() {
    const opInfo = this.opInfo;
    if (opInfo?.ui?.replicate) {
      $("selection_area")
        .querySelectorAll("& > *")
        .forEach((element) => {
          element.remove();
        });
    }
  }

  showHelp(id: string) {
    return false;
  }

  hideCard(tokenId: ElementOrId) {
    $("limbo")?.appendChild($(tokenId));
  }

  getPlaceRedirect(tokenInfo: Token, args: AnimArgs = {}): TokenMoveInfo {
    const location = tokenInfo.location ?? "limbo";
    const tokenId = tokenInfo.key;
    const result: TokenMoveInfo = {
      location: location,
      key: tokenId,
      state: tokenInfo.state
    };
    if (args.place_from) result.place_from = args.place_from;
    if (args.inc) result.inc = args.inc;
    if (!this.gameAnimationsActive()) {
      result.animtime = 0;
    }

    if (tokenId.startsWith("action") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `action_area_${color}`;
      result.onClick = (x) => this.onToken(x);
    } else if (tokenId.startsWith("action") && location.startsWith("hand")) {
      const color = getPart(location, 1);
      result.location = `selection_area`;
      result.onClick = (x) => this.onToken(x);
    } else if (tokenId.startsWith("card")) {
      result.onClick = (x) => this.onToken(x);
      if (tokenId.startsWith("card_setl") && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        const t = this.getRulesFor(tokenId, "t");
        result.location = `settlers_col_${color}_${t}`;
      } else if ((tokenId.startsWith("card_task") || tokenId.startsWith("card_goal")) && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        result.location = `tasks_area`;
        result.onClick = (x) => this.onToken(x);
      } else if (location.startsWith("hand")) {
        const color = getPart(location, 1);
        result.location = `selection_area`;
        result.onClick = (x) => this.onToken(x);
      } else if (tokenId.startsWith("card") && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        result.location = `cards_area_${color}`;
      }
    } else if (location.startsWith("discard")) {
      //result.onEnd = (node) => this.hideCard(node);
    } else if (location.startsWith("deck")) {
      result.onEnd = (node) => this.hideCard(node);
    } else if (tokenId.startsWith("tableau")) {
      result.nop = true;
    } else if (tokenId.startsWith("hand")) {
      result.nop = true;
    } else if (tokenId.startsWith("slot") || tokenId == "round_banner") {
      result.nop = true; // do not move slots
    } else if (tokenId.startsWith("tracker_slider")) {
      const color = getPart(location, 1);
      result.location = `pboard_${color}`;
    } else if (tokenId.startsWith("tracker")) {
      if (this.getRulesFor(tokenId, "s") == 1) {
        result.onStart = async () => {
          return this.syncStorage(result);
        };
      }
      if (tokenId == "tracker_nturns" || tokenId == "tracker_nrounds") {
        result.nop = true;
      }
    } else if (location.startsWith("miniboard") && $(tokenId)) {
      result.nop = true; // do not move
    } else if (tokenId.startsWith("worker") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `breakroom_${color}`;
    }
    return result;
  }
  async syncStorage(result: TokenMoveInfo) {
    //console.log("storage anim", result);
    const tokenId = result.key;
    const tokenNode = $(result.key);
    let count = result.state;

    const color = getPart(tokenId, 2);
    const promisses = [];
    let placeFrom: string = tokenId;
    if (result.place_from) {
      if (!$(result.place_from)) {
        console.error("missing location " + placeFrom);
      } else {
        placeFrom = result.place_from;
      }
    }
    for (let i = 0; i < count; i++) {
      const item = `item_${tokenId}_${i}`;
      const itemNode = $(item);
      if (!itemNode) {
        let targetLoc = `storage_${color}`;
        const div = document.createElement("div");
        div.id = item;
        this.updateToken(div, { key: tokenId, location: placeFrom, state: 0 });
        div.title = this.getTokenName(tokenId);
        if (this.gameAnimationsActive()) {
          $(placeFrom).appendChild(div);
          promisses.push(this.slideAndPlace(item, targetLoc, 500, i * 100));
        } else {
          $(targetLoc).appendChild(div);
        }
      }
    }
    let i = count;
    while (i < 100) {
      const itemNode = $(`item_${tokenId}_${i}`);
      if (itemNode) {
        // remove

        if (this.gameAnimationsActive()) {
          promisses.push(this.slideAndPlace(itemNode, placeFrom, 500, i * 100, undefined, () => itemNode.remove()));
        } else {
          itemNode.remove();
        }
      }
      i++;
    }
    await Promise.allSettled(promisses);
  }

  gameAnimationsActive() {
    return gameui.bgaAnimationsActive() && !this.inSetup;
  }

  updateTokenDisplayInfo(tokenInfo: TokenDisplayInfo) {
    // override to generate dynamic tooltips and such
    const mainType = tokenInfo.mainType;
    const token = $(tokenInfo.tokenId);
    const parentId = token?.parentElement?.id;
    const state = parseInt(token?.dataset.state);
    switch (mainType) {
      case "worker":
        {
          const tokenId = tokenInfo.key;
          const name = tokenInfo.name;
          tokenInfo.tooltip = {
            log: "${name} (${color_name})",
            args: {
              name: this.getTr(name),
              color_name: this.getTr(this.getColorName(getPart(tokenId, 2)))
            }
          };
        }
        return;
      case "slot":
        {
          const tokenId = tokenInfo.key;
          const slotNum = getPart(tokenId, 2);
          let name = tokenInfo.name ?? _("Slot") + " #" + slotNum;

          if (tokenId.startsWith("slot_furnish")) {
            name = _("Furnish Slot") + " #" + slotNum;
          }
          tokenInfo.tooltip += "tbd";
          tokenInfo.name = name;
        }
        return;
      case "card":
        {
          const tokenId = tokenInfo.key;
          const name = tokenInfo.name;
          const tooltip = tokenInfo.tooltip;
          if (tokenId.startsWith("card_setl")) {
            tokenInfo.tooltip = _("When gaining this card you must resolve top harvest and you may resolve bottom effect");
            tokenInfo.tooltip += this.ttSection(_("Environment"), this.getTokenName(`env_${tokenInfo.t}`));
            tokenInfo.tooltip += this.ttSection(_("Bottom Effect"), tooltip as string);
          } else if (tokenId.startsWith("card_ball")) {
            tokenInfo.tooltip = _("Gain skaill knife for each Stone Ball you have");
          } else if (tokenId.startsWith("card_spin")) {
            tokenInfo.tooltip = _("Gain wool for each Spindle you have");
          } else if (tokenId.startsWith("card_roof")) {
            tokenInfo.tooltip = _(
              "No immediate effect. Provides a Roof during end of round. Each roof reduces amount of food you need to pay by one"
            );
          } else if (tokenId.startsWith("card_util")) {
            tokenInfo.tooltip = _("Gain Hide. Increase your Hearth by one. Decrease you Midden production by one");
          } else if (tokenId.startsWith("card_goal")) {
            tokenInfo.tooltip += this.ttSection(
              undefined,
              _("If you have NOT met the condition shown on the Focus Card, you lose 5VP. Condition evaluated at the end of the game")
            );
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
  }

  ttSection(prefix: string, text: string) {
    if (prefix) return `<p><b>${prefix}</b>: ${text}</p>`;
    else return `<p>${text}</p>`;
  }

  getColorName(color: string) {
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
  }

  setupNotifications() {
    console.log("notifications subscriptions setup");

    // automatically listen to the notifications, based on the `notif_xxx` function on this class.
    this.bgaSetupPromiseNotifications({
      minDuration: 1,
      minDurationNoText: 1,

      logger: console.log, // show notif debug informations on console. Could be console.warn or any custom debug function (default null = no logs)
      //handlers: [this, this.tokens],
      onStart: (notifName, msg, args) => this.setSubPrompt(msg, args)
      // onEnd: (notifName, msg, args) => this.setSubPrompt("", args)
    });
  }
  async notif_message(args: any) {
    //console.log("notif", args);
    return this.wait(10);
  }

  async notif_endScores(args: any) {
    // setting scores will make the score sheet visible if it isn't already
    if (args.final) {
      $("round_banner").innerHTML = _("Game Over");
    }
    await this.scoreSheet.setScores(args.endScores, {
      startBy: this.bga.players.getCurrentPlayerId()
    });
  }
  /** @Override */
  bgaFormatText(log: string, args: any) {
    try {
      if (log && args && !args.processed) {
        args.processed = true;

        if (!args.player_id) {
          args.player_id = this.bga.players.getActivePlayerId();
        }
        if (args.player_id && !args.player_name) {
          args.player_name = this.gamedatas.players[args.player_id].name;
        }

        if (args.you) args.you = this.divYou(); // will replace ${you} with colored version
        args.You = this.divYou(); // will replace ${You} with colored version

        if (args.reason) {
          args.reason = "(" + this.getTokenName(args.reason) + ")";
        }
        const res = super.bgaFormatText(log, args);
        log = res.log;
        args = res.args;
      }
    } catch (e) {
      console.error(log, args, "Exception thrown", e.stack);
    }
    return { log, args };
  }
}
