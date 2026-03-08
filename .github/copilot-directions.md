# Copilot Agent Directions — Quiz Buzzer Website

## Project Goal
Build a real-time multi-device web application for quiz competitions where:
- One **Judge** creates and manages a single competition room.
- Up to **4 Contestants** join the same room from their own phones or tablets.
- The judge starts each round.
- Contestants press a virtual buzzer.
- The system determines **who pressed first**.
- The judge marks the answer as **correct** or **wrong**.
- Scores update live for everyone.

This must be a **full-stack Laravel application** with:
- **Backend:** Laravel 12 (latest stable major version)
- **Frontend:** Blade/HTML + Vanilla JavaScript + Tailwind CSS
- **Database:** MySQL
- **Real-time updates:** Laravel Broadcasting + Laravel Reverb + Laravel Echo

Use Laravel’s official real-time broadcasting stack. Laravel 12 documentation confirms Reverb is the first-party WebSocket solution and can be installed using `php artisan install:broadcasting`. The current release notes also show Laravel 12 as the active major documentation version.

---

## Core Product Concept
The website is a **quiz buzzer system**.

There is one competition room used by:
- the **judge**
- the **contestants**

The judge sets up the match, enters contestant names, and starts the competition.
Contestants open a shared room link, select their names, and wait for the judge to start a round.
When the round begins, the buzzer becomes active for eligible contestants.
The first contestant to buzz in is locked as the first responder.
The judge then decides whether the answer is correct or wrong.

---

## Mandatory Tech Decisions
Use these exact implementation rules unless there is a strong reason not to:

1. **Laravel backend with Blade views**
   - Do not use React, Vue SPA, Inertia, Livewire, or any frontend framework.
   - Use standard Laravel routes, controllers, service classes, events, and Blade templates.

2. **Frontend stack**
   - Use Blade templates for page rendering.
   - Use Tailwind CSS for styling.
   - Use plain JavaScript for interactivity and real-time event handling.
   - Use Laravel Echo in the frontend only for subscribing to room events.

3. **Database**
   - Use MySQL.
   - Use Laravel migrations, seeders if useful, Eloquent models, and form requests.

4. **Real-time behavior**
   - Must work across multiple devices simultaneously.
   - Use **Laravel Reverb** for WebSocket broadcasting.
   - Use events/channels so all clients receive immediate state updates.

5. **Architecture quality**
   - Keep code clean and modular.
   - Move match/round logic into dedicated service classes, not controllers.
   - Validate all inputs.
   - Prevent race conditions when multiple contestants buzz nearly at the same time.

---

## User Roles

### 1) Judge
The judge can:
- create a competition room
- choose the number of contestants (2 to 4)
- enter contestant names
- start the competition
- start each round
- reset buzzers
- begin a new round
- mark an answer as correct
- mark an answer as wrong
- end the competition
- see live scoreboard
- see the first contestant who buzzed
- see the answer timer countdown
- see final rankings and winner

### 2) Contestant
A contestant can:
- open the competition link
- choose their own name from the list prepared by the judge
- enter their buzzer screen
- see their own name
- see their score
- press the buzzer only when allowed
- see if they were first
- see if they are temporarily blocked after a wrong answer
- see the scoreboard if enabled

---

## Detailed Functional Requirements

### A. Judge Setup Screen
Build a page for the judge to configure the competition.

Required fields:
- number of contestants (2, 3, or 4 only)
- contestant names based on selected count

Required behavior:
- contestant names are required
- contestant names must be unique within the room
- trim whitespace
- prevent empty or duplicate names
- after clicking **Start Competition**, create exactly one room and generate:
  - a unique room code
  - a judge access token or secure judge session
  - a public contestant join link

Display to the judge:
- join link
- copy button
- QR code for quick contestant access

### B. Contestant Join Flow
Contestants open the room link.

Required behavior:
- show a list of contestant names defined by the judge
- contestant selects one available name
- once selected, that name becomes locked to that device/session
- no two people can claim the same contestant name at the same time
- if a name is already taken, mark it unavailable in real time
- after selecting a name, redirect to the contestant buzzer screen

Implement defensive checks server-side so duplicate claiming is impossible.

