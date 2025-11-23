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
class GameMachine extends Game1Tokens {
  onEnteringState_PlayerTurn(args: OpInfo) {
    if (!this.isCurrentPlayerActive()) {
      if (args?.description) this.statusBar.setTitle(args.description, args);
      return;
    }
    this.completeOpInfo(args);
    if (args.descriptionOnMyTurn) {
      this.statusBar.setTitle(args.descriptionOnMyTurn, args);
    }

    const sortedTargets = Object.keys(args.info);
    sortedTargets.sort((a, b) => args.info[a].o - args.info[b].o);

    for (const target of sortedTargets) {
      const paramInfo = args.info[target];
      const div = $(target);
      const q = paramInfo.q;
      const active = q == 0;
      let name = paramInfo.name;
      if (div) {
        if (active) div.classList?.add(this.classActiveSlot);
        if (!name) name = div.dataset.name;
      }
      if (!name) name = target;
      let handler: any;
      if (paramInfo.sec) {
        // skip, whatever TODO: anytime
        handler = () => this.bgaPerformAction(`action_${target}`, {});
      } else {
        handler = () => this.resolveAction({ target });
      }
      const button = this.statusBar.addActionButton(this.getTr(name), handler, {
        color: active ? "primary" : "alert",
        disabled: !active
      });
      if (!active) {
        button.title = this.getTr(paramInfo.err ?? _("Operation cannot be performed now"));
      }
    }

    // need a global condition when this can be added
    this.addUndoButton();
  }

  onLeavingState(stateName: string): void {
    super.onLeavingState(stateName);
    $("button_undo")?.remove();
  }

  /** default click processor */
  onToken(event: Event, fromMethod?: string) {
    console.log(event);
    let id: string = this.onClickSanity(event);
    if (!id) return true;
    if (!fromMethod) fromMethod = "onToken";
    event.stopPropagation();
    event.preventDefault();
    var methodName = fromMethod + "_" + this.getStateName();
    let ret = this.callfn(methodName, id);
    if (ret === undefined) return false;
    return true;
  }

  onToken_PlayerTurn(tid: string) {
    //debugger;
    if (!tid) return false;
    this.resolveAction({ target: tid });
  }

  onUpdateActionButtons_PlayerTurnConfirm(args: any) {
    this.statusBar.addActionButton(_("Confirm"), () => this.resolveAction());

    this.addUndoButton();
  }

  resolveAction(args: any = {}) {
    this.bgaPerformAction("action_resolve", {
      data: JSON.stringify(args)
    });
  }

  addUndoButton() {
    if (!$("button_undo") && !this.isSpectator && this.isCurrentPlayerActive()) {
      const div = this.statusBar.addActionButton(_("Undo"), () => this.bgaPerformAction("action_undo"), {
        color: "alert",
        id: "button_undo"
      });
      div.classList.add("button_undo");
      div.title = _("Undo all possible steps");
      $("undoredo_wrap")?.appendChild(div);

      // const div2 = this.addActionButtonColor("button_undo_last", _("Undo"), () => this.sendActionUndo(-1), "red");
      // div2.classList.add("button_undo");
      // div2.title = _("Undo One Step");
      // $("undoredo_wrap")?.appendChild(div2);
    }
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

      if (opInfo.info.confirm && !opInfo.info.confirm.name) {
        opInfo.info.confirm.name = _("Confirm");
      }
      if (opInfo.info.skip && !opInfo.info.skip.name) {
        opInfo.info.skip.name = _("Skip");
      }
    } catch (e) {
      console.error(e);
    }
  }
}
