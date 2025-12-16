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
  color?: string; // button color
  args?: any;
}

type ParamInfo = BasicParamInfo;

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

  count?: number;
  mcount?: number;
  ui: {
    buttons?: boolean;
  };
}

/**  Generic processing related to Operation Machine */
class GameMachine extends Game1Tokens {
  opInfo: OpInfo;
  onEnteringState_PlayerTurn(opInfo: OpInfo) {
    if (!this.bga.players.isCurrentPlayerActive()) {
      if (opInfo?.description) this.statusBar.setTitle(opInfo.description, opInfo);
      return;
    }
    this.completeOpInfo(opInfo);
    this.opInfo = opInfo;
    if (opInfo.descriptionOnMyTurn) {
      this.statusBar.setTitle(opInfo.descriptionOnMyTurn, opInfo);
    }
    this.setSubPrompt(opInfo.subtitle, opInfo);
    if (opInfo.err) {
      const button = this.statusBar.addActionButton(this.getTr(opInfo.err, opInfo), () => {}, {
        color: "alert",
        id: "button_err"
      });
    }
    const multiselect = this.isMultiSelectArgs(opInfo);

    const sortedTargets = Object.keys(opInfo.info);
    sortedTargets.sort((a, b) => opInfo.info[a].o - opInfo.info[b].o);

    for (const target of sortedTargets) {
      const paramInfo = opInfo.info[target];
      if (paramInfo.sec) {
        continue; // secondary buttons
      }
      const div = $(target);
      const q = paramInfo.q;
      const active = q == 0;

      if (div && active) {
        div.classList?.add(this.classActiveSlot);
        div.dataset.targetOpType = opInfo.type;
      }
      if (opInfo.ui.buttons == false && div) {
        continue;
      }

      let handler: any;
      if (multiselect) {
        handler = () => this.onMultiCount(target, opInfo);
      } else {
        handler = () => this.resolveAction({ target });
      }
      const color: any = paramInfo.color ?? (multiselect ? "secondary" : "primary");
      const button = this.statusBar.addActionButton(this.getParamPresentation(target, paramInfo), handler, {
        color: color,
        disabled: !active,
        id: "button_" + target
      });
      button.dataset.targetId = target;
      button.dataset.targetOpType = opInfo.type;

      if (paramInfo.max !== undefined) {
        button.dataset.max = String(paramInfo.max);
      } else {
        button.dataset.max = "1";
      }
      if (!active) {
        button.title = this.getTr(paramInfo.err ?? _("Operation cannot be performed now"), paramInfo.args);
      } else {
        if (paramInfo.args.tooltip) button.title = this.getTr(paramInfo.args.tooltip, paramInfo.args);
      }
    }

    // secondary buttons
    for (const target of sortedTargets) {
      const paramInfo = opInfo.info[target];
      if (paramInfo.sec) {
        // skip, whatever TODO: anytime
        const color: any = paramInfo.color ?? "secondary";
        const call = (paramInfo as any).call ?? target;
        const button = this.statusBar.addActionButton(
          this.getParamPresentation(target, paramInfo),
          () =>
            this.bga.actions.performAction(`action_${call}`, {
              data: JSON.stringify({ target })
            }),
          {
            color: color,
            id: "button_" + target
          }
        );
        button.dataset.targetId = target;
      }
    }
    if (opInfo.ui.buttons == false) {
      this.addShowMeButton(true);
    }
    if (multiselect) {
      this.activateMultiSelectPrompt(opInfo);
    }

    // need a global condition when this can be added
    this.addUndoButton();
  }

  getParamPresentation(target: string, paramInfo: ParamInfo) {
    const div = $(target);
    const q = paramInfo.q;
    let name = paramInfo.name;
    if (!name && div) {
      name = div.dataset.name;
    }
    if (!name) name = target;
    if (!paramInfo.args) {
      paramInfo.args = {};
    }
    return this.getTr(name, paramInfo.args ?? paramInfo);
  }

  isMultiSelectArgs(args: OpInfo) {
    return args.ttype == "token_count" || args.ttype == "token_array";
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
    if ($(tid).classList.contains(this.classActiveSlot)) {
      const ttype = this.opInfo?.ttype;
      if (ttype) {
        var methodName = "onToken_" + ttype;
        let ret = this.callfn(methodName, tid);
        if (ret === undefined) return false;
        return true;
      }
      return false;
    } else {
      // propagate to parent
      return this.onToken_PlayerTurn(($(tid).parentNode as HTMLElement)?.id);
    }
  }

  onToken_token(tid: string) {
    if (!tid) return false;
    this.resolveAction({ target: tid });
  }