### C. Contestant Buzzer Screen
Each contestant page must show:
- contestant name
- current score
- room status
- large buzzer button in the center
- connection status indicator (connected / reconnecting / offline)
- temporary block countdown if blocked

Buzzer states:
- disabled before round starts
- enabled when judge starts round
- disabled immediately after someone buzzes first
- disabled for blocked contestant after a wrong answer

UI requirements:
- mobile-first layout
- full-screen friendly design
- very large, easy-to-tap buzzer button
- clear visual change between enabled and disabled states
- optional sound effect on buzzer press

### D. Start Round Logic
When the judge starts a round:
- set room/round state to “active”
- enable the buzzer for all eligible contestants
- reset previous first-buzz data
- reset answer timer
- notify all connected clients instantly

Eligibility rules:
- contestants currently blocked because of a wrong answer cannot buzz until their block expires
- all others can buzz

### E. First Buzz Detection
This is the most critical feature.

Requirements:
- if multiple contestants press almost simultaneously, only the first valid press must win
- store the first valid press atomically on the backend
- all later buzz attempts in that round must be rejected
- broadcast the winner instantly to judge and all contestants

When the first buzz is accepted:
- mark that contestant as current responder
- disable all buzzers for the others
- show a success state/message to the winning contestant
- show the winner name on judge screen
- start a 10-second answer timer

Implementation expectation:
- use a transaction and row-level locking or an equivalent atomic mechanism
- do not rely on frontend timing
- backend is the source of truth

### F. Answer Timer
After the first contestant buzzes:
- start a 10-second countdown
- show countdown on the judge screen
- optionally show countdown on the contestant screen too
- when timer reaches zero, the judge can still decide manually, but the UI should clearly show timeout occurred

Preferred approach:
- store timer start timestamp on backend
- broadcast timer start event
- calculate remaining time on frontend using synced timestamps
- do not spam the server every second if avoidable

### G. Judge Decision: Correct Answer
When judge clicks **Correct Answer**:
- add 1 point to current responder
- update scoreboard immediately for all users
- mark round as finished
- clear active responder
- allow judge to start a new round
- optionally show visual/sound celebration

### H. Judge Decision: Wrong Answer
When judge clicks **Wrong Answer**:
- block the current responder for 10 seconds from buzzing again
- re-enable buzzing only for the other eligible contestants
- broadcast who is blocked and until when
- show blocked countdown on the wrong contestant’s screen
- keep the current round active so remaining contestants may buzz
- if all contestants become blocked or ineligible, the judge can reset or start a new round

### I. Scoreboard
Judge must always see a live scoreboard with:
- contestant names
- current points
- contestant connection/claimed status (helpful)

Also provide an optional shared scoreboard view for contestants and display screens.

Scoreboard updates must happen live after any scoring change.

### J. Round Management Actions
Judge controls page must include buttons for:
- Start Round
- Reset Buzzers
- New Round
- End Competition
- Correct Answer
- Wrong Answer

Behavior guidelines:
- **Start Round**: opens buzzer for eligible contestants
- **Reset Buzzers**: clears first buzz and reopens buzzing according to current eligibility rules
- **New Round**: fully resets round-specific state
- **End Competition**: closes room and shows final results
- **Correct/Wrong**: only enabled when there is an active responder

### K. End Competition
When the judge ends the competition:
- close the room
- prevent more buzzing or joining
- calculate final ranking by points descending
- show first-place winner
- show all contestants with scores
- handle ties gracefully

Optional improvement:
- provide a public final results page

---

## Additional Features to Include
If time permits, include these:
- buzzer sound when first buzz is accepted
- highlight flash for the winning contestant
- visual effect on correct answer
- QR code for the join link
- dedicated display screen for scoreboard/results suitable for TV/projector
- reconnect handling if a device temporarily loses connection

---

## Pages / Screens to Build
Create these pages:

1. **Landing page**
   - brief intro
   - button for judge to create competition

2. **Judge setup page**
   - contestant count
   - contestant names
   - create/start competition

3. **Judge control dashboard**
   - room info
   - join link + QR code
   - contestant readiness/claimed status
   - live scoreboard
   - current round state
   - first buzz winner display
   - timer display
   - action buttons

4. **Contestant join page**
   - available contestant names
   - choose one

