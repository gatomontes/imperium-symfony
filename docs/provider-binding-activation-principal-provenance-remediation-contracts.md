# Provider Binding Activation Principal Provenance Remediation contracts

## Status

`BATCH_1_AUTHORITY_EMPTY_CONSTITUTION_AND_LIFECYCLE_CONTRACTS_COMPLETE`

Three separately versioned contracts define the minimum lawful vocabulary without producing an
authority, principal version or lifecycle disposition:

1. `ImperatorPrincipalConstitutionAuthorityContract` defines one expiring, single-use,
   operator-originated authority for either future-instance root establishment or existing-instance
   missing-principal remediation. The routes and transitions are not interchangeable.
2. `ImperatorRuntimePrincipalVersionContract` defines one instance-bound, identity-bound,
   generation-bound principal version with explicit authority scope, lifecycle, secret exclusion
   and historical links.
3. `ImperatorPrincipalLifecycleDispositionContract` defines activate, renew, suspend, supersede,
   revoke, expire and retire vocabulary without rewriting an immutable source version.

## Exact separation

| Contract | Producer posture | Consumers | Authority-empty invariant |
| --- | --- | --- | --- |
| Constitution authority | `operator-root.imperator-principal-constitution-authority-issuer` | The exact MasterMason constitution route named by the record | Contract existence neither identifies an operator nor issues constitution authority. |
| Principal version | `mastermason.authorized-imperator-principal-version-committer` | Caller-authority issuance, lifecycle disposition and reconstruction | The record cannot constitute, renew or widen itself and contains no credential or provider-execution authority. |
| Lifecycle disposition | `operator-root.imperator-principal-lifecycle-authority` | MasterMason transition, caller-authority issuer and reconstruction | A disposition cannot rewrite history, invent a successor, issue caller authority or reconsider the activation corridor. |

The future-instance route is valid only before operationalization seals. The existing-instance route
must bind the intact operationalization seal and may remediate only the missing principal; it cannot
reopen personnel installation or alter founding personnel. A contract constant does not implement
either route.

## Lifecycle and historical posture

Principal versions distinguish `PENDING_ACTIVATION`, `ACTIVE`, `SUSPENDED`, `SUPERSEDED`, `REVOKED`,
`EXPIRED` and `RETIRED`. Dispositions distinguish the transition event from the immutable version.
Downstream attribution may remain historically readable after a terminal state, but caller-
authority issuance must fail after the disposition's effective time when the record says issuance
is prohibited.

The contracts define no current-state index, transition validator, immutable store, producer,
interruption recovery or reconstruction service. Those remain Batch 2 and later work.

## Preserved perimeter

Every `NON_AUTHORITIES` value is false. No authority or principal is issued, installed, activated,
renewed, suspended, superseded, revoked, expired or retired. No caller authority is issued. No
activation artifact or corridor disposition changes. No credential reference, secret or serialized
capability is persisted. No provider is invoked, no external I/O occurs, and Iron Gate and
Lazaretto remain closed. Provider Execution Assurance remains paused.
