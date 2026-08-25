# Next lifecycle: runtime-integrity hardening

## Status

The Delegate mission route is terminally complete through Step 69. The post-implementation Blackquill review is complete and recorded in `todo/blackquill-todos.md`.

This is a new technical-hardening lifecycle. It is not Delegate Mission Step 70, does not reopen a retired Delegate, and grants no new mission, cognition, provider, credential, tool, perimeter, execution, continuation, redeployment, or reuse authority.

## Purpose

Convert recorded governance guarantees into runtime-enforced invariants under concurrency, process death, partial writes, replay, and unknown provider outcomes.

The critical path is:

1. real credential mediation;
2. durable provider-invocation claiming and recovery;
3. transactional persistence primitives;
4. crash and concurrency proof;
5. standardized replay and idempotency semantics;
6. terminal-transition recovery;
7. terminal-audit scope correction;
8. safe consolidation, formatting, and documentation reduction; and
9. retained live operational evidence.

## First leg: credential mediation and durable invocation claim

### Implementation progress

Hardening Step 1, the durable invocation-claim foundation, is implemented. It introduces the canonical claim contract and one locked, atomic, single-winner transition that persists `INVOCATION_CLAIMED_PENDING_EXTERNAL_IO`, consumes the exact opaque lease and turn authority in the claim, and establishes a stable idempotency key before any external I/O.

The existing Delegate cognition gateway has not yet been migrated onto this boundary. Direct Symfony platform injection therefore remains present until Hardening Step 2 introduces the broker-mediated adapter and makes a valid claim mandatory for invocation.

### Problem

The current Clavium lease records permission but does not technically control the only route to provider credentials. The provider call can also complete before the result/turn is durably recorded. A process death in that interval creates an unknown outcome and may cause a repeated external call.

### Required design outcome

- Only a credential broker can resolve provider credentials.
- The broker requires an exact, live, unexpired, mission-scoped, single-use Clavium lease.
- Invocation claiming and lease consumption are one atomic single-winner transition.
- A durable `INVOCATION_STARTED` record exists before external I/O.
- Each invocation has a stable persisted idempotency key.
- Provider adapters receive the key where supported.
- Unknown outcomes fail stopped and follow explicit recovery rules; they are never automatically replayed.
- Provider response identity is durably sealed before downstream cognition-result processing.
- Credentials never enter lifecycle records, exceptions, logs, serialized state, or callers outside the broker boundary.

### Boundaries that must remain intact

- Clavium attests and mediates access; it does not select the model or authorize the mission.
- Imperator authorizes the exact attested model and frozen turn requirements; authorization is not credential possession.
- Curia selects only from Oracle's frozen eligible candidates; it does not bind credentials or invoke providers.
- Conscription seals the runtime binding; catalogue identity remains distinct from the executable provider model name.
- Citadel performs the governed cognition turn only after every exact authority and runtime invariant passes.
- Symfony AI remains the provider/runtime adapter boundary, not the governance authority.

### Required tests before the leg is complete

- direct provider invocation without broker mediation is impossible;
- missing, expired, mismatched, consumed, or superseded leases fail stopped;
- two concurrent claimants produce exactly one invocation winner;
- crashes before claim, after claim, during provider I/O, after response receipt, and before result persistence have deterministic recovery outcomes;
- an unknown provider outcome cannot be automatically invoked again;
- no credential material appears in persisted artifacts or diagnostics; and
- the existing full Delegate lifecycle remains green.

## Subsequent legs

After the first leg is proven:

1. introduce shared transactional stores and atomic transition primitives;
2. move terminal retirement onto a recoverable multi-store transition;
3. standardize replay fingerprints and conflicting-source rejection;
4. rename or expand the fourteen-record terminal audit;
5. consolidate the three Senate question engines while preserving separate jurisdictions, authorities, and evidence;
6. consolidate same-actor mechanical workflows where governance boundaries are unaffected;
7. validate provider/model configuration through strict adapter-specific allowlists;
8. format compressed Delegate classes and deduplicate persistence code;
9. reduce active documentation to canonical lifecycle, authority matrix, schema catalogue, and audit specification; and
10. collect live operational evidence.

## Completion criterion

This lifecycle is complete only when credential custody, provider invocation, authority consumption, state transitions, and terminal retirement are enforced by the runtime under concurrency and failure, with tests and retained evidence proving those properties.

## Starting instruction

Read the completed route, flow, Blackquill backlog, and review handoff. Inspect the current provider gateway, Clavium activation/lease services, Symfony AI platform registration, bounded cognition-turn service, and their tests before proposing changes. State the exact credential boundary, durable state machine, recovery semantics, and migration sequence before implementation.
