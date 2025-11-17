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
  setup(gamedatas) {
    super.setup(gamedatas);
    //super.setup(gamedatas);

    this.getGameAreaElement().insertAdjacentHTML(
      "beforeend",
      ` 
<div id="thething">
  <div class="whiteblock cow">${_("Should we eat the cow now?")}</div>
</div>
      `
    );

    this.setupNotifications();
    console.log("Ending game setup");
  }

  setupNotifications() {
    console.log("notifications subscriptions setup");

    // automatically listen to the notifications, based on the `notif_xxx` function on this class.
    this.bgaSetupPromiseNotifications({
      minDuration: 1,
      minDurationNoText: 1,

      logger: console.log, // show notif debug informations on console. Could be console.warn or any custom debug function (default null = no logs)

      onStart: (notifName, msg, args) => this.statusBar.setTitle(msg, args),
      onEnd: (notifName, msg, args) => this.statusBar.setTitle("", args)
    });
  }
  async notif_message(args: any) {
    //console.log("notif", args);
    return this.wait(10);
  }
}