  activateMultiSelectPrompt(opInfo: OpInfo) {
    const ttype = opInfo.ttype;

    const buttonName = _("Submit");
    const doneButtonId = "button_done";
    const resetButtonId = "button_reset";

    this.statusBar.addActionButton(
      buttonName,
      () => {
        const res = {};
        const count = this.getMultiSelectCountAndSync(res);
        if (opInfo.ttype == "token_count") {
          this.resolveAction({ target: res, count });
        } else {
          this.resolveAction({ target: Object.keys(res), count });
        }
      },
      {
        color: "primary",
        id: doneButtonId
      }
    );
    this.statusBar.addActionButton(
      _("Reset"),
      () => {
        const allSel = document.querySelectorAll(`.${this.classSelectedAlt}`);
        allSel.forEach((node: HTMLElement) => {
          delete node.dataset.count;
        });
        this.removeAllClasses(this.classSelected, this.classSelectedAlt);
        this.onMultiSelectionUpdate(opInfo);
      },
      {
        color: "alert",
        id: resetButtonId
      }
    );

    // this.replicateTokensOnToolbar(opInfo, (target) => {
    //   return this.onMultiCount(target, opInfo);
    // });

    this.onMultiSelectionUpdate(opInfo);

    this[`onToken_${ttype}`] = (tid: string) => {
      return this.onMultiCount(tid, opInfo);
    };
  }

  onUpdateActionButtons_PlayerTurnConfirm(args: any) {
    this.statusBar.addActionButton(_("Confirm"), () => this.resolveAction());

    this.addUndoButton();
  }

  resolveAction(args: any = {}) {
    this.bga.actions.performAction("action_resolve", {
      data: JSON.stringify(args)
    });
  }

  addUndoButton() {
    if (!$("button_undo") && !this.bga.players.isCurrentPlayerSpectator() && this.bga.players.isCurrentPlayerActive()) {
      const div = this.statusBar.addActionButton(_("Undo"), () => this.bga.actions.performAction("action_undo"), {
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

  getMultiSelectCountAndSync(result: any = {}) {
    // sync alternative selection on toolbar
    const allSel = document.querySelectorAll(`.${this.classSelected}`);
    const selectedAlt = this.classSelectedAlt;
    this.removeAllClasses(selectedAlt);
    let totalCount = 0;
    allSel.forEach((node: any) => {
      const altnode = document.querySelector(`[data-target-id="${node.id}"]`);
      if (altnode) {
        altnode.classList.add(selectedAlt);
      }
      const cnode = altnode ?? node;
      const tid = cnode.dataset.targetId ?? node.id;
      const count = cnode.dataset.count === undefined ? 1 : Number(cnode.dataset.count);
      result[tid] = count;
      totalCount += count;
    });
    return totalCount;
  }

  onMultiCount(tid: string, opInfo: OpInfo) {
    let node = $(tid);
    const altnode: HTMLElement = document.querySelector(`[data-target-id="${tid}"]`);
    const cnode = altnode ?? node;
    const count = Number(cnode.dataset.count ?? 0);
    cnode.dataset.count = String(count + 1);
    const max = Number(cnode.dataset.max ?? 1);
    if (count + 1 > max) {
      cnode.dataset.count = "0";
      if (node) node.classList.remove(this.classSelected);
      else cnode.classList.remove(this.classSelected);
    } else {
      if (node) node.classList.add(this.classSelected);
      else cnode.classList.add(this.classSelected);
    }

    this.onMultiSelectionUpdate(opInfo);
    return;
  }

  onMultiSelectionUpdate(opInfo: OpInfo) {
    const ttype = opInfo.ttype;
    const skippable = false; // XXX
    const doneButtonId = "button_done";
    const resetButtonId = "button_reset";
    const skipButton = $("button_skip");
    const buttonName = _("Submit");

    // sync real selection to alt selection on toolbar
    const count = this.getMultiSelectCountAndSync();

    const doneButton = $(doneButtonId);
    if (doneButton) {
      if ((count == 0 && skippable) || count < opInfo.data.mcount) {
        doneButton.classList.add(this.classButtonDisabled);
        doneButton.title = _("Cannot use this action because insuffient amount of elements selected");
      } else if (count > opInfo.data.count) {
        doneButton.classList.add(this.classButtonDisabled);
        doneButton.title = _("Cannot use this action because superfluous amount of elements selected");
      } else {
        doneButton.classList.remove(this.classButtonDisabled);
        doneButton.title = "";
      }
      $(doneButtonId).innerHTML = buttonName + ": " + count;
    }
    if (count > 0) {
      $(resetButtonId)?.classList.remove(this.classButtonDisabled);

      if (skipButton) {
        skipButton.classList.add(this.classButtonDisabled);
        skipButton.title = _("Cannot use this action because there are some elements selected");
      }
    } else {
      $(resetButtonId)?.classList.add(this.classButtonDisabled);

      if (skipButton) {
        skipButton.title = "";
        skipButton.classList.remove(this.classButtonDisabled);
      }
    }
  }

  setSubPrompt(text: string, args: any) {
    if (!text) text = "";
    const message = this.format_string_recursive(this.getTr(text), args);
    // have to set after otherwise status update wipes it
    setTimeout(() => {
      $("gameaction_status").innerHTML = `<div class="subtitle">${message}</div>`;
    }, 100);
  }

  completeOpInfo(opInfo: OpInfo) {
    try {
      // server may skip sending some data, this will feel all omitted fields

      if (opInfo.data?.count !== undefined && opInfo.count === undefined) opInfo.count = parseInt(opInfo.data.count);
      if (opInfo.data?.mcount !== undefined && opInfo.mcount === undefined) opInfo.mcount = parseInt(opInfo.data.mcount);
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
