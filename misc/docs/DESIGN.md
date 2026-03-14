# Skara Brae - Software Design Document

Reconstructed from the codebase. For game rules see [RULES.md](RULES.md).

## Overview

Skara Brae is a BoardGameArena (BGA) game implementation using TypeScript (client) and PHP (server). The core architectural pattern is a **stack-based operation machine** that drives all game logic through composable, serializable operations.

## System Architecture

### Server Class Hierarchy

```
Table (BGA Framework)
  └── Base (modules/php/Base.php)        — BGA integration, static instance, player helpers
        └── Game (modules/php/Game.php)  — Game logic, effect methods, scoring
```

### Client Class Hierarchy

```
GameGui (BGA Framework)
  └── Game0Basics (src/Game0Basics.ts)   — State dispatch, notifications, preferences
        └── Game1Tokens (src/Game1Tokens.ts) — Token display, animation manager
              └── GameMachine (src/GameMachine.ts) — Operation UI: buttons, selectable targets
                    └── GameXBody (src/GameXBody.ts) — Main UI: boards, score sheet, setup
```

### Key Subsystems

- **Operation Machine** (`modules/php/OpCommon/`) — Stack-based game logic engine
- **Operations** (`modules/php/Operations/`) — ~52 `Op_*.php` files, one per game action
- **Game States** (`modules/php/States/`) — BGA state machine integration
- **Token System** (`modules/php/Db/DbTokens.php`) — Game piece CRUD with deck/reshuffle
- **Machine DB** (`modules/php/Db/DbMachine.php`) — Operation stack persistence
- **Undo System** (`modules/php/Db/DbMultiUndo.php`) — Snapshot-based undo
- **Material** (`modules/php/Material.php`) — Token definitions, partially generated from CSV

## Operation Machine

The operation machine is the central design pattern. All game logic — turns, actions, resource changes — is expressed as **operations** queued on a stack and dispatched in order.

### Core Classes

**OpMachine** (`OpCommon/OpMachine.php`): Manages the operation stack stored in the `machine` DB table.

- `push(type, owner, data)` — Insert at rank 1 (top of stack, interrupts current)
- `queue(type, owner, data)` — Append at end (after all current operations)
- `interrupt(rank, count)` — Shift existing operations down to make room
- `dispatchAll()` — Main loop: execute auto-operations, return when player input needed
- `dispatchOne()` — Execute single top operation

**Operation** (`OpCommon/Operation.php`): Base class for all operations.

Key methods:

- `auto()` — Can this execute without player input? Calls `canResolveAutomatically()`
- `getPossibleMoves()` — Returns valid targets with error codes
- `getArgs()` — Builds UI state (targets, descriptions, prompts) sent to client
- `resolve()` — Core logic: process player choice, queue next operations
- `action_resolve(data)` — Entry point from player action; validates then calls `resolve()`
- `action_skip()` — Player skips optional operation
- `canSkip()` — Whether operation is optional
- `requireConfirmation()` — Whether to auto-resolve single-target cases

**ComplexOperation** (`OpCommon/ComplexOperation.php`): Operations with sub-operations (delegates). Used for composite expressions like `op1/op2`.

**CountableOperation** (`OpCommon/CountableOperation.php`): Ranged operations `[min,max]`. Tracks `count` (max iterations) and `mcount` (minimum).

### Operation Data Flow

Operations carry a `data` JSON field through the stack:

```php
$this->queue("cook", $owner, ["weight" => $remaining, "reason" => $card]);
```

Child operations inherit parent data. `withData()` / `withDataField()` merge fields. `getDataForDb()` serializes for storage.

### Argument Types

Operations declare their target type via `getPrimaryArgType()`:

- `TTYPE_TOKEN` — Single token selection (click one target)
- `TTYPE_TOKEN_ARRAY` — Multiple token selection (select multiple, then confirm)
- `TTYPE_TOKEN_COUNT` — Token with quantities (select counts per target)

### Auto-Resolution Logic

`canResolveAutomatically()` returns true when:

- Single valid target and `requireConfirmation()` is false
- No valid targets but `canSkip()` is true
- Operation explicitly overrides `auto()` to return true

### Error Codes in Targets

`getPossibleMoves()` returns targets with quality codes (`q` field):

- `0` / `MA_OK` — Valid target
- `1` / `MA_ERR_COST` — Insufficient resources
- `2` / `MA_ERR_PREREQ` — Prerequisites unmet
- `3` / `MA_ERR_OCCUPIED` — Location occupied
- `4` / `MA_ERR_MAX` — Capacity reached
- `5` / `MA_ERR_NOT_ENOUGH` — Insufficient quantity
- `6` / `MA_ERR_NOT_APPLICABLE` — Not applicable

Client displays disabled buttons with error tooltips for non-zero codes.

## Operation Expression Language

Operations are queued using a mini-language parsed by `OpExpression.php` / `OpParser.php` (recursive descent parser).

### Operators (by precedence, lowest first)

