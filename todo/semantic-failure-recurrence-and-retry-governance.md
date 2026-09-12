# Semantic Failure Recurrence and Retry Governance

## Status

Proposed separate campaign. Preparation may begin only after the active tool/credential abstraction
campaign has stabilized the canonical tool-operation and typed failure boundaries on which this work
depends.

This TODO records a discovered gap. Existing interruption, transactional-authority, terminal-closure,
crash-recovery, and continuous-governance controls constrain the blast radius of failed execution, but
they do not yet prove that Imperium can recognize semantically equivalent repeated failures, distinguish
zero progress from a materially different attempt, or govern retry disposition from normalized failure
evidence.

## Origin

A production-agent example demonstrated a common failure pattern:

1. a tool operation fails for a stable underlying cause;
2. cognition makes superficial input variations;
3. each variation produces the same semantic failure;
4. the runtime counts separate attempts but detects no absence of progress;
5. tokens, API calls, time, and possibly side-effect authority continue to be consumed.

The gap is not merely bounded retry count. The runtime must determine whether an attempted strategy has
materially changed, whether the same failed hypothesis is being repeated, and whether any further action
is authorized.

## Governing doctrine

- A model may propose what to try; deterministic infrastructure governs whether another attempt may open.
- A failure report is evidence, not disposition authority.
- Classification as transient, permanent-for-input, ambiguous, or indeterminate does not itself authorize
  retry, fallback, escalation, mutation, or external effect.
- No Officer may investigate its own failure, accept its own explanation, issue its own disposition, and
  execute that disposition through one undifferentiated authority.
- Retry authority must be explicit, bounded, typed, target-specific, and separately consumable.
- A changed string is not necessarily a changed strategy.
- An unknown side-effect outcome must never be treated as proof of failure.
- Cognition interruption must preserve evidence, custody, lineage, and the ability to resume only through
  a newly competent transition.

## Existing foundations to preserve

This campaign must extend rather than bypass:

- operational cognition lease interruption;
- transactional authority consumption and exhaustion;
- terminal refusal and closure;
- crash-demonstration and recovery evidence;
- continuous governance event history;
- Iron Gate execution boundaries;
- Armory tool custody and Clavium credential separation;
- sortie isolation and retirement;
- decision, disposition, authorization, and execution separation.

None of those foundations currently constitutes proof of semantic recurrence detection by itself.

## Required canonical contracts

### 1. Typed tool failure evidence

Define a versioned tool-failure artifact that records at minimum:

- tool and operation identity;
- attempted target and input identity;
- failure class and provider/native code;
- normalized semantic cause;
- retryability claim and its evidence basis;
- side-effect posture: none, confirmed absent, confirmed completed, partial, or unknown;
- attempt, authority lease, credential lease, sortie, and execution-receipt lineage;
- timestamp, provenance, and integrity digest.

Raw provider prose must remain preserved but must not be the canonical comparison surface.

### 2. Semantic failure fingerprint

Define a deterministic fingerprint over the stable attributes of a failure. It must:

- collapse cosmetic wording, casing, quoting, and formatting differences where they express the same cause;
- preserve distinctions that materially change target, operation, scope, or failure semantics;
- be versioned with its normalization policy;
- retain the pre-normalized evidence;
- refuse comparison when evidence is insufficient rather than invent equivalence.

A fingerprint is evidence of likely recurrence. It is not itself authority to interrupt, retry, or terminate.

### 3. Progress witness

Define the mechanical evidence required to claim that a proposed next attempt is materially different.
Possible progress dimensions include:

- new admissible evidence;
- changed target or schema discovered through competent inspection;
- changed tool or operation contract;
- corrected prerequisite;
- authorized fallback path;
- reduced or otherwise altered scope;
- resolved ambiguity;
- a strategy identifier whose obligations differ from the failed strategy.

Prompt paraphrase, identifier guessing, or repeated reasoning without new evidence must not count as progress.

### 4. Retry disposition

Define separately typed dispositions such as:

- bounded retry after a transient failure;
- diagnostic inspection;
- request for missing context or evidence;
- authorized fallback proposal;
- suspension pending external condition;
- escalation to a competent authority;
- terminal stop;
- side-effect reconciliation.

The component producing the failure or recurrence finding must not silently grant the authority needed to
execute the disposition.

### 5. Retry authority

Define a single-use or bounded retry authority binding:

- exact failed operation lineage;
- permitted retry or diagnostic action;
- maximum attempts and expiry;
- tool, credential, target, data, and side-effect scope;
- required delay or external condition;
- required progress witness;
- stop conditions;
- issuing authority and competent consumer.

Consumption, interruption, expiry, refusal, or terminal closure must not renew retry authority.

### 6. Side-effect reconciliation

For payments, messages, database writes, provisioning, deployment, and comparable operations:

- require idempotency identity where the provider supports it;
- distinguish request failure from operation failure;
- treat timeouts and lost acknowledgements as unknown outcomes until reconciled;
- prohibit blind replay when an effect may already have occurred;
- bind reconciliation evidence to the original execution receipt;
- require fresh authority for any compensating or repeated effect;
- preserve partial and duplicate-effect evidence without post-hoc mutation.

