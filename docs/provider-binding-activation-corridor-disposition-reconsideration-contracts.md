# Provider Binding Activation Corridor Disposition Reconsideration contracts

## Status

`BATCH_1_AUTHORITY_EMPTY_TARGET_DOSSIER_AND_ELIGIBILITY_CONTRACTS_COMPLETE`

Three separately versioned contracts define the minimum disposition vocabulary without identifying
a live target, assembling evidence, assessing eligibility, issuing authority or sealing an outcome:

1. `ActivationCorridorDispositionTargetContract` identifies one instance-bound corridor generation
   and digest-bound historical artifact/evidence sets under the continuing terminal custody refusal.
2. `ActivationCorridorDispositionEvidenceDossierContract` defines one read-only dossier spanning
   the active principal, activation chain, all six interruption records, stranded-artifact records,
   process-loss proof, secret exclusion and the terminal custody refusal.
3. `ActivationCorridorDispositionEligibilityContract` defines the predicates and consequences for
   `QUARANTINED_PENDING_REMEDIATION` and `RETIRE_CORRIDOR` without choosing either outcome.

No validator, store, producer, assessor, caller-authority transition, consumer, reconstruction
service or disposition service is implemented.

## Exact separation

| Contract | Producer posture | Consumers | Authority-empty invariant |
| --- | --- | --- | --- |
| Corridor target | `imperator.activation-corridor-target-identifier` | Dossier assembly, eligibility and a future caller-authority issuer | Identifying a corridor cannot activate a principal or binding, issue authority, select an outcome, mutate artifacts or handle credentials. |
| Evidence dossier | `imperator.activation-corridor-read-only-evidence-dossier-assembler` | Eligibility, a future caller-authority issuer and future producer | Dossier assembly is read-only; it cannot repair evidence, reinterpret the refusal, create authority or seal a disposition. |
| Disposition eligibility | `imperator.activation-corridor-disposition-eligibility-assessor` | A future caller-authority issuer, producer and terminal auditor | Eligibility is not the Imperator's decision and cannot select or seal an outcome, consume authority or create a successor. |

Every `NON_AUTHORITIES` value is false. Contract and posture names do not implement their named
actors. Repository schemas and test fixtures are not instance evidence.

## Exact target

The target must bind one `instance_id`, `corridor_id`, `corridor_generation`, the terminal refusal,
the source campaign and exact digests for the activation-artifact set and historical-evidence set.
It cannot be inferred from a single activation authority, lease, failed custody assessment or
campaign document.

## Eligible evidence dossier

A complete dossier must reserve exact references for:

- the canonical principal generation and its effective active-state evidence;
- the activation decision, activation authority and activation lease;
- exactly six activation decision/issuance interruption records;
- all applicable stranded-artifact dispositions;
- process-loss capability-custody evidence;
- credential-reference and secret-exclusion evidence; and
- `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE`.

The dossier classifications are `COMPLETE`, `INCOMPLETE`, `CONFLICTED` and `REFUSED`. Completeness
does not establish authority or eligibility, and an absent or non-active principal remains refusal.

## Candidate eligibility and consequences

Both candidates require an intact exact target, a complete intact dossier, no conflicting
disposition, an effectively `ACTIVE` unexpired and unrevoked principal whose canonical scope permits
corridor disposition, and the continuing custody refusal. Source mutation and successor authority
remain prohibited.

`QUARANTINED_PENDING_REMEDIATION` must leave the corridor operationally unusable, create no
remediation authority, preserve historical evidence and require new authority for any future
reconsideration.

`RETIRE_CORRIDOR` must leave the corridor operationally unusable, preserve historical evidence,
make outstanding artifacts non-authorizing, prohibit an implicit replacement and require new
authority for any replacement corridor. The contract records an irreversible-retirement
consequence but does not perform retirement.

Eligibility classifications are `ELIGIBLE`, `INELIGIBLE`, `INCOMPLETE`, `CONFLICTED` and `REFUSED`.
An `ELIGIBLE` assessment would still not select or authorize the candidate disposition.

## Preserved perimeter

The terminal custody refusal remains authoritative. No principal or binding is activated; no caller
authority is issued or consumed; no target, dossier, eligibility assessment or disposition record
is produced; no activation artifact is mutated, consumed, revoked or reinterpreted; no successor
authority is created; no capability or credential is handled; no credential platform is selected;
no provider is invoked; no external I/O occurs; and Iron Gate and Lazaretto remain closed. Provider
Execution Assurance remains paused.