- `;` (seq) — Ordered sequence, different priority levels
- `:` (paygain) — Pay cost, then gain reward
- `/` (or) — Player choice among alternatives
- `+` (order) — Unordered collection
- `,` (seq) — Ordered sequence, same priority
- `!` (atomic) — Wraps single operand as atomic unit

### Modifiers

- `N(op)` — Repeat N times, e.g. `3(gather)`
- `?(op)` — Optional (0 or 1), e.g. `?(cook)`
- `[min,max](op)` — Range, e.g. `[1,3](gather)`
- `^` — Shared counter (used with `/+` for shared limits)
- `op(args)` — Parameterized, e.g. `cotag(3,wood)`

### Expression to Operation Mapping

1. Parser produces `OpExpression` tree
2. `exprToOperation()` converts tree nodes:
   - Leaf → `instanciateSimpleOperation()` → concrete `Op_*` class
   - Composite → `ComplexOperation` with delegate sub-operations
   - Ranged → wrapped in `Op_seq` or `CountableOperation`

## State Machine Integration

### BGA States

- **GameDispatch** (GAME state) — Auto-execution loop, dispatches to:
  - **PlayerTurn** (ACTIVE_PLAYER) — Single player makes a choice → **PlayerTurnConfirm**
  - **MultiPlayerMaster** (MULTIPLE_ACTIVE_PLAYER) — Simultaneous play → **MultiPlayerTurnPrivate** / **MultiPlayerWaitPrivate**
  - **MachineHalted** — Game end

### Dispatch Loop (`dispatchAll`)

```
loop (max 1000 iterations):
  if multiplayer operations pending → return MultiPlayerMaster
  op = top operation (lowest rank)
  result = op.onEnteringGameState()
  if result == PlayerTurn → pause, wait for player input
  if result == null → auto-executed, continue loop
  if no operations left → return PlayerTurnConfirm or MachineHalted
```

### Player Action Flow

1. Client in `PlayerTurn` state, displays operation UI
2. Player clicks target → `action_resolve({target: "..."})` sent to server
3. `PlayerTurn::action_resolve()` → `Operation::action_resolve()` → `resolve()`
4. `resolve()` queues next operations via `queue()`/`push()`
5. Transition back to `GameDispatch` → loop continues

## Token System

### Token Model (`token` table)

- `token_key` (string PK) — Unique ID, e.g. `card_setl_3_31`, `worker_0_ff0000`
- `token_location` (string) — Where it is, e.g. `tableau_ff0000`, `deck_village`, `supply`
- `token_state` (int) — State value: position, counter, face up/down

### Key Patterns

**Tracker tokens**: Named `tracker_<type>_<color>`, state = numeric counter. Used for resources, VP, track positions. Modified via `Game::effect_incCount()`.

**Deck/discard**: Tokens in `deck_*` locations auto-reshuffle from `discard_*` when drawn and deck is empty.

**Location queries**: `getTokensOfTypeInLocation(type_pattern, location, state)` with wildcard support (`%`).

### Machine Table (`machine`)

- `id` (int PK) — Auto-increment
- `rank` (int) — Execution order: positive = active, negative = historical
- `type` (string) — Operation expression string
- `owner` (string) — Player color or empty
- `data` (JSON) — Operation parameters

Rank management: `interrupt()` shifts ranks to insert; `hide()` sets rank negative for undo; `normalize()` resets to 1,2,3...

## Undo System

### Snapshot Model (`multiundo` table)

- `move_id` (int) — BGA global move counter
- `player_id` (string) — Owner
- `data` (JSON) — Full snapshot of token + machine tables
- `meta` (JSON) — Version, barrier flag, label

### Barrier System

- Barrier 0 — Normal savepoint, can undo past it
- Barrier 1 — Soft barrier, undo stops here
- Barrier 2 — Hard barrier, clears all player snapshots

### Undo Flow

1. `customUndoSavepoint()` creates snapshot before player's turn
2. Player calls `action_undo()` → `undoRestorePoint()` restores token/machine state
3. Per-player filtering ensures undo only affects that player's tokens/operations

## Client Architecture

### State Hooks

```typescript
onEnteringState_<stateName>(args); // Build UI for state
onLeavingState_<stateName>(); // Cleanup
onUpdateActionButtons_<stateName>(); // Add action bar buttons
```

### GameMachine.ts — Operation UI Rendering

Receives `OpInfo` from server containing:

- `targets` — Valid selections with error codes
- `ttype` — Argument type (token, array, count)
- `prompt` — Localized prompt string
- `buttons` — Whether to show as buttons vs selectable board elements

Rendering logic:

- Targets with `q == 0` → enabled buttons/selectable elements
- Targets with `q > 0` → disabled with error tooltip
- Multi-select → confirm button after selection
- Skip button if `canSkip`

### Notification System

Server: `$this->notify->all(type, message, args)` or `->player()`

Client: `onNotification_<type>(notif)` handlers in Game0Basics/GameXBody

Common notifications: token moves, counter changes, state updates. Server decorates args with `player_name`, `reason` for log messages.

### Animation

`LaAnimations.ts` provides CSS-based animation helpers independent of Dojo. `Game1Tokens.ts` manages `BgaAnimations.Manager` for token movement animations (default 500ms).

