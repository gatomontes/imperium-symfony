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

Hardening Step 2 is implemented. The Delegate bounded-turn service now creates the durable claim before entering the cognition gateway, and the gateway depends on a claim-aware brokered provider invoker instead of a credential-bearing `PlatformInterface`. External-I/O start, response identity, and unknown outcome are durably journaled with automatic replay prohibited. Other resident and Legate gateways remain to be migrated before credential custody is system-wide.

Hardening Step 3 is implemented. The Symfony platform adapter is independently testable, invocation timestamps use an injected clock, credential-resolution failure produces explicit pre-I/O evidence, and provider diagnostics are suppressed after both pre-I/O and unknown-outcome failures. Step 4 begins the shared transactional persistence primitives needed to replace local filesystem transition mechanics.

Hardening Step 4 is implemented. Shared atomic-transition, immutable-record, mutable-state compare-and-swap, and authority-consumption primitives now exist with focused contract tests. Existing services remain explicitly unmigrated until their complete transitions are moved onto these primitives.

Hardening Step 5 is implemented. Durable provider-invocation claiming now uses the shared `AtomicTransition` and `ImmutableRecordStore`; its legacy private lock and commit implementation have been removed without changing the claim schema or checkpoint.

Hardening Step 6 is implemented. Provider invocation journaling now uses shared mutable-state compare-and-swap transitions and immutable claim reads. Its legacy private journal lock and commit implementation have been removed; stale terminal writers and tampered journal state fail stopped while pre-I/O failure, sealed-response, and unknown-outcome semantics remain unchanged.

Hardening Step 7 is implemented. A read-only recovery assessor now classifies every durable provider boundary without granting replay authority, and a cross-platform subprocess contention test proves that two journal starters produce exactly one winner. Crash fixtures cover absence before claim, claimed-without-journal, in-flight provider I/O, response-sealed-before-turn, and completed-turn states.

Hardening Step 8 is implemented. Provider responses are now sealed in immutable, credential-free, claim-bound envelopes before journal response sealing and downstream result processing. The recovery assessor distinguishes unknown in-flight outcomes from recoverable envelope-backed responses, and bounded-turn records now use `ImmutableRecordStore` for exact replay and conflicting-result rejection.

Hardening Step 9 is implemented. A provider-independent forward-recovery service now requires one exact durable recovery authorization, consumes its authority through the shared single-winner store, revalidates the complete claim/journal/envelope/activation/commission chain and cognition payload, and immutably persists the missing turn. Exact replay returns the existing turn; provider reinvocation remains structurally unavailable.

Hardening Step 10 is implemented. Delegate Mission terminal return now advances through a durable, forward-only retirement transaction. Custody restoration, binding retirement, and immutable terminal recording are individually checkpointed; interruption at every checkpoint resumes to one terminal state without rollback or renewed authority.

Hardening Step 11 is implemented. The former terminal audit is now explicitly the fourteen-record terminal operational-evidence audit. Its result declares the bounded completeness claim and exclusion of non-operational pre-deployment governance; the precise command is canonical while the old command remains a compatibility alias.

Hardening Step 12 is implemented. A canonical replay-fingerprint primitive and contract now govern the critical hardened boundaries. Provider claims and terminal retirement require the complete authoritative-input fingerprint for replay, while recovered turns additionally bind exact replay to the recovery authorization that produced them. Changed inputs or a different recovery authority fail stopped instead of inheriting an existing result.

Hardening Step 13 is implemented. Trust, Security, and Usability question authorship now use one jurisdiction-parameterized engine behind their existing public services. The engine preserves each jurisdiction's exact Senator seat, accepted checkpoint, question sequence, prior-testimony lineage, authority purpose, output schema, status, and error vocabulary; an unsupported jurisdiction fails before evidence access or cognition.

Hardening Step 14 is implemented. Security and Usability subsequent-question commission issuance now share one parameterized engine behind stable public services. The engine preserves the prior Trust/Security testimony chain, exact Lord Speaker and recipient Senator authorities, jurisdiction sequence, commission schema, statuses, and error vocabulary. Trust is deliberately rejected because its first-question commission derives from the examination opening rather than prior testimony.

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

## Hardening Step 15

Security and Usability subsequent-question commission acceptance and refusal now pass through one jurisdiction-parameterized engine. Existing service constructors and jurisdiction-specific error/status contracts remain intact. Trust is deliberately excluded because its first-question authority originates from the examination opening rather than a subsequent testimony turn.

## Hardening Step 16

Trust, Security, and Usability question-dispatch authorization/refusal now share one jurisdiction-parameterized Lord Speaker decision engine. The existing public services, exact error families, question lineage, Bailiff authority, and jurisdiction-specific evidence records remain intact.

## Hardening Step 17

Trust, Security, and Usability physical question dispatch now share one jurisdiction-parameterized Bailiff engine. It consumes the exact dispatch authority, preserves the sealed question unchanged, and creates one manifestation-bound testimony-response authority without granting wider cognition or execution authority.

## Hardening Step 18

Trust, Security, and Usability testimony responses now share one jurisdiction-parameterized engine and the existing cognition gateway. Trust and Security retain their bounded next-question commission handoffs; Usability alone validates the complete three-turn chain and creates the finding-phase opening authority.

## Hardening Step 19

The Senate question-engine consolidation sub-leg is closed. Unsupported-jurisdiction failures for the six shared engines now occupy the dedicated contiguous `S790`–`S795` range, avoiding collisions with the preserved Usability runtime errors in `S700`–`S719`. Focused boundary tests and the full Delegate flow protect both the shared mechanics and the independent jurisdiction contracts.

## Hardening Step 20

The Steps 44–46 transactional sub-leg now has its persistence foundation. `CodexImperiiStore` owns one digest-bound Codex per Imperium instance, appends ordered Folia through compare-and-swap checkpoint advances, increments a monotonic generation, returns exact replays, and fails stopped on omission, substitution, reordering, duplicate identity, stale checkpoint, or conflicting replay. The three operational services remain unmigrated until their complete transition coordinator is introduced.

## Hardening Step 21

Delegate Mission operational qualification, manifestation assembly, and mission-seat binding now route both first execution and replay through one recoverable transition coordinator. The coordinator validates each sealed Folium, requires exact predecessor identity and digest, advances the Codex through the three established runtime checkpoints, and resumes safely when interrupted immediately before or after any index transition. No checkpoint grants deployment or operational-use authority.

## Hardening Step 22

The Steps 44–46 persistence migration is closed. Their three Folium commits now use the shared `ImmutableRecordStore`; same-identity conflicting writers cannot overwrite one another, and only the stored winner can advance the Codex. A cross-platform two-process contention test proves one immutable qualification winner, one explicit conflict, one stored Folium, and one generation-one Codex.

## Hardening Step 23

Steps 51–52 now share one bounded record-mechanics substrate for safe reads, canonical digest validation, and immutable persistence. Commission construction and readiness assessment retain separate services, source chains, authorities, outputs, statuses, and established `C26x`/`C27x` error vocabularies. Conflicting same-identity writes fail stopped without allowing readiness judgment to collapse into commission construction.

## Hardening Step 24

Steps 67–68 now share bounded result/return record mechanics: path-safe reads, canonical digest verification, exact source resolution, immutable persistence, and established conflict mapping. Result disposition still decides only `ACCEPTED`, `STOPPED`, or `FAILED`; return authorization separately proves the predeclared termination contract and opens only Garrison's terminal-transition authority. Replay now rejects changed disposition, rationale, actor, source digest, or authority instead of inheriting an earlier result.
