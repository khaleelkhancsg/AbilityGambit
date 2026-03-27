# Ability Gambit - Laravel-based project

A chess-inspired strategy game that introduces abilities to expand gameplay depth, create new strategies, and increase replayability.

---

# Project Overview

This project began as a passion-driven idea. I tend to gravitate towards game development projects, as gaming is something I enjoy in my free time. This also makes it more likely that I will continue developing the project.

The idea started as a chess-themed game. However, a traditional chess implementation felt too simplistic for the scope of the assessment and would not provide much room for expansion. Additionally, a basic chess clone would not be particularly engaging as a development project.

To solve this, I introduced **abilities** into the game. These abilities enhance gameplay and create new strategic opportunities. With only a few rule modifications, the game opens up a wide variety of move combinations and gameplay possibilities.

---

# Project Goals

* Create a chess variant with deeper strategy
* Introduce meaningful ability mechanics
* Maintain simplicity while adding complexity
* Build a scalable and maintainable architecture

---

# Core Concept

The main concept of the game is **Chess with Abilities**.

Each player will have:

* A traditional chess board
* Standard chess rules (with modifications)
* A unique **ability bar**
* Special abilities that can be activated during gameplay

These mechanics introduce:

* New strategies
* Comeback mechanics
* Increased skill ceiling
* Greater replayability

---

# Example Ability: Super Pawn

One of the first abilities introduced is the **Super Pawn**.

### Standard Pawn Rules

* Moves forward 2 spaces on first move
* Moves forward 1 space after first move
* Attacks diagonally 1 space
* Cannot attack forward

### Super Pawn Ability

The **Super Pawn** temporarily allows a pawn to:

* Attack directly forward for one move

### Purpose

This ability:

* Prevents easy pawn blocking
* Enhances early game positioning
* Introduces tactical surprises
* Expands strategic depth

---

# Ability Bar System

Each player has an **Ability Bar** that fills over time.

### Initial Design

* Starts at **0%**
* Ability activates at **100%**
* Gains **5% every 5 moves**
* Loses percentage when capturing opponent pieces
* Gains percentage when losing pieces

---

# Tech Stack

## Backend

* Laravel

## Frontend

* Vue.js

## Database

* SQLite

### Why These Technologies?

**Vue.js**

* Lightweight
* Dynamic UI
* Ideal for web-based games

**SQLite**

* Lightweight
* Easy setup
* Perfect for small-scale projects

**Laravel**

* Clean architecture
* Strong ecosystem
* Built-in tools for queues, events, and APIs

---

# Architecture

This project follows **MVC Architecture**.

### Benefits

* Clear separation of concerns
* Maintainable codebase
* Easier UI changes
* Scalable structure

### Why Not Microservices?

Microservices would be:

* Too heavy for this project
* Less efficient
* Overly complex for a chess game

---

# Features

To increase complexity and gameplay depth, the game includes:

* AI opponent
* Background move calculation
* Ability usage evaluation
* Cooldown queue system
* Game statistics tracking
* Replay queue system

### Future Consideration

* Move suggestion system
* AI strategy queue
* Ability usage
* Win rate
* Average game length
* Move accuracy (planned)

These features may be implemented in later stages.

---

# AI-Assisted Development

Development used **Gemini Agentic AI** inside VSCode.

### Setup Steps

1. Set up Gemini inside VSCode
2. Login via Gmail
3. Install dependencies:

* Node.js
* PHP
* Laravel
* SQLite

---

# Installation & Setup

## Install Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
```

Verify Installation:

```bash
composer --version
```

---

## Create Laravel Project

```bash
composer create-project laravel/laravel magic42_project
```

---

## Configure SQLite

Add the following to your `.env` file:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

---

# Running the Project

To start the server, run:

```bash
npm run dev
php artisan serve
php artisan reverb:start
php artisan queue:work
```

---
# Use of AI in the Development Process

Artificial Intelligence was used extensively throughout the development of the chess game to assist with system design, implementation, feature expansion, debugging, and refinement. AI acted as a development assistant, supporting both architectural planning and practical implementation while all final decisions remained under developer control.

## Initial System Architecture and Planning

AI was first used to design the overall system architecture of the chess game. I provided requirements specifying the use of Laravel, Vue.js, and SQLite, alongside requirements for MVC architecture and queued background processing.

AI then generated a structured architecture including:

* Models for game data
* Controllers for request handling
* Service classes for gameplay logic
* Queue jobs for background processing
* Vue components for frontend interaction
* Database tables for game persistence

This established the foundation of the project and ensured that the system followed MVC architecture principles from the beginning.

## Database Design and Backend Logic

AI was used to generate the database schema, including tables for:

* Games
* Moves
* AbilityLogs

These schemas were designed to support gameplay mechanics, replay functionality, and ability tracking. AI also assisted in creating service classes such as `ChessBoardService`, which handled board representation, piece movement, and capture detection. This ensured that core logic remained separate from controllers, following best practice architecture.

AI then extended the system to include:

* Move validation
* Special chess rules (castling, en passant, pawn promotion logic)
* Check and checkmate detection
* Ability-based gameplay mechanics

This significantly accelerated development of complex chess logic.

## Implementation of Gameplay Systems

AI was used to generate and implement key gameplay components, including:

* `MoveController` for player input
* AI move calculation system
* Ability system (Super Pawn ability)
* Ability bar mechanics
* Gameplay loop
* Replay system

Queue jobs were also created using AI assistance, including:

* `CalculateAiMove`
* `ResolveAbilityEffect`
* `UpdateAbilityBar`
* `AnalyseGameReplay`

These jobs enabled asynchronous processing and demonstrated background processing using Laravel queues.

## Feature Expansion and AI Recommendations

After initial implementation, I then asked AI to identify missing features. AI recommended several improvements including:

* Minimax AI algorithm
* Real-time updates
* Advanced draw detection
* PGN export functionality
* Additional abilities
* Move history tracking

I evaluated these suggestions and selectively implemented them after confirming their relevance and academic value. This demonstrated critical evaluation rather than blindly accepting AI-generated suggestions.

## Frontend Development and UI Refinement

AI was also used to generate Vue components including:

* Chessboard interface
* Ability bars
* Move interaction system
* Replay viewer

Additionally, AI was used to fix visual bugs such as:

* Centering layout
* Improving piece design consistency
* Visual highlighting for abilities
* Fixing responsiveness issues

## Developer Oversight and Critical Evaluation

Although AI contributed to many aspects of development, all suggestions were reviewed before implementation. I confirmed the reasoning behind AI recommendations and only implemented features that aligned with project goals and academic requirements.