5. **Contestant buzzer page**
   - name
   - score
   - buzzer button
   - round status
   - blocked countdown if needed

6. **Display/scoreboard page**
   - suitable for large screen
   - current scores
   - active responder
   - final result view when competition ends

7. **Final results page**
   - winner
   - rankings
   - scores

---

## Database Design
Use a schema close to this.

### competitions
- id
- room_code (unique)
- status (`setup`, `waiting`, `active`, `paused`, `finished`)
- judge_token or secure judge access identifier
- contestant_join_token (optional)
- max_contestants
- current_round_number
- active_responder_id (nullable)
- current_round_status (`idle`, `open`, `buzzed`, `judging`, `completed`)
- first_buzzed_at (nullable)
- answer_timer_started_at (nullable)
- answer_timer_expires_at (nullable)
- ended_at (nullable)
- timestamps

### contestants
- id
- competition_id
- name
- slot_number
- score (default 0)
- is_claimed (boolean)
- claimed_session_id or claimed_token (nullable)
- claimed_at (nullable)
- blocked_until (nullable)
- last_seen_at (nullable)
- timestamps

### rounds (optional but recommended)
- id
- competition_id
- round_number
- status
- first_responder_id (nullable)
- first_buzzed_at (nullable)
- timer_started_at (nullable)
- timer_expires_at (nullable)
- decision (`correct`, `wrong`, `timeout`, `reset`, nullable)
- decided_by
- timestamps

### buzz_attempts (optional but recommended for audit/debugging)
- id
- competition_id
- round_id
- contestant_id
- attempted_at
- accepted (boolean)
- rejection_reason (nullable)
- timestamps

---

## Backend Architecture
Organize the backend with clear responsibilities.

Suggested structure:
- `CompetitionController`
- `JudgeController`
- `ContestantController`
- `ScoreboardController`
- `CompetitionService`
- `RoundService`
- `BuzzService`
- `ContestantClaimService`
- `TimerService` (if needed)
- Form Request classes for validation
- Events for broadcasting room state changes

### Important service responsibilities

#### CompetitionService
- create competition
- generate room code
- validate setup
- end competition
- compute final rankings

#### ContestantClaimService
- claim contestant slot atomically
- prevent duplicate claim
- release claim if needed

#### RoundService
- start round
- reset round
- open buzzers for eligible contestants
- manage responder transitions
- apply correct/wrong decision

#### BuzzService
- process buzzer presses atomically
- determine first valid buzz
- reject late or invalid buzz attempts
- broadcast state changes

---

## Real-Time Events to Broadcast
Define events for room updates. Suggested events:
- `CompetitionCreated`
- `ContestantClaimed`
- `ContestantReleased`
- `RoundStarted`
- `BuzzerOpened`
- `BuzzAccepted`
- `BuzzRejected` (optional)
- `ResponderBlocked`
- `ScoreUpdated`
- `RoundReset`
- `CompetitionEnded`
- `PresenceUpdated` (optional)

Use private or presence channels as appropriate.
Suggested channel patterns:
- `competition.{roomCode}`
- `competition.{roomCode}.judge`
- `competition.{roomCode}.display`

Broadcast compact state payloads so the frontend can re-render efficiently.

---

## State Management Rules
The backend must be the single source of truth.

At minimum, track these states:
- competition status
- round status
- which contestants are claimed
- which contestants are eligible to buzz
- active responder
- timer start/end
- contestant scores
- block expiration times

Do not trust frontend-only state for anything important.

---

## Concurrency / Data Integrity Requirements
These rules are critical:

1. **Only one first buzzer per round**
   - Must be enforced server-side atomically.

2. **Only one claimant per contestant name**
   - Must be enforced server-side atomically.

3. **Judge actions must validate current state**
   - e.g. cannot mark correct when there is no active responder.

4. **No buzzing after competition ends**
   - reject server-side.

5. **Blocked contestant cannot buzz during block window**
   - reject server-side.

Use database transactions and pessimistic locking where appropriate.

---

## Routes to Implement
Use clean web routes.

Suggested route structure:

### Public / General
- `GET /`
- `POST /competitions`
- `GET /c/{roomCode}` -> contestant join page
- `GET /c/{roomCode}/buzzer` -> contestant buzzer page
- `GET /c/{roomCode}/display` -> public display page
- `GET /c/{roomCode}/results` -> final results page

