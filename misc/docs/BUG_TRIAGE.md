# Bug Triage - project config and state

Game-specific configuration and running state for the `game-bug-triage` skill.
The skill itself is game-independent; everything project-specific lives here.
Read this file at the start of every triage run.

## Game reference

- **BGA game id:** 2579 (Skara Brae)
- **Bug list:** https://boardgamearena.com/bugs?game=2579
- **Single report:** https://boardgamearena.com/bug?id=<REPORT_ID>
- **Studio error log:** https://studio.boardgamearena.com/studioissues?game=skarabrae - studio short name is `skarabrae`, not the numeric id above.

## Rules sources (for `rules`-type reports)

1. [RULES.md](RULES.md) - the rulebook - you must read this no matter what you doing. It does not cover the promo tiles (Barter, Forage).

## Deploy model

No git tags. The live version is the `version` field (`yymmdd-hhmm`) of the game in the gamepanel page data (`https://boardgamearena.com/gamepanel?game=skarabrae`), also the `Release` of backend errors (`skarabrae@260603-1612`). A commit is live if it predates that timestamp: `git log -1 --before='<yyyy-mm-dd hh:mm>'` gives the last deployed commit, then `git merge-base --is-ancestor <fix> <that commit> && echo LIVE`.

- Before 2026-10-29 the repo had no tags: `260603-1612` = commit 8bf785a.
- From `v261029-0315` on, `/pre-deploy` tags each release: creating a git tag = deployed, so `git merge-base --is-ancestor <fix> <latest-tag> && echo LIVE`.

## Investigation and tests

- PHP tests live in `modules/php/Tests/` (in-memory doubles, see CLAUDE.md). Run one file with `npm run test -- modules/php/Tests/<File>.php`.
- A reproducing test must be **green**, asserting the current buggy behavior, with a comment pinning it for `BGA #<id>` (flip the assertion when the fix lands).

## Bug triage last checked

**never** - first run 2026-10-28; bump this line to `date` at run start after each run.

## Tracked bugs

Internal record of triaged reports (root cause, fix, test) - never put this detail in a public bug comment.

- [ ] **End-of-round Cook after flipped Craft is forced, should be optional (#240030, #214117 2 votes, maybe #217258).** Rules agree with the reporters: RULES.md, Standard Action Tiles / Craft: "Once flipped: Players may take a Cook Action at the end of each Round." Code NOT checked yet - no test. #217258 (German) reads like the same: cooking with 0 resources keeps re-prompting "Confirm: 0" without ending the action.
- [ ] **Forage gives 2 gathers instead of 3 when flipped (#241052, #226247).** RULES.md does not cover promo tiles; the only text is the card text in action_material.csv ("Resolve 1 Gather Action Tile up to 2 times (3 times when flipped)"), which is game data, not a rules source. Player tluiko on #226247: "the first action is done automatically, if there is only one legal choice. Then you have 2 left" - possibly a UX confusion, not a count bug. Not investigated.
- [ ] **Translation comment owed:** #217258 (German, see Cook entry above).
- [ ] **Not yet triaged** (no log hit on their tables, or too old for the log): #247332 task card action not given, #241428 Trade skaill-knife-or-skip prompt stuck, #226872 furnish takes more than selected, #214453 weekly task not completing at 8 flipped tiles, #212802 choose where the large worker goes (reads as a feature ask), #212577 / #210779 / #209230 undo, #211341 freezes every move, #209305 discarded settlers kept, #206403 hearth number not shown, #210984 (Info needed since 2026-03-19, no reply).
- [ ] **Frontend auto errors, unchecked.** `format_string_recursive` null `i.i18n` / `i.type` (GAMESERVER-JS-BBF8, BH3Y, BFV0, BFRP, BHJS, BC6X - one defect across engines and releases, ~60 events / ~40 users; culprit is in the BGA site layer `ly_studio`, so check for a game frame before calling it ours); `querySelectorAll '& > *' is not a valid selector` (JS-BFCB, 19 events, 1 user, old browser?). Backend: "action_undo method is not defined in this game" (663430, 716980, 733023 - 5 events total).

## Triage run log

- 2026-10-28 (first run, partial): created this file. Read all 33 unresolved reports and the 30-day error log; found the flipped-Barter `?(2shell)` cluster (fix 18a778c, undeployed), #210727 ready for Fixed, #247353 in the 2026-09-30 TableOptions incident. No status changes or comments posted yet - waiting on Victoria. Marker not bumped.
- 2026-10-29: deployed v261029-0315 (18a778c Barter fix, e46790e table options fix); no `?(2shell)` event after the deploy. Set Fixed (BGA template comment): #252721, #252976, #252847, #246569 (Barter), #210727 (translation, 527b603), #247353 (2026-09-30 TableOptions incident, e46790e).
- 2026-10-29 (cont.): Victoria's call - the unproven June-Aug "last action" reports match the Barter crash, set Fixed: #225893, #227377, #228810, #229037, #230536, #233579, #234030 (English translation comment posted first), #238431, #240179, #228401. Barter entry removed from Tracked bugs. NEXT RUN: recheck `?(2shell)` errors (741723, 749409, 750072, 701774, 711720, 714942, 750959, 731046) and error 700232 for any event on release `261029-0315`.
