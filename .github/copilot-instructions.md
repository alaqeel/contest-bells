# GitHub Copilot Instructions for Quiz Buzzer Website

You are building a production-ready real-time web application for quiz competitions.

## Product Goal

Build a **multi-device quiz buzzer system** where one **judge** manages the competition and up to **4 contestants** join the same room from separate phones or tablets. Contestants press a virtual buzzer, and the system must determine the **first valid buzzer press** with low latency and correct synchronization.

The stack is fixed unless explicitly changed:

- **Backend:** latest Laravel
- **Frontend:** Blade or plain HTML pages with JavaScript and Tailwind CSS
- **Database:** MySQL
- **Realtime:** use Laravel broadcasting with **Laravel Reverb** and Echo unless there is a strong documented reason not to

## Non-Negotiable Architecture Rules

1. Use **Laravel 12+ conventions** and keep code compatible with the current latest stable Laravel major version.
2. Use **server-authoritative realtime logic**. The backend decides:
    - whether a round is active
    - whether a contestant is eligible to buzz
    - who buzzed first
    - whether a contestant is locked out
    - score updates
3. Never rely on frontend timing or frontend trust for buzzer order.
4. Design the system so simultaneous presses are resolved **atomically** and consistently.
5. All important competition state changes must originate from backend actions and be broadcast to connected clients.
6. Keep the UI simple, mobile-first, responsive, and touch-friendly.
7. Prefer simple server-rendered pages enhanced with JavaScript over a heavy SPA unless there is a compelling reason.
8. Use clean folder organization, small services/actions, form requests, policies where appropriate, and feature tests.

## Core User Roles

### 1. Judge

The judge can:

- create a competition room
- choose number of contestants (2 to 4, maximum 4)
- enter contestant names
- start the competition
- start rounds
- reset buzzers
- mark answers correct or wrong
- start a new round
- end the competition
- watch scores and timers live
- see who buzzed first
- view final ranking

### 2. Contestant

The contestant can:

- open the room link sent by the judge
- select their assigned name from the list
- claim that identity if not already taken
- enter their buzzer screen
- see their own name and score
- see whether the buzzer is enabled or disabled
- press a large buzzer button when a round starts
- see status messages such as:
    - waiting for round start
    - you buzzed first
    - another contestant buzzed first
    - wrong answer, wait 10 seconds
    - round ended

## Required Product Flow

### Competition Setup

- Judge lands on a setup screen.
- Judge selects contestant count: 2, 3, or 4.
- Judge enters contestant names.
- On submit, system creates one competition room and its contestants.
- Generate a contestant join URL.
- Also generate a QR code for quick mobile access.

### Contestant Join

- Contestants open the shared room link.
- They see the list of predefined contestant names.
- Each person selects one name.
- A name may only be claimed by one connected contestant at a time.
- Prevent duplicate claiming robustly on the backend.
- After claiming, the contestant enters their buzzer screen.

### Waiting State

- Before the judge starts a round, contestant buzzer buttons are disabled.
- The judge dashboard shows connected / claimed contestant status.

### Round Start

- Judge clicks **Start Round**.
- Backend sets round state to active.
- Broadcast event to all room clients.
- All eligible contestants see their buzzer activated immediately.

### First Buzz Logic

- The first valid contestant press must be recorded by the backend.
- Once first buzz is accepted:
    - store who buzzed first
    - lock the round from additional winners
    - disable buzzers for all other contestants
    - notify the judge
    - notify the winning contestant that they have the turn to answer
    - start a 10-second answer timer

### Correct Answer

If judge marks **Correct**:

- award 1 point to the contestant
- update scoreboard live
- end current round
- allow judge to start the next round

### Wrong Answer

If judge marks **Wrong**:

- the answering contestant is locked out for 10 seconds
- the remaining eligible contestants become active again
- system waits for the next valid buzz
- locked contestant sees a countdown message
- judge sees current round status live

### Round Controls

Judge must have controls for:

- Start Round
- Reset Buzzers
- Start New Round
- End Competition

Clarify the intended behavior in implementation:

- **Reset Buzzers** resets the current round state safely
- **Start New Round** creates a clean new round after the previous one ends

### Competition End

When judge ends the competition:

- room becomes closed
- contestants can no longer buzz
- show final ranking
- show winner name
- show each contestant score

## Strongly Recommended Extra Features

Include these if reasonably scoped:

- buzzer sound effect when first valid press is accepted
- visual highlight for first buzzer
- celebratory animation for correct answers
- QR code on judge screen
- optional public display screen for projector / TV scoreboard

## Data Model Expectations

Use migrations and Eloquent models. A good baseline domain model is:

- `Competition` / `Room`
- `Contestant`
- `Round`
- `BuzzAttempt` or equivalent event log
- optionally `SessionClaim` or `ContestantConnection`

Suggested fields to model:

### competitions

- id
- public_room_code or uuid
- judge_access_token or secure judge session mechanism
- status: setup, active, ended
- current_round_id nullable
- joined_at / started_at / ended_at timestamps

### contestants

- id
- competition_id
- display_name
- score
- claim_token nullable
- claimed_at nullable
- is_connected flag if useful

### rounds

- id
- competition_id
- status: pending, active, locked, completed
- first_buzz_contestant_id nullable
- buzz_opened_at nullable
- first_buzzed_at nullable
- answer_deadline_at nullable
- resolved_at nullable

### contestant_lockouts (optional) or round-specific lock state

- contestant_id
- round_id
- locked_until

### buzz_attempts

- id
- round_id
- contestant_id
- attempted_at
- accepted boolean
- rejection_reason nullable

## Realtime / Concurrency Requirements

This app is realtime-critical. Implement defensively.

