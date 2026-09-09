# Citadel Formation Claim Custody and Transport Boundary — FC0–FC3

Status: selected for local offline implementation and independent review. The owner chose deferred enrollment and continued work toward the first Castellan interview. [CP0–CP3 acceptance and owner decision](citadel-commissioning-preparation-acceptance.md) control the scope. Runtime baseline is PR #776 merge `a3e113dfcb29c78ecfa60a8def45e1d3fc32f37a`, tree `91bac3df3fdf78288fbd896672aa6001206160ad`; begin on main containing the later documentation-only selection merge. Record the actual entry commit/tree.

## Concrete problem and deliverable

`FormationCognition::call` retains an `imperium.citadel-session-call-claim/v1` inside the formation aggregate, with consumed derived authority/lease, reservation and a durable start fence. `ClaimBoundCredentialBroker` expects a different persisted claim and fixed legacy binding. The governance and deterministic brokers likewise do not authorize formation claims. Renaming a claim or exporting a self-sealed copy would bypass the authority boundary.

Deliver a formation-specific, default-dormant custody path that validates the genuine retained aggregate claim and binds one exact prepared transport operation before any credential capability is issued. Prove it with synthetic authority produced through the actual formation services, recording credential infrastructure and an offline transport. Preserve the default `UnavailableFormationTransport` alias and refusal. This campaign does not implement or enable a live provider connection, select a provider/model/tariff, enroll any installation or amend B1's accepted remote cost/time guarantees.

The objective is a reusable custody boundary with real production integration seams and adverse proof, not another general readiness inventory. Scope only the necessary formation/Clavium boundary, contract, tests and safe preparation support. Do not migrate the legacy personnel lifecycle, add speculative institutional witnesses or change native enrollment.

## Required reading

Read applicable AGENTS.md, this document and [handoff](handoffs/citadel-formation-claim-custody-ready.md), then:

- `docs/citadel-commissioning-preparation-acceptance.md`, `docs/citadel-commissioning-compatibility.md`, `docs/citadel-commissioning-owner-decisions.md`.
- `docs/citadel-readiness-matrix.md` (especially B1), `docs/citadel-readiness-runbook.md`, `docs/citadel-readiness-integration-acceptance.md`.
- `docs/citadel-formation-correction-acceptance.md`, `docs/citadel-ir01-correction-report.md`, `docs/citadel-native-authority-protocol-acceptance.md`.
- `docs/citadel-mission-formation-decisions.md`, `docs/citadel-mission-formation-implementation.md`, `contracts/citadel-formation-runtime.md`, `docs/delegate-mission-flow.md`.
- Formation sources: `FormationCognition`, `FormationJournal`, `FormationPersonnel`, `FormationSignatures`, `FormationInstitution`, `FormationPreparation`, `SessionExposure`, `BoundedFormationTransport`, `UnavailableFormationTransport`.
- `Clavium/FormationSessionLeaseService`, `ClaimBoundCredentialBroker`, `GovernanceClaimBoundCredentialBroker`, `DeterministicJournalBoundCredentialBroker`, `LaCortine/CredentialBroker`, `EnvironmentCredentialBroker`, capability persistence/consumption and the actual provider-response envelope producer/consumer.
- The actual command/DI registration, relevant persistence/locking helpers, `CitadelMissionFormationTest`, `CitadelFormationCorrectionTest`, `CitadelReadinessPreparationTest`, `Support/CitadelFormationFixture`, adjacent broker tests, existing formation proof scripts and accepted native correction tests.

Resolve abbreviated class names against `src/Imperium/Runtime`; trace dependencies actually used. No installed runtime, environment, credential store or private journal is a source-reading prerequisite.

## Sequence

| Step | Required result |
| --- | --- |
| FC0 | Exact authority-to-custody mapping and bounded implementation contract: persisted claim source, currentness, lock order, one-use transition, exact prepared operation and refusal points |
| FC1 | Formation-specific claim validation and durable one-use custody authorization, bound to the authoritative aggregate and actual infrastructure seam |
| FC2 | Offline operation-binding adapter and response/usage integration proof; pure preparation and unchanged refusing production DI |
| FC3 | Meaningful adverse/concurrency/interruption proof, final committed offline gates, owner-facing blocker delta and independently verifiable review packet |

Proceed through all four steps locally. Do not stop after an inventory when the defined offline implementation is possible. If a concrete policy or authority-model decision is indispensable, finish independent useful work and return the exact unresolved decision with source evidence; do not invent a positive path or weaken the accepted contract to finish.

### FC0 — exact authority and operation contract

Trace the aggregate's lock and claim lifecycle through reservation, start, custody, response sealing, settlement and recovery. Identify the actual persisted source for every decision, appointment, lease, holder, issuer, request, session and attempt field. Define which mutable facts must still be current at the final authorized start and what may subsequently be recognized only as retained completion. Never treat a claim hash or `consumed:true` supplied by a caller as proof.

Bind the exact request bytes and prepared wire bytes (or a precise immutable representation whose serialization is checked immediately before dispatch), their digests, destination, model, provider, approved public credential reference/operation, per-call and aggregate limits, expiry and authority source. A model/provider label alone does not bind a wire operation. Reject changes between inspection and dispatch, redirects and implicit alternate destinations. No provider is selected by a fixture or inherited DeepSeek configuration.

Document the exact addition needed if existing claim terms cannot bind those facts. An internal schema change must be versioned where required, with old retained evidence/recovery preserved and no new authority inferred for old records. No unchecked public callback may substitute an arbitrary operation after validation. The runtime chooses the approved adapter; callers cannot choose a root, clock, verifier, broker or live transport.

