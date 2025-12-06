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
  readonly gameTemplate = `
<div id="thething">
<div id="players_panels"></div>
<div id="mainarea">
 <div id="cardset_1" class="cardset cardset_1"></div>
 <div id="cardset_2" class="cardset cardset_2"></div>
 <div id="cardset_3" class="cardset cardset_3"></div>
 <div id="discard_village" class="discard village"></div>
 <div id="deck_village" class="deck village"></div>
</div>
</div>

`;
  setup(gamedatas) {
    super.setup(gamedatas);

    placeHtml(this.gameTemplate, this.getGameAreaElement());
    // Setting up player boards
    for (const playerId of gamedatas.playerorder) {
      const playerInfo = gamedatas.players[playerId];
      this.setupPlayer(playerInfo);
    }

    // if (this.isSolo()) {
    //   const playerInfo = gamedatas.players[1];
    //   this.setupPlayer(playerInfo);
    // }
    super.setupGame(gamedatas);

    this.setupNotifications();
    console.log("Ending game setup");
  }
  setupPlayer(playerInfo: any) {
    console.log("player info " + playerInfo.id, playerInfo);
    const pp = `player_panel_content_${playerInfo.color}`;
    document.querySelectorAll(`#${pp}>.miniboard`).forEach((node) => node.remove());
    placeHtml(`<div id='miniboard_${playerInfo.color}' class='miniboard'></div>`, pp);
    placeHtml(
      `
      <div id='tableau_${playerInfo.color}' class='tableau'>
         <div id='pboard_${playerInfo.color}' class='pboard'>
                 <div id='track_furnish_${playerInfo.color}' class='track_furnish track'></div>
                 <div id='track_trade_${playerInfo.color}' class='track_trade track'></div>
                 <div id='breakroom_${playerInfo.color}' class='breakroom'></div>
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

  showHelp(id: string) {
    return false;
  }

  updateTokenDisplayInfo(tokenDisplayInfo: TokenDisplayInfo) {
    // override to generate dynamic tooltips and such
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

    if (tokenId.startsWith("action") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `action_area_${color}`;
      result.onClick = (x) => this.onToken(x);
    } else if (tokenId.startsWith("card")) {
      result.onClick = (x) => this.onToken(x);
      if (tokenId.startsWith("card_setl") && location.startsWith("tableau")) {
        const color = getPart(location, 1);
        result.location = `settlers_col_${color}_1`;
      }
    } else if (location.startsWith("discard")) {
      result.onEnd = (node) => this.hideCard(node);
    } else if (location.startsWith("deck")) {
      result.onEnd = (node) => this.hideCard(node);
    } else if (tokenId.startsWith("slot")) {
      result.nop = true; // do not move slots
    } else if (location.startsWith("miniboard") && $(tokenId)) {
      result.nop = true; // do not move
    } else if (tokenId.startsWith("worker") && location.startsWith("tableau")) {
      const color = getPart(location, 1);
      result.location = `breakroom_${color}`;
    }
    return result;
  }

  setupNotifications() {
    console.log("notifications subscriptions setup");

    // automatically listen to the notifications, based on the `notif_xxx` function on this class.
    this.bgaSetupPromiseNotifications({
      minDuration: 1,
      minDurationNoText: 1,

      logger: console.log, // show notif debug informations on console. Could be console.warn or any custom debug function (default null = no logs)
      //handlers: [this, this.tokens],
      onStart: (notifName, msg, args) => this.statusBar.setTitle(msg, args),
      onEnd: (notifName, msg, args) => this.statusBar.setTitle("", args)
    });
  }
  async notif_message(args: any) {
    //console.log("notif", args);
    return this.wait(10);
  }
  /** @Override */
  bgaFormatText(log: string, args: any) {
    if (log && args && !args.processed) {
      args.processed = true;
      try {
        if (!args.player_id) {
          args.player_id = this.getActivePlayerId();
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
      } catch (e) {
        console.error(log, args, "Exception thrown", e.stack);
      }
    }
    return { log, args };
  }
}