## Runtime behavior

The intended bounded path is:

```text
tool attempt
  -> typed result or typed failure
  -> normalize and fingerprint failure
  -> compare against attempt lineage
  -> evaluate progress witness
  -> recurrence finding
  -> interrupt current cognition/execution lease when threshold or invariant requires
  -> open no new authority automatically
  -> competent retry disposition
  -> optional separately authorized retry, diagnostic, fallback, reconciliation, escalation, or stop
```

A recurrence threshold is a policy input, not a universal constant. Consequence, side-effect posture,
failure class, and assurance tier may require interruption after the first attempt.

## Failure classes

At minimum, prove distinct handling for:

- transient infrastructure or provider failure;
- permanent failure for the current input;
- violated prerequisite or missing dependency;
- invalid tool contract or schema;
- policy or authority refusal;
- credential denial or expiry;
- ambiguous failure requiring competent review;
- unknown side-effect outcome;
- repeated semantic failure with no progress;
- materially changed strategy producing a new failure;
- hostile or malformed failure evidence.

No language-model classification may be accepted as the sole mechanical basis for another external effect.

## Telemetry and evidence

Record without granting decision authority:

- normalized failure and fingerprint;
- comparison policy version;
- recurrence count and attempt lineage;
- claimed and proven strategy change;
- token, time, tool-call, and authority consumption;
- circuit-breaker transition;
- cognition/execution lease interruption;
- disposition and issuing authority;
- side-effect posture and reconciliation outcome;
- resumption, refusal, escalation, or terminal closure.

## Verification obligations

- [ ] Prove cosmetic variations of one invalid database-object guess collapse to one semantic failure.
- [ ] Prove a materially corrected schema target is not falsely collapsed into the prior failed attempt.
- [ ] Prove repeated equivalent failure interrupts the active lease and opens no replacement authority.
- [ ] Prove an Officer cannot classify its own failure and thereby authorize its own retry.
- [ ] Prove retry count exhaustion and semantic zero-progress detection remain distinct controls.
- [ ] Prove a transient failure can be retried only under explicit bounded authority and required conditions.
- [ ] Prove permanent-for-input failure cannot consume repeated retry authority through paraphrase.
- [ ] Prove a circuit-breaker finding does not itself select fallback, escalation, or termination.
- [ ] Prove unknown side-effect outcome enters reconciliation and cannot be blindly replayed.
- [ ] Prove idempotency identity is preserved across an authorized provider-level retry.
- [ ] Prove duplicate or partial external effects remain attributable and reconstructable.
- [ ] Prove crash and recovery cannot erase recurrence history or reset the attempt budget.
- [ ] Prove interruption, expiry, refusal, or terminal closure cannot regenerate retry authority.
- [ ] Produce a complete reconstruction from canonical Imperium artifacts rather than application-log inference.

## Proposed campaign sequence

0. **Preparation and evidence inventory** — inventory current tool-result contracts, failure representations,
   attempt histories, lease interruption, retry references, execution receipts, side-effect evidence, and
   test coverage. No runtime behavior change.
1. **Canonical typed failure contract** — define failure classes, side-effect posture, raw/normalized evidence,
   and lineage.
2. **Deterministic normalization and fingerprinting** — implement versioned equivalence rules and refusal on
   insufficient evidence.
3. **Attempt lineage and progress witness** — distinguish materially changed strategy from cosmetic variation.
4. **Recurrence finding and circuit-breaker transition** — detect zero progress and interrupt without granting
   disposition or new authority.
5. **Retry disposition and transactional retry authority** — separate finding, disposition, authorization,
   consumption, and closure.
6. **Side-effect reconciliation** — add idempotency, unknown-outcome handling, duplicate/partial-effect evidence,
   and replay refusal.
7. **Crash and recovery continuity** — preserve recurrence, attempt budget, and authority exhaustion across
   interruption and restart.
8. **Telemetry and reconstruction** — expose native evidence without turning telemetry into authority.
9. **Adversarial and crash demonstrations** — prove cosmetic evasion, false equivalence resistance, authority
   non-renewal, and side-effect safety.
10. **Documentation, flow integration, and terminal handoff** — update canonical mission, tool, authority,
    Iron Gate, and incident flows.

## Preparation boundary

Batch 0 may inspect and document only. It must not:

- change runtime retry behavior;
- introduce a universal retry threshold;
- grant models circuit-breaker, fallback, escalation, or termination authority;
- alter current tool or credential contracts while their active campaign remains unsettled;
- permit external execution, payments, messages, writes, provisioning, or deployment;
- claim that existing lease interruption already proves semantic recurrence detection.

The first implementation batch may open only after the active tool/credential abstraction campaign is
terminal and Batch 0 has identified the exact canonical contracts to extend.

## Completion criterion

The campaign is complete only when Imperium can prove that repeated semantic failure cannot masquerade as
progress, cannot silently renew cognition or execution authority, cannot trigger an ungoverned disposition,
and cannot duplicate an external effect merely because the runtime failed to observe the first outcome.