Keep B1 explicit: an output limit or local HTTP deadline does not establish remote cancellation or a billing ceiling. No pricing, token usage or provider guarantee is verified in this campaign. Unsupported live bounds must continue to refuse before credentials/I/O. A future B1 amendment requires an explicit owner decision; FC0 cannot enact it.

### FC1 — custody that consumes actual formation authority

Implement the narrow formation-specific consumer using the authentic aggregate claim, exact request and session terms. Do not translate into a legacy claim or grant ProtectedMission competence. Verify digest and identity equality against retained state, consumed derivation/lease, exact source/holder/issuer, valid phase, expiry, session control and final-start currentness. Define the start-to-custody race precisely; refuse fresh use after refusal, understanding completion, expiry, supersession or revocation at the established boundary.

Use the existing atomic infrastructure, with explicit lock order and no nested deadlock. Couple a durable one-use delivery/start decision to credential issue/consume so two processes, replayed callbacks or a crash cannot issue a second capability or invoke again. Prove durable replay exclusion at the actual custody seam; an in-memory flag is insufficient. Do not hold a lock across external work unless the concrete concurrency semantics and consequences are justified. Preserve uncertain state/exposure after a possible effect; no timeout reset, automatic replay, refund or deletion.

Validate all refuse-able public facts before entering credential infrastructure. Tests use recording synthetic infrastructure; never read a real credential. Ensure errors, traces, commands, requests, manifests and returned receipts exclude secrets. The application default must not acquire a usable formation credential path merely because a service is registered.

### FC2 — dormant integration and honest response accounting

Exercise `inspect -> reserve -> derive/consume -> start -> custody -> invoke -> seal -> settle/recover` through actual formation services and the new seam. Pure inspect/preparation does no credential activity, provider activity or runtime mutation. No production alias switch, environment activation flag or installed enrollment command is added. Any test-only injection belongs to restricted generated-root fixtures, not an ordinary CLI override.

Use exact synthetic bytes and recording adapters to prove binding. The synthetic provider result must identify its provenance as a fixture; it is not evidence of remote billing, cancellation, trusted usage or live compatibility. Preserve the original usage fields/order and integer limits. Malformed, absent, excessive or contradictory usage cannot reduce reserved exposure. Response bytes/ID and claim binding must remain attributable through the real envelope and admission path; a response ID or self-reported usage alone does not authenticate a provider.

Recovery reads only authenticated retained completion and creates no new capability or invocation. Keep unknown outcomes at full reserved exposure where trustworthy settlement is absent. CF01, CF02 and IR01 behavior must remain intact across direct, stale and indirect control paths.

### FC3 — proof and review package

Tests must exercise production validation and state transitions, not replicas. Include: legitimate synthetic path; forged/resealed claim; substituted session/attempt/source/holder/issuer; changed body/destination/model/credential reference/operation/limits; mismatched lease or consumed flags; wrong-phase authority; expired/revoked/superseded authority; refused/completed interview; cross-root or cross-instance evidence; unsupported provider bounds; pure preparation and default-DI zero-access spies; duplicate attempts and two-process custody contention; interruption before/after durable delivery and before/after possible invocation; unknown outcomes, retained envelope recovery and missing/untrustworthy usage. State each injection point and observable effect count. Reuse existing meaningful tests and add only missing coverage.

Run focused tests followed by the complete offline PHP suite on the final committed executable tree. Include existing CF01/CF02/IR01 and native corrections without restarting those campaigns. Use locked available dependencies and disposable roots with explicit safe environment settings; no dependency update or installation change. Verify the worktree loads its own source. If dependencies are unavailable offline, report that concrete blocker rather than borrowing installed private configuration. Run affected Python checks only if those helpers change.

Retain native exit codes, exact commands, UTC start/end, PHP/dependency identity, test counts, warnings, raw failures and before/after source status. Distinguish local executions, source inspection and historical supplied results. Preserve historical warnings with their qualifications; do not silently present them as fresh. Executable changes after testing require fresh affected gates; identify later Markdown-only changes with exact diff.

Return a report with producer-to-consumer source paths, an honest guarantee table, changed-test map, exact future owner prerequisites and commands only where implemented. Report closed software gaps separately from unresolved installed trust, nine institutional witnesses, genuine personnel judgments, qualified Castellan/Locksmith appointments and B1. No claim of live readiness follows from the synthetic demonstration.

Return complete tested/final source ZIPs and file/mode/blob/SHA-256 manifests, bounded Git bundle with explicit prerequisites, exact final/post-test diffs, public synthetic proof and raw gate logs, a complete packet SHA manifest, external ZIP SHA-256 and verification script. No real credential-adjacent material or raw private evidence enters Git or the packet. Stop at local commits for independent review; no push/PR/merge or real commissioning in the local run.

## Accepted scope and remaining flow

Success is **FORMATION_CLAIM_CUSTODY_IMPLEMENTED_OFFLINE_LIVE_TRANSPORT_BLOCKED**, with concrete evidence. It is a software prerequisite closure, not a certificate of institutional legitimacy, supportable provider guarantees or operational readiness. `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation`, `execution_authority` remain false.

After independent review: resolve genuine institutional lineage/currentness and formation-specific competence; produce legitimate personnel judgments and qualified appointments; resolve actual B1 provider/custody/bounds policy; complete public preflight; obtain separately scoped owner commissioning authorization. These gates may be prepared independently but none is bypassed. Deferred enrollment is not a claim that legacy operation can provide formation authority.

Citadel receives; Castellan interviews; “I understand” closes interview authority. Separate approval permits drafting. Separate mission approval precedes legitimate child-Curia constitution/handoff. Receiving assessment grants no execution authority. Unknown outcomes retain exposure without retry/refund; default transport refuses. The accepted Guildhall planning acceptance is not reinvestigated or promoted to formation competence.

*Imperium via solitaria est.*
