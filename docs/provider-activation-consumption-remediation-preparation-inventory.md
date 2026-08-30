# Provider Activation-Consumption Remediation — Preparation Batch 0

## Result

PREPARATION_BATCH_0_COMPLETE_COMBINED_WINNER_SELECTED_NO_RUNTIME_CHANGE

Preparation selects the smallest coherent correction: the governed admission must become the single
immutable winner for both the exact provider-binding activation and its bound durable execution
authority. The winner and transition lock must be activation-keyed, not authority-keyed.

## Current-state inventory

| Surface | Classification | Current fact | Required correction |
|---|---|---|---|
| activation identity | EXISTS_CANONICALLY | exact activation ID, digest and schema | preserve exact reference |
| declared cardinality | EXISTS_CANONICALLY | single_operation: true and ACTIVATED_UNCONSUMED | prove one activation winner |
| activation consumption | ABSENT | no activation-keyed durable winner | add exact activation consumption |
| authority consumption | EXISTS_CANONICALLY | v1 admission records it under authority lock | bind it into the combined winner |
| combined atomicity | ABSENT | no record commits both consumptions | one immutable v2 admission |
| lock scope | MISSCOPED | governed-provider-execution-admission:<authority-id> | exact activation identity |
| divergent contention | ABSENT | separate authorities use separate locks | reject a different authority under the activation winner |
| expiry and revocation | EXISTS_FRAGMENTED | some lineage checks exist | validate every constituent before first winner |
| reconstruction | EXISTS_CANONICALLY | immutable admission is evidence | reconstruct without reactivation |
| secret exclusion | EXISTS_CANONICALLY | admission holds no credential | preserve |
| provider effects | CLOSED | admission is pre-resolution and pre-I/O | preserve |
| v1 admissions | INERT_HISTORICAL | test and non-live evidence | no mutation or live adoption |
| threat model | EXISTS_CANONICALLY | one root and trusted writer | no distributed claim |

## Selected one-record winner

The v2 admission ID derives from the exact activation ID and activation digest. The activation-keyed
scope is governed-provider-execution-admission:<activation-id>. Every distinct authority referencing
one activation must contend there.

One immutable v2 admission carries the exact activation and authority references plus:

- activation_consumption.single_operation: true;
- activation_consumption.consumed: true;
- activation_consumption.continuing_authority: false;
- authority_consumption.single_use: true;
- authority_consumption.consumed: true;
- authority_consumption.continuing_authority: false; and
- local effect-start committed before credential resolution and I/O.

No separate activation-consumption record may be written before admission. Two sequential immutable
writes expose a crash cut where activation is spent without the combined winner. One immutable
admission avoids that partial dual-write state.

The transition reads the activation, derives the winner, acquires the activation lock, returns only
an existing exact activation-plus-authority winner, refuses a different authority, validates fresh
lineage, and writes the single record. A single activation scope eliminates lock-order inversion.
The authority cannot legally name another activation.

## Crash, replay and contention matrix

| Cut or race | Required result |
|---|---|
| crash before immutable put | no winner; exact current lineage may retry |
| crash during put | either no intact winner or one intact winner; partial state refuses |
| crash after winner | reconstruct without re-consumption |
| exact replay | return the same record |
| second authority, same activation | refuse before credential resolution |
| same authority, changed digest | refuse |
| expired or revoked input before first winner | refuse without winner |
| completed winner after later expiry | read-only reconstruction only |
| corrupt winner | immutable-store tamper refusal |
| every case | zero credential handling, provider invocation, bytes and external I/O |

## Versioning and revocation

GovernedProviderExecutionAdmissionContract v1 cannot gain a required activation_consumption field
without breaking its schema. Remediation requires a separately versioned v2 contract and producer.
Existing v1 admissions remain immutable historical evidence. Stationary resolution must not accept
v1 as proof of corrected combined consumption after v2 is introduced.

The immutable activation has status vocabulary but no revocation reference. Preparation does not
invent mutable REVOKED status. Batch 1 must define an exact durable revocation reference or resolved
revocation fact. Until then activation revocation is ABSENT_CONTRACTUALLY.

## Non-authorities

ACTIVATED_UNCONSUMED, issuance-authority consumption, durable-authority issuance, authority
possession, authority-scoped locking, exact request identity, idempotency, local effect-start,
credential availability, and this inventory are not a combined winner.

## Smallest sequence

1. Batch 1: v2 combined-admission contract and activation-revocation input only.
2. Batch 2: activation-keyed v2 producer with one-record commit; no credentials or provider effects.
3. Batch 3: stationary-resolution validation requires the v2 winner, retaining callback-local no-I/O proof.
4. Batch 4: adversarial proof and repeated terminal audit.

Preparation defines no runtime contract and changes no runtime behavior. It does not activate or
consume anything, issue or consume authority, handle a credential or capability, invoke a provider,
perform external I/O, send bytes, authorize retry, migrate a command, open Iron Gate or Lazaretto, or
claim provider outcome. UNKNOWN_REPLAY_PROHIBITED remains mandatory after future provider effect-start.
