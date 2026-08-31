# Provider Binding Successor Production Adoption Preparation Batch 0 inventory

## Result

PREPARATION_BATCH_0_COMPLETE_PRODUCTION_SUCCESSOR_DECISION_AND_ATOMIC_ADOPTION_ROUTE_REQUIRED

## Existing truth

The reconciled lifecycle successor is exact, immutable, operation-scoped and
secret-free, but its producer posture is
`future-authorized-atomic-operation-scoped-successor-transition`. The
reconciliation terminal audit classifies it as offline evidence rather than a
production decision, live activation, adoption route, execution authority or
provider-call admission.

The exact executor-principal generation is durably ACTIVE. The original provider
implementation binding remains BOUND_INACTIVE. The legacy single-operation
activation remains `ACTIVATED_UNCONSUMED`, is bound to an
`ATTESTED_INERT` principal, and may not be promoted to the reconciled
ACTIVE-principal lineage.

The v2 combined execution admission atomically consumes the legacy activation
and durable execution authority and commits effect-start before credential
resolution or external I/O. It validates the legacy inert boundary, inert
principal and inactive binding. It does not accept the reconciled lifecycle
successor.

## Authority and custody classification

| Concern | Current fact | Classification |
| --- | --- | --- |
| Competent decision owner | No production producer for the reconciled successor is named | Missing exact production decision principal |
| Decision input | Caller-supplied, authority-empty reconciliation input | Evidence only; not a production decision |
| Successor-creation authority | Shape exists only inside offline evidence | No canonical issuer, custody route or exercisable production record |
| Exact executor principal | Reconciled lineage names the durably ACTIVE generation | Identity is durable evidence, not process-local credential capability |
| Credential custody | Stationary inside the credential-owning executor | Must remain same-process and must never become durable successor material |
| Existing combined admission | Consumes legacy activation plus execution authority | Not authorized to consume the reconciled successor |
| Live adoption | No production consumer or migration join exists | Absent |

Credential possession is not execution authority. Durable authority identifies
the exact permitted transition; process-local capability identity exists only
inside the credential-owning executor. Neither may be reconstructed from the
other.

## Required production ordering

A future implementation must prove one activation-keyed atomic winner that
validates an exact competent decision, consumes exactly one single-use
successor-creation authority and immutably creates exactly one reconciled
successor. No credential resolution, provider invocation, external I/O or
effect-start may occur in that transition.

Only after that successor exists may a separately authorized adoption step
consider whether and how the execution-admission corridor consumes or references
it. Successor creation must not be folded silently into the current v2 combined
effect-start admission.

## Failure and lifecycle posture

- Crash before the atomic put leaves no successor and no consumed authority.
- Crash after the atomic put reconstructs the one immutable winner read only.
- Exact replay converges; changed evidence conflicts.
- Same-root contention yields one winner.
- Expired or revoked decision lineage and authority refuse before creation.
- Reconstruction never repairs, replaces, promotes or reissues authority.
- Revocation after a completed winner cannot rewrite history; downstream
  eligibility must be evaluated separately.
- `UNKNOWN_REPLAY_PROHIBITED` remains binding after any possible effect-start.

## Secret exclusion and threat model

Successor, decision, authority, consumption and adoption records must exclude
credential bytes, environment-variable names, callback or object identity, and
process-local capability material recursively. The trusted-writer-root model
does not turn arbitrary filesystem possession into decision, execution,
credential or provider authority.

## Candidate boundary postures

| Posture | Decision |
| --- | --- |
| Mutate the original binding globally to BOUND_ACTIVE | Rejected |
| Promote legacy ACTIVATED_UNCONSUMED evidence | Rejected |
| Let the current combined admission synthesize the successor | Rejected |
| Treat offline fixtures as production authority | Rejected |
| Create an exact decision and single-use successor authority, then atomically consume and create | Selected for later batches |
| Adopt the completed successor into execution admission as a separate explicit join | Required after production creation proof |

## Non-authorities

Preparation creates no production decision, runtime contract, successor,
activation, authority, consumption, provider-call admission or live adoption. It
may not activate a provider binding. It may not issue or consume authority. It
may not handle or resolve a credential or capability. It may not invoke a
provider. It may not perform external I/O. It may not migrate a live command.
Iron Gate and Lazaretto remain closed.

## Smallest lawful sequence

1. Batch 1: authority-empty contracts for the exact competent production
   decision, single-use successor-creation authority and explicit adoption
   target.
2. Batch 2: pure fail-closed validators and segregated caller-supplied offline
   fixtures.
3. Batch 3: interruption, replay, conflict, expiry, revocation and same-root
   contention proof.
4. Batch 4: read-only aggregate reconstruction and lineage audit.
5. A later separately authorized production batch may implement the atomic
   decision-authority consumption and successor-creation winner.
6. Only after that proof may an explicit live-adoption join be considered.

Only Batch 1 authority-empty contracts may next be considered.
