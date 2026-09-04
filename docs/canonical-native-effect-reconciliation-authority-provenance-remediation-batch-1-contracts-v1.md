# Canonical native-effect reconciliation authority provenance remediation — Batch 1 contracts v1

Status: `BATCH_1_COMPLETE_CANONICAL_ISSUANCE_AND_CUSTODY_CONTRACTS_DEFINED`

This batch is declarative only. It creates no issuer, capability, authority,
issuance record, consumption, claim or receipt and changes no runtime or service
wiring. The accepted process-custody and no-provider recovery boundaries remain
unchanged.

## Authenticating source chain

The sole admissible competence path is acyclic:

1. the canonical effect admission identifies its committed `native_root`;
2. that transition commit identifies the native authority used for the commit;
3. the native authority is resolved, not copied, and must reconstruct the current
   active native Imperator principal;
4. the principal must remain backed by its verified Operator Root act;
5. issuance binds that resolved competence to the exact admission, callback-start,
   sealed response and deterministic receipt;
6. a separately stored immutable issuance record attests what was issued;
7. a resolver may deliver only a process-local typed capability for an unexpired,
   unrevoked and non-conflicting authority.

The public schema, act, holder and issuer-service strings are descriptions. The
public digest algorithm establishes byte integrity. Neither labels nor a
caller-computed digest authenticate issuance. A record located in a trusted
directory is not trusted ingress unless its issuance and source chain resolve.

## Durable evidence and custody

The v2 authority and issuance are durable immutable records. They are not bearer
capabilities. The caller supplies only an authority identifier and current time;
the resolver loads canonical evidence and revalidates competence. Its delivered
capability is exact-object, process-local, non-cloneable and non-serializable.
A fresh process can resolve an authority again only while canonical evidence is
valid and there is no conflicting consumption.

The authority is single-purpose and single-use: it can derive precisely one
deterministic forward-recovery claim. Authority-to-claim derivation and its
consumption share one locked transition. An identical source/consumer replay
converges; a different claimant conflicts.

The claim is also single-use: it can bind precisely its deterministic receipt.
Claim-to-receipt completion and consumption share one locked transition. An
identical claim/receipt replay returns the established result; it is not a fresh
or reusable authorization. A different receipt conflicts.

## Lock order and interruption contract

The staged runtime must acquire scopes in this order and never invert them:

1. admission continuation scope;
2. reconciliation authority scope;
3. exact forward-recovery claim scope;
4. immutable receipt scope.

Authority consumption and claim persistence must be one atomic transition.
Claim consumption and receipt persistence must be another. Therefore interruption
cannot leave a consumed source without its deterministic derived record, and a
retry can only converge on the identical source/consumer binding.

## Reconstruction and boundaries

Reconstruction joins receipt, claim consumption, claim, authority consumption,
authority, issuance, native authority, native principal and Operator Root act.
It is read-only: it cannot repair, issue, derive, consume, complete, invoke a
provider or resolve a credential.

Provider invocation, callback reinvocation, automatic retry, credential access,
network I/O and continuing authority are false throughout these contracts.
No-provider recovery remains the only recovery boundary. Existing v1 runtime
records remain present until the replacement batch; their labels do not satisfy
the v2 provenance contract.

## Contract surfaces

- `NativeEffectReconciliationAuthorityV2Contract`
- `NativeEffectReconciliationAuthorityIssuanceContract`
- `NativeEffectReconciliationAuthorityCustodyContract`
- `NativeEffectReconciliationAuthorityResolutionContract`
- `NativeEffectReconciliationAuthorityConsumptionContract`
- `NativeEffectForwardRecoveryClaimV2Contract`
- `NativeEffectForwardRecoveryClaimConsumptionContract`
- `NativeEffectReconciliationAuthorityReconstructionContract`

## Stage boundary

Batch 2 may implement the issuer, immutable issuance evidence, source resolver,
typed custody and atomic authority-to-claim custody. Batch 3 alone may replace
the existing public array admission and integrate the corridor. Batch 4 owns the
adversarial/application/process proof. Batch 5 owns independent terminal audit,
full PHPUnit and SHA-bound CI evidence. Batch 7 and every provider effect remain
suspended.

