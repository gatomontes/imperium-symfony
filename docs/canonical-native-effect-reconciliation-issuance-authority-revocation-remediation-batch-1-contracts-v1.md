# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 1 contracts v1

`BATCH_1_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_CURRENTNESS_CONTRACTS_DEFINED`
`CONTRACT_ONLY_AUTHORITY_EMPTY`
`BATCH_2_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Batch 1 defines versioned law only. It creates no decision, issuance authority,
capability, reconciliation authority, issuance evidence, consumption, claim,
receipt or reconstruction result. It adds no service, store, validator, resolver,
producer, consumer, container definition or command and changes no existing
runtime behavior.

Four stages remain: Batch 2 rooted decision/custody/atomic consumption, Batch 3
issuer and claim-use enforcement, Batch 4 adversarial/application proof, and
Batch 5 separately sequenced terminal audit. Batch 2 is not authorized.

## Exact decision and competent issuer

`NativeEffectReconciliationIssuanceDecisionContract` defines one disposition for
`DECIDE_EXACT_RECONCILIATION_AUTHORITY_ISSUANCE`. The decision binds a separately
provenanced competent issuer, its exact competence, the target and holder, the
admission/callback/response lineage, native authority/principal/transition and
Operator Root references, one validity window and one replay identity.

The decision is not self-issuing and is not the issuance authority. Competence
must be present and current at decision time and later revalidated at the issuer
cut. The following remain evidence or possession facts, never authorization:

- source provenance;
- possession or construction of the issuer service;
- historical approval;
- deterministic output; and
- the already-consumed native transition authority whose exact transition use
  records `continuing_authority: false`.

## Single-purpose issuance authority

`NativeEffectReconciliationIssuanceAuthorityContract` defines one separately
sourced, single-purpose, single-use authority permitting only
`ISSUE_EXACT_RECONCILIATION_AUTHORITY`. It binds the exact decision, issuer,
holder, target authority identity/schema/digest, admission/callback/response and
native/Root lineage, deterministic receipt, effective and expiry bounds, and
replay identity. It is never continuing authority.

Neither an equivalent array nor matching public fields establish custody.
`NativeEffectReconciliationIssuanceCapabilityCustodyContract` describes future
typed delivery as process-local, exact-object, non-cloneable and
non-serializable. Durable evidence is not a capability; copied fields do not
transfer custody; a fresh process cannot reuse old custody. The Batch 1 class has
no capability value, constructor, issuer or delivery method and its status is
`CONTRACT_ONLY_NOT_DELIVERED`.

## Atomic consumption and deterministic publication

`NativeEffectReconciliationIssuanceConsumptionPublicationContract` fixes one
issuance-root lock identity and a globally downstream order:

1. reconciliation issuance root;
2. issuance-authority consumption;
3. reconciliation-authority publication;
4. reconciliation issuance-evidence publication;
5. reconciliation-authority claim use;
6. forward-recovery claim consumption; and
7. receipt publication.

No downstream claim lock may be held while attempting an upstream issuance
lock. No external I/O occurs under any lock.

The governed publication order is consumption, authority, then issuance
evidence. Before consumption there is no output. After consumption, an
interruption permits only the exact decision/authority/issuer/holder/target/
lineage/window/replay retry to finish the same deterministic authority and
issuance evidence. After authority but before issuance evidence, the same rule
finishes the one orphaned publication. Once complete, retry returns the
established result. Any changed decision, authority, issuer, target, lineage,
window or replay identity is `REFUSED_CONFLICTED`; no retry grants new power.

This is a contract for the future atomic cut. It consumes and publishes nothing.

## Present-tense currentness at both use cuts

`NativeEffectReconciliationAtUseCurrentnessContract` requires current resolution
inside both governed cuts before consumption/publication:

- issuer consume-and-publish; and
- claim-use consume-and-publish.

Both cuts revalidate current Operator Root identity and its untimestamped
revocation flag, native-principal generation/activation/revocation, source
generation and lifecycle, exact effect lineage and immutable record digests.
Currentness is not serialized into custody and resolution-time success is not
permanent authority.

Operator Root revocation, native-principal revocation, source-generation advance
and source-lifecycle change are independently mutable. They require present-tense
revalidation. RR02, RR05 and RR11 remain transitively bounded expiry
preservation cases, not at-use stale-capability races.

RR07–RR10 retain distinct event/refusal meanings:

| Source event | Exact refusal |
| --- | --- |
| `SUSPEND` | `REFUSED_SOURCE_SUSPENDED` |
| `SUPERSEDE` | `REFUSED_SOURCE_SUPERSEDED` |
| `REVOKE` | `REFUSED_SOURCE_REVOKED` |
| `EXPIRE` | `REFUSED_SOURCE_EXPIRED` |
| `RETIRE` | `REFUSED_SOURCE_RETIRED` |
| v3 migration/currentness requirement | `REFUSED_SOURCE_MIGRATION_REQUIRED` |

A later forward-completion refusal cannot retroactively authorize an already
derived claim.

## Result and refusal vocabulary

`NativeEffectReconciliationIssuanceOutcomeContract` permits only `AUTHORIZED`,
`EXACT_RETRY_CONVERGED` or `REFUSED`. Its exact refusal vocabulary distinguishes
missing and counterfeit decision/authority/capability; expired, replayed,
substituted, consumed and stale inputs; current Root/native revocation; each
source lifecycle outcome; migration-required source records; and conflict.
`EXACT_RETRY_CONVERGED` names completion or return of the already-established
result, never another authorization.

## Read-only historical reconstruction

`NativeEffectReconciliationHistoricalReconstructionContract` joins receipt back
through both consumptions, claim, reconciliation authority/issuance, issuance
authority/decision, native/source history and Operator Root evidence. It creates,
repairs, resolves, delivers, consumes and publishes nothing, grants no continuing
authority and reaches no provider or credential.

CUR08A remains `EXISTS_FRAGMENTED`: Operator Root revocation is a current,
untimestamped eligibility fact. If the Root is currently revoked, historical
reconstruction refuses `REFUSED_OPERATOR_ROOT_CURRENTLY_INELIGIBLE`. Batch 1
does not repair that audit-reachability limitation.

CUR08B is distinct: timestamped native/source lifecycle history may reconstruct
read only at the historical cut while current Operator Root eligibility remains
satisfied. Historical reconstruction is evidence, not power.

## Contract surfaces

- `NativeEffectReconciliationIssuanceDecisionContract`
- `NativeEffectReconciliationIssuanceAuthorityContract`
- `NativeEffectReconciliationIssuanceCapabilityCustodyContract`
- `NativeEffectReconciliationIssuanceConsumptionPublicationContract`
- `NativeEffectReconciliationAtUseCurrentnessContract`
- `NativeEffectReconciliationIssuanceOutcomeContract`
- `NativeEffectReconciliationHistoricalReconstructionContract`

Each surface is constants-only with a private constructor. None imports or calls
runtime state, atomic transitions, stores, issuers, resolvers, consumers,
reconstruction services, random sources or external I/O.

## Boundary

No production issuer, resolver, claim, recovery, corridor, container or command
behavior changes in Batch 1. No Root ceremony, credential, provider, AgentMail,
network, mission, live trial, email, Iron Gate or Lazaretto action occurs.
Formal closure remains refused and Batch 7 remains suspended.

Stop at
`BATCH_1_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_CURRENTNESS_CONTRACTS_DEFINED`.