### Judge
- `GET /judge/{roomCode}`
- `POST /judge/{roomCode}/start-round`
- `POST /judge/{roomCode}/reset-buzzers`
- `POST /judge/{roomCode}/new-round`
- `POST /judge/{roomCode}/answer/correct`
- `POST /judge/{roomCode}/answer/wrong`
- `POST /judge/{roomCode}/end`

### Contestant actions
- `POST /c/{roomCode}/claim`
- `POST /c/{roomCode}/buzz`
- `POST /c/{roomCode}/heartbeat` (optional)

Also add JSON endpoints if needed for initial page state hydration.

---

## UI / UX Requirements

### General
- mobile-first
- responsive on phones, tablets, laptops, and TV display
- Arabic-friendly layout support if needed later, but build cleanly so localization can be added
- simple and high-contrast design

### Judge dashboard
- easy to read under pressure
- large timer display
- prominent first responder area
- clear colored action buttons
- visible contestant readiness indicators

### Contestant screen
- extremely simple
- very large buzzer button
- strong visual feedback on press
- clear disabled state
- clear blocked countdown

### Display screen
- optimized for projector / TV
- big typography
- big scoreboard cards

---

## Security / Validation Requirements
- secure judge access with signed URL, token, or session protection
- validate all incoming requests
- use CSRF protection for form posts
- do not expose sensitive judge controls publicly
- ensure contestants can only act for the claimed contestant session/token
- guard against duplicate submissions and rapid repeated tapping
- sanitize and validate contestant names

---

## Error Handling Requirements
Handle these gracefully:
- room not found
- room already ended
- contestant name already claimed
- buzzer pressed too early
- buzzer pressed too late
- contestant blocked
- judge action invalid for current state
- WebSocket disconnected / reconnecting

Show friendly user messages.

---

## Testing Requirements
Write automated tests for critical flows.

Must include:
1. competition creation
2. unique contestant names validation
3. contestant claim success
4. contestant duplicate claim rejection
5. round start enables eligible contestants only
6. first buzz winner is accepted
7. simultaneous buzz attempts result in exactly one winner
8. correct answer increases score
9. wrong answer blocks responder for 10 seconds
10. blocked contestant cannot buzz during block window
11. competition end prevents future actions

Use feature tests and unit tests for service logic.

---

## Implementation Notes for Copilot Agent
Please follow this order:

### Phase 1 — Project Setup
- create Laravel 12 project
- configure MySQL
- install and configure Tailwind CSS
- install broadcasting with Laravel Reverb
- install Laravel Echo for frontend event listening
- set up environment variables
- create base layout and Blade structure

### Phase 2 — Data Layer
- create migrations
- create Eloquent models
- add model relationships
- create factories if helpful

### Phase 3 — Core Logic
- build services for competition creation, contestant claiming, buzz handling, round handling, scoring, and competition ending
- implement atomic first-buzz logic with transactions/locking

### Phase 4 — Realtime
- define broadcast channels
- create events
- wire judge, contestant, and display pages to live state updates

### Phase 5 — UI Pages
- landing page
- judge setup page
- judge dashboard
- contestant join page
- contestant buzzer page
- display page
- results page

### Phase 6 — Polish
- QR code generation
- sound effects
- visual feedback
- reconnect handling
- improved accessibility

### Phase 7 — Tests
- write feature tests and service tests for critical scenarios

---

## Non-Negotiable Rules
- Do not use a SPA framework.
- Do not use React or Vue.
- Use Blade + HTML + Vanilla JavaScript + Tailwind.
- Keep the app real-time and multi-device.
- The backend must decide the first buzzer, never the frontend.
- Prevent duplicate contestant claiming.
- The judge must have a clear control dashboard.
- The contestant UI must be mobile-first and very simple.
- Write clean, maintainable Laravel code.

---

## Final Deliverables Expected
The finished application should include:
- working Laravel project
- MySQL migrations
- Blade-based frontend with Tailwind
- real-time room synchronization across devices
- judge dashboard
- contestant buzzer workflow
- live scoreboard
- final results page
- automated tests for critical logic
- README with setup instructions

