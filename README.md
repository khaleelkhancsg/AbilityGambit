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



