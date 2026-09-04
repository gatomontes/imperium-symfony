# Canonical Native Effect Reconciliation Authority Provenance Remediation — authority provenance/call graph v1

`PREPARATION_BATCH_0_AUTHORITY_PROVENANCE_GRAPH_ONLY`
`ARBITRARY_CALLER_ARRAY_PRESENTLY_ADMITTED`
`NO_RUNTIME_OR_PROVIDER_EFFECT`

## Current graph: integrity is promoted to authority

```text
test/helper caller
  | reads immutable admission, callback-start, response
  | copies public SCHEMA / HOLDER / ISSUER / ACT constants
  | chooses authority_id, effective_at, expires_at
  v
NativeState::seal(caller array)
  | public deterministic digest
  | proves only byte consistency
  v
NativeEffectForwardRecoveryClaimAdmissionService::admit(array, at)
  | validate exact keys/constants/false flags/time/public seal
  | read admission, callback-start, response from ImmutableRecordStore
  | validate effect lineage and deterministic receipt ID
  | DOES NOT resolve issuer, issuance, principal, Root or revocation
  v
AtomicTransition("...reconciliation-authority:" + hash(authority_id))
  -> ImmutableRecordStore::put(AUTHORITIES, caller authority)
  -> ImmutableRecordStore::put(CLAIMS, derived durable claim)
  v
claim_id
  v
NativeEffectForwardRecoveryService::forwardComplete(claim_id, at)
  -> read claim
  -> lock admission-continuation scope
  -> lock exact claim scope
  -> validate claim constants/false flags/time
  -> resolve admission/callback/response and exact digests
  -> receipt exists? return it
  -> NativeEffectReceiptBindingService::bind(...)
       -> immutable deterministic receipt
       -> no callback, provider or credential input
```

The effect lineage below the admission boundary is real. The authority lineage
above it is fictional. `AUTHORITIES` is a trusted persistence destination, not
an authenticated source, because the first write is made from caller bytes.

## Exact caller construction sites

```text
ContinuationExclusivity Batch 3
  admitRecoveryClaim -> NativeState::seal(array) -> new AdmissionService -> admit

ContinuationExclusivity Batch 4
  addForwardRecoveryClaim -> NativeState::seal(array) -> new AdmissionService -> admit
  -> writes claim ID into a cross-process fixture
  -> canonical_native_effect_worker forward-recover -> forwardComplete

ProcessCustody Batch 3
  recoveryAuthority -> NativeState::seal(array)
  admitRecoveryAuthority -> new AdmissionService -> admit

ProcessCustody Batch 4
  reconciliationAuthority -> NativeState::seal(array)
  -> new AdmissionService -> admit
```

No production source constructs this authority schema. No production command
calls `admit()` or `forwardComplete()`.

## Current serialization, persistence, replay and recovery graph

```text
authority array
  -> freely copied / JSON encoded / PHP serialized
  -> no custody or private issuer fact

admit exact same array
  -> same authority ID + same response digest
  -> same claim ID
  -> immutable puts converge

admit same lineage under different caller authority IDs
  -> different outer authority locks
  -> different stored authority records
  -> different claim IDs
  -> no authority consumption conflict

forwardComplete distinct claims for same admission
  -> shared admission-continuation lock serializes receipt binding
  -> one deterministic receipt converges
  -> neither authority nor claim is consumed

forwardComplete same claim before expiry
  -> returns same receipt
  -> this is reusable claim validation, even though the result is idempotent

reconstruct(receipt ID)
  -> read-only receipt
  -> no claim, callback, credential or provider
```

## Current lock and interruption graph

```text
claim admission:
  reconciliation-authority:<hash(authority_id)>
    -> immutable:<hash(AUTHORITIES)>
    -> immutable:<hash(CLAIMS)>

forward recovery:
  canonical-native-effect-continuation:<hash(admission_id)>
    -> canonical-native-effect-forward-recovery:<hash(claim_id)>
       -> reads source records
       -> immutable:<hash(RECEIPTS)> through binder
```

Interruption after authority put and before claim put leaves a durable authority
without a claim; exact resubmission can finish because there is no consumption.
Interruption after a future authority-consumption cut must instead permit only
the same deterministic claimant/source to finish. Interruption after a future
claim-consumption cut must permit only completion/return of the exact receipt.

## Reusable canonical provenance graph

```text
Operator Root lineage records
  -> Imperator constitution authority / principal version
  -> ImperatorPrincipalLifecycleReconstructionService (read only)
       -> effective ACTIVE principal + authority_scope
  -> DeterministicTransitionCallerAuthorityIssuanceService::issueImperator
       -> source principal resolved
       -> exact transition + target + short expiry
       -> immutable caller authority
  -> domain decision service
       -> exact source/basis + issuance authority
  -> domain issuance service
       -> resolve decision and referenced records
       -> consume caller/issuance authority
       -> persist issued artifact and issuance evidence
```

Useful concrete patterns are:

- `DurableProviderExecutionAuthorityIssuanceService`: exact decision and basis
  resolution, `AuthorityConsumptionStore`, separate authority and issuance
  directories, issued-artifact reference and no-effect issuance evidence;
- `OutboundEmailAuthorizationIssuanceService`: active Imperator caller
  authority, source request/decision lineage and one immutable issuance result;
- `ProviderBindingActivationIssuanceService` and its revocation issuer: exact
  source decision, current binding/principal/boundary and validity joins;
- `RecordReferenceValidator::resolve()`: durable reference-to-source
  resolution with digest and optional identity equality;
- `ImperatorPrincipalLifecycleReconstructionService`: read-only current-status
  reconstruction; and
- `AuthorityConsumptionStore`: deterministic authority-ID lock and exact
  source/consumer-bound consumption replay.

The `ImperatorPrincipalProvenanceFixtureStore` is not a pattern for authenticated
ingress. It validates and stores evidence fixtures. Treating its successful put
as proof that a live Operator Root issued the bytes would recreate the same
mistake under a grander filename.

## Smallest target graph for later batches

```text
active Imperator principal reconstruction
  -> exact reconciliation issuance decision/caller authority
  -> ReconciliationAuthorityIssuer
       -> resolve admission + callback-start + sealed-response
       -> resolve scoped principal + Root lineage
       -> check expiry/revocation
       -> consume issuance authority
       -> put canonical authority
       -> put issuance evidence

authority ID
  -> ReconciliationAuthorityResolver
       -> read canonical authority (never caller bytes)
       -> read issuance evidence and source principal
       -> check exact identity/digests/current revocation
       -> return resolver-issued non-transferable typed custody

ClaimAdmission(resolved custody, at)
  -> consume exact authority for exact lineage/consumer
  -> put deterministic durable claim

ForwardRecovery(claim ID, at)
  -> resolve claim + authority consumption/source
  -> before first mutation, consume claim exactly once
  -> bind deterministic receipt
  -> exact retry may finish/return only that receipt

ReceiptReconstruction(receipt ID)
  -> read only
```

Required prohibitions:

```text
caller array ------------------------------X-> claim admission
caller seal -------------------------------X-> issuer authentication
constant issuer prose ---------------------X-> principal resolution
authority directory write by admission ----X-> trusted ingress
receipt replay ----------------------------X-> reusable authorization
recovery claim ----------------------------X-> callback/provider/credential
```

## Deployment boundary

This graph is local-host and cooperative-filesystem only. It does not claim
distributed locks, hostile-host storage integrity, authenticated live Root
ceremony, credential safety beyond the already accepted no-provider API, or any
provider effect.

