# Handoff: Delegate mission runtime-integrity review complete

## Completed baseline

The Delegate mission lifecycle is implemented and terminally closed through Step 69 at:

`DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL`

The route covers capability demand, Guildhall personnel resolution, Garrison custody, Profile derivation, Senate examination, Imperator approval, operational assembly, deployment, runtime activation, Oracle model selection, Conscription binding, Clavium access/activation, one Citadel cognition turn, Curia disposition/return authorization, and Garrison retirement.

The read-only terminal audit currently validates fourteen digest-bound operational and terminal records plus live binding/custody state without replaying side effects.

## Review completed

Blackquill completed a post-implementation pressure test. The actionable findings are preserved in `todo/blackquill-todos.md` and summarized in `docs/delegate-mission-flow.md`.

Verdict: the governance model survives, but the runtime does not yet deserve the full confidence of its vocabulary. Some controls are recorded without being technically unavoidable.

## Next lifecycle selected

Proceed with the separately named **runtime-integrity hardening lifecycle**, documented in `docs/next-lifecycle-runtime-integrity-hardening.md`.

Do not create Delegate Mission Step 70. Do not reopen the terminal route. Do not begin with code formatting, documentation cleanup, Senate-engine consolidation, or broad persistence refactoring.

## First recommended leg

Implement **credential mediation and durable provider-invocation claiming**:

1. place provider credential resolution exclusively behind a broker;
2. require and atomically consume the exact Clavium lease when claiming invocation;
3. persist `INVOCATION_STARTED` before external I/O;
4. persist and reuse a stable idempotency key;
5. define fail-stopped recovery for unknown provider outcomes; and
6. prove the boundary with crash and concurrency tests.

Preserve all existing Office authorities and the separation between Oracle catalogue identity, Curia selection, Conscription runtime binding, Clavium access custody, Imperator invocation authorization, and Citadel execution.

## Repository references for the next chat

Read before changing code:

- `docs/handoffs/delegate-mission-route-complete.md`
- `docs/delegate-mission-flow.md`
- `docs/next-lifecycle-runtime-integrity-hardening.md`
- `todo/blackquill-todos.md`
- `contracts/delegate-mission-access-and-authorization.md`
- `contracts/delegate-mission-bounded-cognition-turn.md`

Then inspect the concrete Symfony AI platform/provider registration, Clavium access and activation services, Citadel bounded-turn gateway/service, persistence stores, and corresponding tests. Do not assume the class names from the review; derive them from the repository.

## Verification baseline and practice

The user performs the authoritative full PHPUnit run locally on PHP 8.4. The latest user-confirmed baseline before the terminal-audit addition was green at 372 tests; later implementation increased the suite, so the next chat must inspect repository/test history and ask for or record the new local baseline after changes rather than inventing one.

Use bounded commits and GitHub pull requests. Push and merge only when requested or approved. Do not run Composer installation unless explicitly needed and authorized.

## Fresh-chat prompt

```text
Continue Imperium from `main` after merged PR #283 (`868972658c0652eece42eccda0e02cb1ad05468d`).

Read `docs/handoffs/delegate-mission-runtime-integrity-review-complete.md`, `docs/handoffs/delegate-mission-route-complete.md`, `docs/delegate-mission-flow.md`, `docs/next-lifecycle-runtime-integrity-hardening.md`, and `todo/blackquill-todos.md` before changing code. Also read the two Delegate access/invocation contracts named by the handoff.

The Delegate mission route is terminally complete through Step 69. Do not invent Step 70 or reopen the retired Delegate. Begin the separate runtime-integrity hardening lifecycle with its first recommended leg: real credential mediation plus a durable provider-invocation claim and fail-stopped recovery for unknown outcomes.

First inspect the actual Symfony AI provider registration, Clavium lease/activation path, Citadel bounded-cognition gateway, persistence implementation, and tests. Then propose the exact credential boundary, durable invocation state machine, recovery/idempotency semantics, migration order, and tests. Preserve all Office authority boundaries and the distinction between Oracle catalogue identity and runtime provider binding.

The user will run the authoritative full PHPUnit suite locally. Do not run Composer install unless explicitly necessary. Stop if the design requires a governance decision or if implementation uncovers a structural hiccup.
```
