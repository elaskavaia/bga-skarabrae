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

interface BasicParamInfo {
  q: number; // error code
  max?: number; // max count for this param
  err?: string | NotificationMessage; // error string if error code is set
  name?: string | NotificationMessage; // alternative param representation (can be rec tr)
  info?: ParamInfoArray; // param info for next argument
  sec?: boolean; // this is secondary target
  o?: number; //  priority order
}

interface ButtonParamInfo extends BasicParamInfo {
  color?: string; // button color
  // button?: string | NotificationMessage; // button name if different from name
}

type ParamInfo = ButtonParamInfo | BasicParamInfo;

interface ParamInfoArray {
  [key: string]: ParamInfo;
}

interface OpInfo {
  id: number;
  type: string; // operation type
  owner: string; // operation owner (color)
  data: any; // operation data

  ttype: string; // operation target type
  void: boolean; // operation is void
  target: string[]; // possible targets
  info: ParamInfoArray; // possible targets extra info

  confirm?: boolean; // require confirmation before sending to server
  description?: string; // for other players
  descriptionOnMyTurn?: string; // prompt when op is single/active
  subtitle?: string; // sub prompt when op is single/active (rended small subtext)

  err?: string | NotificationMessage; // error string or notification object XXX
  args: { [key: string]: any }; // other args for notifs
}

/**  Generic processing related to Operation Machine */
class GameMachine extends GameBasics {
  onEnteringState_auto(args: any) {
    if (args?.description && !this.isCurrentPlayerActive()) {
      this.statusBar.setTitle(args.description, args.args);
    }
  }
  onUpdateActionButtons_PlayerTurn(args: OpInfo) {
    this.completeOpInfo(args);
    if (args.descriptionOnMyTurn) {
      this.statusBar.setTitle(args.descriptionOnMyTurn, args.args);
    }

    for (const target of args.target) {
      const paramInfo = args.info[target];
      this.statusBar.addActionButton(this.getTr(paramInfo.name), () => this.resolveAction({ target }));
    }
    for (const target in args.info) {
      const paramInfo = args.info[target];
      if (paramInfo.sec) {
        // skip, whatever TODO: anytime
        this.statusBar.addActionButton(this.getTr(paramInfo.name), () => this.bgaPerformAction(`action_${target}`, {}));
      }
    }

    // need a global condition when this can be added
    this.statusBar.addActionButton(_("Undo"), () => this.bgaPerformAction("action_undo"), { color: "alert" });
  }

  onUpdateActionButtons_PlayerTurnConfirm(args: any) {
    this.statusBar.addActionButton(_("Confirm"), () => this.resolveAction());

    this.statusBar.addActionButton(_("Undo"), () => this.bgaPerformAction("action_undo"), { color: "alert" });
  }

  resolveAction(args: any = {}) {
    this.bgaPerformAction("action_resolve", {
      data: JSON.stringify(args)
    });
  }

  completeOpInfo(opInfo: OpInfo) {
    try {
      // server may skip sending some data, this will feel all omitted fields

      if (!opInfo.args) opInfo.args = [];
      if (opInfo.data?.count !== undefined) opInfo.args.count = parseInt(opInfo.data.count);
      if (opInfo.void === undefined) opInfo.void = false;
      opInfo.confirm = opInfo.confirm ?? false;

      if (!opInfo.info) opInfo.info = {};
      if (!opInfo.target) opInfo.target = [];

      const infokeys = Object.keys(opInfo.info);
      if (infokeys.length == 0 && opInfo.target.length > 0) {
        opInfo.target.forEach((element) => {
          opInfo.info[element] = { q: 0 };
        });
      } else if (infokeys.length > 0 && opInfo.target.length == 0) {
        infokeys.forEach((element) => {
          if (opInfo.info[element].q == 0) opInfo.target.push(element);
        });
      }

      // set default order
      let i = 1;
      for (const target of opInfo.target) {
        const paramInfo = opInfo.info[target];
        if (!paramInfo.o) paramInfo.o = i;
        i++;
      }
    } catch (e) {
      console.error(e);
    }
  }
}
