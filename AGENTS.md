# AGENTS.md

This repository contains a **realtime quiz buzzer website**.

## Stack
- Backend: latest Laravel
- Frontend: server-rendered HTML with JavaScript and Tailwind CSS
- Database: MySQL
- Realtime: Laravel broadcasting with Reverb and Echo

## Product Summary
One judge creates a competition room and manages rounds. Up to 4 contestants join from separate devices, claim unique names, and race to press a virtual buzzer. The backend must reliably determine the first valid buzz.

## Primary Objective
Build a clean, mobile-first, production-ready app with **server-authoritative realtime buzzer logic**.

## Non-Negotiable Rules
- Backend is the source of truth for round state, buzz order, lockouts, and scores.
- Never trust the client to decide who buzzed first.
- Use transactions and row locking or an equally safe mechanism for concurrent buzz handling.
- Keep the implementation simple and maintainable.
- Prefer Blade plus minimal JavaScript over a SPA.
- Maximum 4 contestants.
- Every major state change must be broadcast to connected clients.

## Required Features
### Judge
- create room
- enter 2 to 4 contestant names
- share join link
- view QR code
- start round
- reset buzzers
- mark answer correct or wrong
- start new round
- end competition
- view scoreboard and timer live

### Contestant
- open room link
- select and claim one predefined name
- cannot claim a name already taken
- see their score
- press a large buzzer button when enabled
- receive live status updates

### Round Logic
- judge starts round
- all eligible contestants become active
- first valid buzz is accepted by backend
- others are blocked immediately
- 10-second answer timer starts
- correct answer adds 1 point and ends round
- wrong answer locks that contestant for 10 seconds and re-enables others

### End of Competition
- competition closes
- final ranking is shown
- winner is displayed

## Suggested Domain Model
- Competition
- Contestant
- Round
- BuzzAttempt
- Lockout state per contestant / round

## Expected Engineering Approach
1. Create schema and models first.
2. Implement domain logic in services/actions.
3. Add events and broadcasting.
4. Build judge interface.
5. Build contestant interface.
6. Add tests for critical flows.
7. Refactor only after correctness is achieved.

## Testing Priorities
- unique contestant claiming
- start round flow
- first buzz wins under concurrent conditions
- wrong answer lockout behavior
- correct answer score update
- authorization boundaries
- end competition flow

## UX Expectations
### Judge dashboard
Show:
- room link
- QR code
- contestant list
- claimed status
- first buzzed contestant
- timer
- scoreboard
- controls

### Contestant page
Show:
- contestant name
- score
- giant buzzer button
- waiting / active / locked / winner messages

## Avoid
- frontend-authoritative game state
- unnecessary frameworks
- complex SPA architecture
- fragile timer logic based only on browser clocks
- unclear state transitions in controllers

## Delivery Style
When making changes:
- create working code, not just pseudo-code
- keep commits / change sets focused
- explain major architecture decisions briefly
- prefer simple reliable solutions over clever ones
