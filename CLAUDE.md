# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Skara Brae is a board game implementation for [BoardGameArena](https://boardgamearena.com) (BGA). It uses TypeScript for the client and PHP for the server, running on the BGA framework.

## Build Commands

```bash
npm run build        # Build both TypeScript and SCSS
npm run build:ts     # Compile TypeScript → skarabrae.js
npm run build:scss   # Compile SCSS → skarabrae.css
npm run watch        # Watch both for changes
npm run watch:ts     # Watch TypeScript only
npm run watch:scss   # Watch SCSS only
```

Compiled outputs (`skarabrae.js`, `skarabrae.css`) are checked into the repo — BGA requires them.

## Code Formatting

Prettier with `printWidth: 140`, `braceStyle: "1tbs"`, `trailingComma: "none"`. Includes PHP plugin.

## Architecture

### Client (TypeScript → `skarabrae.js`)

Class inheritance chain:
```
GameGui (BGA) → Game0Basics → Game1Tokens → GameMachine → GameXBody
```

- **Game0Basics** (`src/Game0Basics.ts`): Base class extending BGA's GameGui. Handles state dispatch, notifications, preferences.
- **Game1Tokens** (`src/Game1Tokens.ts`): Token display and animation management.
- **GameMachine** (`src/GameMachine.ts`): Renders operation machine UI — buttons, selectable targets, multi-select.
- **GameXBody** (`src/GameXBody.ts`): Main game UI. Template setup, player boards, score sheet, game area.
- **Zain** (`src/Zain.ts`): Module loader. Declares `bgagame.skarabrae` and loads Dojo/BGA libraries.
- **LaAnimations** (`src/LaAnimations.ts`): Animation helpers independent of Dojo.

State hooks pattern: `onEnteringState_<stateName>()`, `onLeavingState_<stateName>()`, `onUpdateActionButtons_<stateName>()`.

### Server (PHP)

```
Table (BGA) → Base → Game
```

- **Game** (`modules/php/Game.php`): Main game logic.
- **Base** (`modules/php/Base.php`): Base class extending BGA Table.
- **Material** (`modules/php/Material.php`): Token/material definitions, error messages.

### Operation Machine (`modules/php/OpCommon/`)

Core architectural pattern — a stack-based system that drives all game logic:

- **OpMachine** (`OpMachine.php`): Manages operation stack stored in `machine` DB table.
- **Operation** (`Operation.php`): Base class for all operations. Key methods: `getPrimaryArgType()`, `argPrimaryOperation()`, `resolve()`.
- **ComplexOperation** (`ComplexOperation.php`): Multi-step operations with sub-operations.
- **OpExpression** (`OpExpression.php`): Parses operation expression strings.

Operations are in `modules/php/Operations/` (51 `Op_*.php` files). Each encapsulates one game action (e.g., `Op_cook`, `Op_craft`, `Op_feed`, `Op_village`).

Flow: Server pushes operations → state machine dispatches → player selects target → `resolve()` processes and queues next operations.

### Game States (`modules/php/States/`)

- **GameDispatch / GameDispatchForced**: Auto-execute queued operations.
- **PlayerTurn / PlayerTurnConfirm**: Active player interaction states.
- **MultiPlayerMaster / MultiPlayerTurnPrivate / MultiPlayerWaitPrivate**: Simultaneous play mode.
- **MachineHalted**: Game end.

### Token System (`modules/php/Db/`)

- **DbTokens**: CRUD for the `token` table (`token_key`, `token_location`, `token_state`). Includes deck/discard auto-reshuffle.
- **DbMachine**: Operation stack persistence.
- **DbMultiUndo**: Multi-step undo system.

### Database Tables

- `token`: Game piece tracking (key, location, state)
- `machine`: Operation stack (id, rank, type, owner, data JSON)
- `multiundo`: Undo checkpoints (move_id, player_id, data, meta)

### Styles (SCSS → `skarabrae.css`)

Entry point: `src/css/GameXBody.scss`. Partials: `Boards.scss`, `Cards.scss`, `Tokens.scss`, `Tooltip.scss`, `_variables.scss`.

### TypeScript Compilation

`tsconfig.json` targets ES5 with no modules, concatenating all `src/**/*.ts` into a single `skarabrae.js`. Type definitions for BGA framework are in `src/types/`.

## PHP Tests

Located in `modules/php/Tests/`. Uses in-memory test doubles (`MachineInMem`, `TokensInMem`) to test game logic without a database.

## Adding a New Operation

1. Create `modules/php/Operations/Op_<name>.php` extending `Operation` or `ComplexOperation`.
2. Implement `getPrimaryArgType()`, `argPrimaryOperation()`, and `resolve()`.
3. Register it so the operation machine can instantiate it.
