# Corridor Disposition Principal Authority Remediation contracts

## Status

`BATCH_1_AUTHORITY_EMPTY_SCOPE_SUCCESSOR_AND_ISSUANCE_CONTRACTS_COMPLETE`

Three separately versioned contracts define the missing vocabulary without producing an authority,
principal generation, activation or caller authority:

1. `ImperatorCorridorDispositionScopeGrantContract` describes one expiring, revocable, single-use
   Operator Root grant for an exact successor generation and only the corridor-disposition scope
   delta.
2. `ImperatorCorridorDispositionScopeSuccessorContract` describes the mechanical consume-to-commit
   result: one immutable next generation held at `PENDING_ACTIVATION`, with identity, binding and all
   non-corridor scope preserved.
3. `ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract` describes one later,
   expiring issuance authorization bound to the effectively active successor, its activation
   disposition and the exact target, dossier, eligibility and candidate outcome.

## Exact separation

| Contract | Sole producer posture | Consumer posture | Authority-empty invariant |
| --- | --- | --- | --- |
| Scope grant | `operator-root.imperator-corridor-disposition-scope-grant-issuer` | Future MasterMason successor committer | The contract identifies no live Operator Root, issues no grant and does not widen the source generation. |
| Scope successor | Future MasterMason successor committer | Lifecycle authority, later issuance authorizer and read-only reconstruction | The successor starts pending; committing it neither activates it nor reinterprets ordinary lifecycle supersession. |
| Issuance authorization | Effectively active future Imperator issuance authorizer | Future corridor caller-authority issuer | Authorization neither issues the result authority nor selects or seals the corridor outcome. |

The scope grant binds exact source and successor generations, exact identity-bearing references,
only `corridor_disposition_authority=true`, unchanged non-corridor scope, rationale, expiry,
revocation posture and issuance/consumption winner requirements. It is not constitution authority
and cannot be used for generation one, missing-principal repair, renewal or ordinary supersession.

The successor contract requires generation continuity, identity and binding preservation, separate
activation authority and `PENDING_ACTIVATION`. Its source remains immutable and is not marked
superseded merely because a pending successor was committed. A later lifecycle transition must
establish the unique active generation without silently inheriting authority from this contract.

The issuance-authorization contract binds the active successor and its exact activation disposition,
then binds the same target, dossier, eligibility and proposed disposition required by the existing
caller-authority contract. `REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE` remains explicit. The
authorization is not the caller authority and cannot issue or consume itself.

## Expiry, revocation and return gate

Both authority-shaped contracts include issuance time, expiry, revocation, single-use,
exercisability, non-continuation and winner requirements. Batch 2 validators must fail closed on
missing, expired, revoked, consumed, mismatched or competing fixtures. Contract existence proves no
instance-specific principal or authority.

These contracts do not satisfy the Reconsideration Batch 5 return gate. That requires later canonical
production and read-only proof of one unique active successor plus one exact intact caller authority.

## Preserved perimeter

Every `NON_AUTHORITIES` value is false. No validator, store, producer, issuer, consumer, current-state
index or reconstruction behavior is implemented. No scope grant or caller authority is issued or
consumed; no principal is created, superseded or activated; no disposition is selected or sealed;
no activation artifact is mutated; no capability or credential is handled; no provider is invoked;
no external I/O occurs; and Iron Gate and Lazaretto remain closed. Provider Execution Assurance
remains paused.
