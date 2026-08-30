# Provider Binding Activation Corridor Disposition caller-authority validation

## Status

`BATCH_2_CALLER_AUTHORITY_CONTRACT_AND_FAIL_CLOSED_VALIDATORS_COMPLETE`

`ActivationCorridorDispositionCallerAuthorityContract` defines one expiring, single-use authority
for `DECIDE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION`. It binds one canonical principal version, exact
corridor target, complete evidence dossier, eligibility assessment and proposed disposition.
Issuance and consumption winner requirements are explicit; no authority is issued or consumed.

`ActivationCorridorDispositionContractValidator` validates caller-supplied sealed fixtures only. It
has no store, registry, producer, issuer, consumer, current-state index or reconstruction behavior.

## Fail-closed target rules

- The target binds one instance, corridor identity and positive corridor generation.
- Its scope names the provider-binding activation corridor and exact artifact/evidence set digests.
- The terminal custody refusal and source campaign are exact references.
- Target validation creates no authority and activates no binding.

## Fail-closed dossier rules

- The dossier binds the exact target and active-principal references.
- Activation decision, activation authority and activation lease references are all required.
- Exactly six unique transition-interruption references are required.
- At least one exact stranded-artifact disposition reference is required.
- Process-loss, credential/secret-exclusion and terminal-refusal references are required.
- Only a `COMPLETE`, conflict-free, read-only dossier is accepted as an authority basis.
- Validation creates no authority, disposition or source-artifact mutation.

## Fail-closed eligibility rules

- Only `QUARANTINED_PENDING_REMEDIATION` and `RETIRE_CORRIDOR` are candidates.
- Every common predicate must be explicitly true, including effective active-principal state,
  corridor-disposition scope, lifecycle eligibility, exact evidence and continuing refusal.
- Quarantine must leave the corridor unusable, create no remediation authority, preserve history
  and require new authority for reconsideration.
- Retirement must be marked irreversible, leave the corridor unusable, preserve history, make
  outstanding artifacts non-authorizing and require new authority for a replacement corridor.
- Only an `ELIGIBLE` fixture can form a future caller-authority basis. Eligibility still selects and
  seals nothing.

## Fail-closed caller-authority basis

The caller authority must:

- permit only `DECIDE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION`;
- bind the same instance, exact principal, target, dossier, eligibility and proposed disposition;
- be unconsumed, non-continuing, single-use and exercisable;
- expire after issuance and within fifteen minutes;
- require both an issuance winner and a consumption winner;
- bind a canonical v2 principal fixture whose status is `ACTIVE`, whose effective time has arrived,
  whose expiry has not arrived and whose authority expiry does not exceed principal expiry;
- require `corridor_disposition_authority=true`; and
- exclude persisted credential references, secrets and serialized capabilities.

The existing activation-principal constitution route still fixes `corridor_disposition_authority`
to false. Therefore a validator-acceptable fixture does not prove that an eligible instance
principal exists or can currently be produced.

## Preserved perimeter

No target, dossier, eligibility assessment, principal or caller authority is stored or produced.
No principal or binding is activated; no caller authority is issued or consumed; no disposition is
selected or sealed; no activation artifact is mutated, consumed, revoked or reinterpreted; no
successor authority is created; no capability or credential is handled; no credential platform is
selected; no provider is invoked; no external I/O occurs; and Iron Gate and Lazaretto remain
closed. `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains authoritative and Provider Execution
Assurance remains paused.