## Effect Methods Pattern

`Game.php` contains `effect_*()` methods that perform game state changes + notifications:

```php
effect_incCount($color, $type, $inc, $reason)  // Increment tracker + notify
effect_incTrack($color, $type, $inc, $reason)  // Track advancement variant
effect_incVp($color, $inc, $reason, $category) // VP with stat tracking
effect_drawSimpleCard($deck, $location)         // Draw from deck
```

The `reason` parameter tracks action origin for log messages and undo context.

## Material System

### Structure

`Material.php` contains a large associative array mapping token IDs to metadata:

```php
"token_id" => [
    "type" => "location|token|action|error|tracker",
    "name" => clienttranslate("..."),
    "tooltip" => clienttranslate("..."),
    // Additional fields from CSV generation
]
```

### Generation Pipeline

```
CSV files (misc/*.csv) → genmat.php → Material.php (between markers)
```

Generated sections are delimited by `/* --- gen php begin <name> --- */` / `/* --- gen php end <name> --- */`. Manual entries outside markers are preserved.

Access: `getRulesFor(tokenId, field, default)` looks up material data.

## Testing

### Test Infrastructure (`modules/php/Tests/`)

- **GameUT** — Test game class with in-memory doubles
- **MachineInMem** — Replaces `DbMachine` with array storage
- **TokensInMem** — Replaces `DbTokens` with array storage
- **FakeNotify** — Stub notification sink

### Test Pattern

```php
$this->game->tokens->createTokens();
$this->game->machine->push("operation_expr", $color, $data);
$op = $this->game->machine->createTopOperationFromDbForOwner(null);
$args = $op->getArgs();                    // Verify UI state
$op->action_resolve([...]);                // Simulate player action
$this->dispatch(StateConstants::STATE_*);  // Run dispatch loop
$this->assertEquals(...);                  // Assert game state
```

No database required — fast unit tests running entirely in memory.

## Database Tables

- **`token`** — Game pieces (key, location, state)
- **`machine`** — Operation stack (id, rank, type, owner, data)
- **`multiundo`** — Undo snapshots (move_id, player_id, data, meta)

## End-to-End Example: Player Cooks

1. Player places worker on Cook tile → `Op_act::resolve()` queues `"activate(action_cook_ff0000)"`
2. `Op_activate::resolve()` queues `"cook"` for the owner
3. Dispatch reaches `Op_cook` → `auto()` returns false (needs player choice)
4. State → `PlayerTurn`; `Op_cook::getArgs()` sends recipes + hearth limit to client
5. Client renders recipe buttons with quantities
6. Player selects recipes → `action_resolve({target: {recipe_fish: 2}})`
7. `Op_cook::resolve()` validates hearth weight, queues sub-operations for each recipe
8. Sub-operations execute (remove ingredients, add food/bone/etc.)
9. If hearth capacity remains → re-queues `"cook"` with reduced weight
10. When done → dispatch continues to next operation in stack

## Solo Challenge Mode

Game option "Solo Challenge" (variant 4 in Solo Difficulty) with "Challenge Type" (Weekly Challenge 1-7).

### Deterministic Setup

All players playing the same challenge number in the same ISO week get identical game setups. During `setupGameTables()`, `mt_srand(seed)` is called before any shuffles. Seed format: `YYYYWWNN` (ISO year + week + challenge number).

All randomness is routed through `Base::bgaShuffle()` and `Base::bgaRand()` wrappers. `DbTokens::shuffle()` delegates to `$this->game->bgaShuffle()`. Mid-game reshuffles (deck exhaustion) use normal randomness — only the initial deal is seeded.

In challenge mode, the special action tile is auto-picked (no draft) to keep setup fully deterministic.

### Score Tracking

`SoloChallenge` component (`modules/php/Common/SoloChallenge.php`) handles all BGA legacy API interactions for solo scoring. Game-independent — reusable across projects.

- **Best score**: Legacy key `bscore`, stores integer score
- **Challenge score**: Legacy key `cscoreN` (N=1-7), stores `"YYYYWW:score"` format
- Week-based expiry: if stored week doesn't match current ISO week, score is treated as absent
- Win condition: score > previous best AND score >= minimum goal (default 45)
- Score negated to -1 if conditions not met

### Constants

Solo difficulty modes and goals defined in `Material.php`:
- `MA_GAMEOPTION_SOLO_DIFFICULTY_STANDARD` (1), `_HARD` (2), `_BEAT_OWN` (3), `_CHALLENGE` (4)
- `SOLO_GOAL_STANDARD` (45), `SOLO_GOAL_HARD` (55)

## Build and Compilation

- `npm run build:ts` → `skarabrae.js` — All `src/**/*.ts` concatenated (ES5, no modules)
- `npm run build:scss` → `skarabrae.css` — SCSS compiled to CSS
- `npm run genmat` → `Material.php` — CSV to PHP (also runs during build)
- `npm run predeploy` — Build + lint PHP + run tests
- `npm run tests` — PHPUnit with in-memory doubles

Compiled outputs are checked into the repo (BGA requirement). Type definitions for BGA framework are in `src/types/`.
