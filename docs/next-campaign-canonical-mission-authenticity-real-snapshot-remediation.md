# Next campaign — Canonical Mission Authenticity and Real Snapshot Remediation

`CANONICAL_MISSION_AUTHENTICITY_REAL_SNAPSHOT_REMEDIATION_SELECTED`
`QUARANTINED_CANDIDATE_NOT_ACCEPTED`
`IMPLEMENTATION_BATCHES_0_THROUGH_3_AUTHORIZED_LOCAL`
`REFERENCE_MISSION_EXECUTION_REQUIRES_SEPARATE_OPERATOR_ORDER`
`REMOTE_PUBLICATION_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Purpose

Turn the useful mission-thread shape into an authentic governed mission. The campaign must prove
that authority originates outside the consumer, that the consumer cannot select its own verifier,
and that a read-only repository inspection operates on bytes cryptographically bound to the exact
Git snapshot named by the Operator.

The governing review is
`docs/canonical-mission-thread-post-review-blackquill-v1.md`.

Accepted implementation baseline is
`2527b33925bf3ef47d029786e60a6aefe752737b`. The quarantined branch
`codex/canonical-mission-thread-authority-provenance` is evidence only.

## Campaign sequence

### Preparation Batch 0 — authority, snapshot and consumer inventory

Map the existing planning approval and Mission Authorization chain, persistent records, service
construction, consumer injection surfaces, Git-object reading boundary, mission lifecycle state,
consumption stores, lock identities and real process-test harnesses. Classify every proposed reuse
from the quarantined branch as `RECOVERABLE_SHAPE`, `AUTHORITY_COUNTERFEIT`,
`SIMULATED_EVIDENCE`, `PROCESS_LOCAL_ONLY` or `REIMPLEMENT_REQUIRED`.

No production correction or reference-mission execution occurs in this batch.

### Batch 1 — authenticated Mission Authorization bridge

Bind the new mission dossier to an exact, persisted Mission Authorization and its verified approval
lineage. The bridge must verify Operator identity, competence, exact plan/dossier identity and
digest, affirmative disposition, scope, timing, authenticity, revocation and supersession according
to `contracts/mission-planning.md`.

A provenance label or caller assertion is never sufficient.

### Batch 2 — non-substitutable capability verification

Remove caller-supplied verifier/consumer selection from authority-bearing services. Runtime wiring
must resolve the trusted consumer and issuer identity from established custody. Bind every
capability to the authorization record, mission, action, actor, target, lifecycle transition, time,
nonce and issuer. Add explicit malicious-consumer and fabricated-authorization tests.

### Batch 3 — real Git snapshot and durable lifecycle substrate

Introduce a read-only repository snapshot adapter that resolves an exact commit, tree and blob
identity and verifies every inspected byte against Git object identity. Do not accept an arbitrary
`files` array carrying an asserted SHA.

Make transition consumption durable, atomic and required-state-bound. Replace Fiber theater with a
real multi-process contention harness over shared durable state. Batch 3 may test disposable
synthetic repositories but must not execute the selected reference mission.

### Operator Gate — mandatory separate action

Stop after a clean Batch 3 commit and full local suite. Produce an exact approval-ready mission
dossier, its digest, requested permissions, prohibitions, target commit/tree, budgets, expiry,
success criteria and the command or UI action the human Operator must perform.

The implementation agent must not run that command, create the approval, sign the order, answer an
interactive approval prompt or continue by interpreting this campaign authorization as mission
authorization.

### Batch 4 — actual read-only reference mission

Only after a separately supplied Operator authorization artifact, execute the mission against the
actual authorized Git snapshot. Record verified commit/tree/blob bindings, lifecycle transitions,
durable capability consumption, findings, evidence and terminal receipt. No target mutation,
network, provider, credential or remote action is permitted.

### Batch 5 — adversarial and process proof

Prove counterfeit consumer, fabricated provenance, wrong issuer, cross-mission substitution,
wrong lifecycle state, replay, expiry, revocation, mutation, target substitution, Git-byte
substitution, concurrent process use, crash cuts and terminal re-entry all fail closed.

### Batch 6 — separately sequenced exact-head audit

From a clean local merge of Batch 5, independently reconstruct the complete chain from Operator
approval through actual Git evidence and terminal receipt. Re-run focused and full PHPUnit and
reconcile every prose claim to exact executable evidence.

## Current authorization

The local implementation agent may execute Preparation Batch 0 and Batches 1–3, committing each
batch separately. It must stop at the Operator Gate. Batches 4–6 are defined but not yet authorized.

No branch push, pull request, merge, provider invocation, credential access, network operation,
external effect, live trial or Batch 7 action is authorized.
