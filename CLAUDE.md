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

## Deployment

Use the `/deploy` skill to prepare for BGA deployment. This will:

1. Run `npm run build` to compile TypeScript and SCSS
2. Check for build errors
3. Run tests `npm run tests`
4. Show git status to see changed files
5. Check for spelling mistakes and issues in changed code
6. Check if new PHP tests should be added

Deployment does not create git commits automatically. Files are uploaded to BGA via the SFTP VSCode extension (auto-upload on save if configured).

## Code Formatting

Prettier with `printWidth: 140`, `braceStyle: "1tbs"`, `trailingComma: "none"`. Includes PHP plugin.

## Design Documents

- [misc/docs/DESIGN.md](misc/docs/DESIGN.md) — Software design: operation machine, state machine, token system, undo, testing
- [misc/docs/RULES.md](misc/docs/RULES.md) — Game rules reference

## Architecture

### Client (TypeScript → `skarabrae.js`)

Class inheritance chain: `GameGui (BGA) → Game0Basics → Game1Tokens → GameMachine → GameXBody`

- **Game0Basics** (`src/Game0Basics.ts`): Base class. State dispatch, notifications, preferences.
- **Game1Tokens** (`src/Game1Tokens.ts`): Token display and animation.
- **GameMachine** (`src/GameMachine.ts`): Operation machine UI — buttons, selectable targets, multi-select.
- **GameXBody** (`src/GameXBody.ts`): Main game UI. Template setup, player boards, score sheet.
- **Zain** (`src/Zain.ts`): Module loader.
- **LaAnimations** (`src/LaAnimations.ts`): Animation helpers independent of Dojo.

State hooks: `onEnteringState_<name>()`, `onLeavingState_<name>()`, `onUpdateActionButtons_<name>()`.

### Server (PHP)

Class chain: `Table (BGA) → Base → Game`

- **Game** (`modules/php/Game.php`): Main game logic.
- **Base** (`modules/php/Base.php`): Base class extending BGA Table.
- **Material** (`modules/php/Material.php`): Token/material definitions. Partially generated — see Material Generation below.

### Operation Machine (`modules/php/OpCommon/`)

Stack-based system driving all game logic. Operations are in `modules/php/Operations/` (~52 `Op_*.php` files).

- **OpMachine**: Manages operation stack (stored in `machine` DB table).
- **Operation**: Base class. Key methods: `getPrimaryArgType()`, `argPrimaryOperation()`, `resolve()`.
- **ComplexOperation**: Multi-step operations with sub-operations.
- **OpExpression**: Parses operation expression strings (see DESIGN.md for syntax).

Flow: Push operations → state machine dispatches → player selects target → `resolve()` queues next operations.

### Game States (`modules/php/States/`)

- **GameDispatch / GameDispatchForced**: Auto-execute queued operations.
- **PlayerTurn / PlayerTurnConfirm**: Active player interaction.
- **MultiPlayerMaster / MultiPlayerTurnPrivate / MultiPlayerWaitPrivate**: Simultaneous play.
- **MachineHalted**: Game end.

### Token System (`modules/php/Db/`)

- **DbTokens**: CRUD for `token` table (`token_key`, `token_location`, `token_state`). Auto-reshuffle.
- **DbMachine**: Operation stack persistence.
- **DbMultiUndo**: Multi-step undo checkpoints.

### Database Tables

- `token`: Game piece tracking (key, location, state)
- `machine`: Operation stack (id, rank, type, owner, data JSON)
- `multiundo`: Undo checkpoints (move_id, player_id, data, meta)

### Styles (SCSS → `skarabrae.css`)

Entry point: `src/css/GameXBody.scss`. Partials: `Boards.scss`, `Cards.scss`, `Tokens.scss`, `Tooltip.scss`, `_variables.scss`.

### TypeScript Compilation

`tsconfig.json` targets ES5 with no modules, concatenating all `src/**/*.ts` into a single `skarabrae.js`. Type definitions in `src/types/`.

## Material Generation

`Material.php` is partially generated from CSV files in `misc/`. The script `misc/other/genmat.php` reads pipe-separated CSV files and updates sections of `Material.php` between `/* --- gen php begin <name> --- */` and `/* --- gen php end <name> --- */` markers. Sections outside these markers (constants, manual entries) are hand-edited.

Source CSV files: `loc_material.csv`, `op_material.csv`, `token_material.csv`, `action_material.csv`, `setl_material.csv`, `tracker_material.csv`.

To regenerate: `npm run genmat` (also runs as part of `npm run build`).

When modifying material data, edit the corresponding CSV file, then run `npm run genmat`. Do not hand-edit the generated sections in `Material.php`.

## PHP Tests

Located in `modules/php/Tests/`. Uses in-memory test doubles (`MachineInMem`, `TokensInMem`) to test game logic without a database.

## Adding a New Operation

1. Create `modules/php/Operations/Op_<name>.php` extending `Operation` or `ComplexOperation`.
2. Implement `getPrimaryArgType()`, `argPrimaryOperation()`, and `resolve()`.
3. Register it so the operation machine can instantiate it.