1. Use broadcasting events for:
    - room created / updated
    - contestant claimed
    - contestant connected / disconnected if tracked
    - round started
    - buzz accepted
    - contestant locked out
    - scoreboard updated
    - round reset / ended
    - competition ended
2. Handle simultaneous buzz requests with **database transaction + row-level locking** or another reliable atomic mechanism.
3. Never decide first buzzer on the client.
4. Log all buzz attempts for debugging.
5. Use server timestamps for authoritative ordering.
6. Rejoin and refresh should recover current room state from backend.

## Backend Implementation Guidance

Use Laravel patterns like:

- controllers for HTTP endpoints
- form requests for validation
- service classes or actions for domain logic
- events for broadcasts
- policies / middleware for judge-only actions
- jobs only if truly useful, not for core buzzer acceptance path

Suggested endpoints/pages:

### Judge

- `GET /` -> setup form
- `POST /competitions` -> create competition
- `GET /judge/{room}` -> judge dashboard
- `POST /judge/{room}/rounds/start`
- `POST /judge/{room}/rounds/reset`
- `POST /judge/{room}/rounds/{round}/correct`
- `POST /judge/{room}/rounds/{round}/wrong`
- `POST /judge/{room}/end`

### Contestant

- `GET /join/{room}` -> select name screen
- `POST /join/{room}/claim`
- `GET /play/{room}/{contestant}` -> contestant buzzer screen
- `POST /play/{room}/{contestant}/buzz`

### Public display (optional)

- `GET /display/{room}`

## Frontend Guidance

- Build mobile-first pages with Tailwind CSS.
- Use large tap targets.
- Keep contestant screen extremely simple.
- Use vanilla JavaScript or minimal JS modules.
- Connect to broadcast channels with Laravel Echo.
- Show clear visual state changes:
    - buzzer disabled
    - buzzer enabled
    - waiting
    - locked out countdown
    - you buzzed first
    - another contestant buzzed first
    - score updates
- Prevent double-submit on buzzer button from the UI, but still fully enforce on backend.

## UX Requirements

### Judge dashboard should show

- room code / share link
- QR code
- contestant list and claim status
- current round state
- current timer countdown
- who buzzed first
- live scoreboard
- main control buttons

### Contestant screen should show

- contestant name
- current score
- giant buzzer button
- current status message
- lockout countdown when applicable

### Final results screen should show

- sorted ranking
- winner
- scores

## Security / Integrity Rules

1. Judge actions must be protected from contestant access.
2. Contestants must only be able to act as their claimed identity.
3. Duplicate claiming must be prevented server-side.
4. Do not expose internal IDs unnecessarily when a signed URL, UUID, token, or secure identifier is better.
5. Validate all requests.
6. Prevent stale clients from forcing invalid state transitions.

## Testing Requirements

Write meaningful automated tests, especially for:

- competition creation
- contestant claim uniqueness
- round start state transition
- first buzz wins under concurrent conditions
- wrong answer lockout behavior
- correct answer score increment
- end competition flow
- authorization boundaries between judge and contestant

Include at least:

- feature tests for core flows
- unit tests for buzzer resolution service if created

## Developer Workflow Requirements

When making changes:

1. Start with schema and domain model.
2. Implement core services for round lifecycle and buzz acceptance.
3. Add broadcasting events.
4. Build judge pages.
5. Build contestant pages.
6. Add public display page.
7. Add tests.
8. Refactor for clarity.

## Output Expectations For Copilot

When implementing this project:

- create files directly instead of only describing them
- prefer complete working code over pseudo-code
- explain major architecture choices briefly in comments or commit-style summaries
- when uncertain, choose the simplest reliable architecture
- do not introduce unnecessary frameworks
- keep dependencies minimal
- keep code readable and production-oriented

## Acceptance Criteria

The implementation is successful only if:

- judge can create a room and share a join link
- contestants can claim unique names from multiple devices
- judge can start a round
- first valid buzz is determined correctly by backend
- others are blocked immediately after first accepted buzz
- correct answers add points
- wrong answers lock out only the wrong contestant for 10 seconds and allow others to continue
- scoreboard updates live
- final winner view works
- app remains usable on phones and tablets

## Nice Implementation Details

Prefer these naming ideas unless the codebase suggests better alternatives:

- `CompetitionService`
- `RoundService`
- `BuzzService`
- `ClaimContestantAction`
- `StartRoundAction`
- `AcceptBuzzAction`
- `MarkAnswerCorrectAction`
- `MarkAnswerWrongAction`

## Avoid

- SPA overengineering
- client-side authoritative state
- polling as the primary realtime mechanism if broadcasting is available
- deeply coupled controller logic
- unclear state transitions
- fragile timer logic based only on the browser

If a tradeoff is needed, prioritize **correct buzzer fairness**, **clarity**, **mobile usability**, and **simple maintainable Laravel architecture**.

## Internationalization requirement:

- The application must support both Arabic and English.
- Arabic is the default and primary language.
- The UI must be fully RTL-aware when Arabic is active.
- Do not hardcode any user-facing text in Blade, JavaScript, controllers, validation messages, seeders, or components.
- All visible text must come from translation files using translation keys.
- Organize translations clearly, for example:
    - lang/ar/\*.php
    - lang/en/\*.php
- Use consistent translation namespaces such as:
    - common.\*
    - judge.\*
    - contestant.\*
    - competition.\*
    - scoreboard.\*
    - validation.\*
- Buttons, labels, alerts, statuses, timers, errors, success messages, and empty states must all use translation keys.
- Frontend JavaScript must receive translated strings from the backend or a localization layer, not inline hardcoded text.
- Build the layout so Arabic works as RTL and English works as LTR.
- Make Arabic the fallback/default locale.
- Ensure future pages are easy to localize by following the same translation-key pattern everywhere.
