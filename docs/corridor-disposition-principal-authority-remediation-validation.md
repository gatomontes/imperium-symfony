# Corridor Disposition Principal Authority Remediation validation

## Status

`BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE`

`CorridorDispositionPrincipalAuthorityRemediationContractValidator` validates caller-supplied
offline fixtures for the scope grant, pending successor transition and caller-authority issuance
authorization. `CorridorDispositionPrincipalAuthorityRemediationFixtureStore` persists only validated
fixtures beneath a segregated evidence path. It is no live registry, current-state index, producer,
issuer or consumer.

## Scope grant validation

The grant must bind an exact digest-intact active source principal without corridor scope, one exact
next generation, unchanged identity-bearing principal references, only
`corridor_disposition_authority=true`, and exact preservation of every non-corridor scope value.
Operator Root identity/decision fields, rationale, single use, issuance and consumption winners,
expiry and revocation refusal, a fifteen-minute maximum window and absence of prior consumption are
mandatory.

## Successor validation

The transition must bind the exact grant and its source/successor references, enforce generation
continuity by incrementing exactly once, preserve identity, binding and non-corridor scope, add only
corridor scope, and remain at `PENDING_ACTIVATION`. Grant consumption is represented, while source mutation, premature source
supersession, caller-authority issuance and continuing authority must remain false. Separate
activation authority remains mandatory.

## Issuance authorization validation

The authorization must bind a digest-intact, effective active-principal fixture matching the exact
successor generation and carrying corridor scope without persisted credentials or capabilities. It
also binds the exact activation disposition, target, complete dossier, eligible candidate and result
authority identity. Candidate agreement, principal lifetime, fifteen-minute maximum expiry, absence
of revocation/consumption, both winner requirements and
`REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` fail closed.

## Fixture-store meaning

Exact replay returns the same immutable record; a changed record at the same identity conflicts.
Storage demonstrates fixture integrity only. It does not identify an Operator Root, establish a
canonical principal generation, issue or consume authority, activate a principal or satisfy the
Reconsideration Batch 5 return gate.

## Preserved perimeter

No live authority, principal, target, dossier, eligibility or disposition is created. No producer,
issuer, consumer, current-state registry or reconstruction path is implemented. No activation
artifact is mutated; no capability or credential is handled; no provider is invoked; no external
I/O occurs; and Iron Gate and Lazaretto remain closed. Provider Execution Assurance remains paused.
