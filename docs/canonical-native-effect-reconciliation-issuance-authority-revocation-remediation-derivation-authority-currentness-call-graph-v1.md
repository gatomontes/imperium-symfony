# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — derivation-authority/currentness call graph v1

`PREPARATION_BATCH_0_GRAPH_ONLY`
`ISSUANCE_DERIVATION_AUTHORITY_ABSENT`
`REVOCATION_AT_CAPABILITY_CONSUMPTION_ABSENT`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`

## Current issuance graph

```text
caller with NativeState + admissionId + at + expiresAt
  -> new NativeEffectReconciliationAuthorityIssuanceService(state)
     OR CanonicalNativeEffectCorridor::reconciliationAuthorityIssuer()
  -> issue(admissionId, at, expiresAt)
       NO decision input
       NO caller/issuance authority input
       NO typed issuance capability
       NO issuance-authority consumption
       -> SourceResolver::resolve(admissionId, at)
            immutable admission
            -> native transition commit
            -> NativeAuthority::load(authorityId, at)
                 -> NativePrincipal::load(nativePrincipal, at)
                      -> NativeRootActs::verify(root act, at)
                      -> source Imperator principal ACTIVE/lifecycle/generation
            -> callback-start + sealed-response lineage
       -> source-bounded time check
       -> deterministic authorityId + issuanceId + receiptId
       -> AtomicTransition(issuance:<authorityId>)
            -> put authority
            -> put issuance evidence
```

The graph proves source provenance and deterministic publication. The missing
edge is an independently authorized derivation act before publication.

## Exact source authority and its exhausted use

```text
NativeAuthority decision
  disposition = AUTHORIZED_EXACT_TRANSITION
  target = exact authority + TransitionContract consumer/scope/root
  authority_single_use = true
  continuing_authority = false
  -> NativeConsumer::execute(authorityId)
       -> NativeAdmission::records(authorityId, at)
            native-transition-consumption
              consumed = true
              continuing_authority = false
       -> NativeConsumer commits transition
            records.authority_consumption = that consumption
```

There is no arrow from the consumed transition authority to
`ISSUE_EXACT_RECONCILIATION_AUTHORITY`. Adding one by inference would contradict
both exact target and `continuing_authority: false`.

## Current resolve -> revoke -> consume graph

```text
Resolver::resolve(authorityId, t0)
  -> inspect(authorityId, t0)
       validate authority time/flags/seal
       validate issuance and references
       SourceResolver::resolve(admissionId, t0)
         Root anchor current
         native principal active/not revoked/not expired
         source Imperator lifecycle ACTIVE
         source generation has no effective successor
         transition/admission/callback/response exact
       claim absent
  -> capability registered by exact object in this resolver
     PID + private process-incarnation binding + expiresAt

between t0 and t1, one currentness fact changes:
  A. Root anchor revoked/expired/replaced
  B. native principal receives effective REVOKE
  C. higher source-principal generation becomes effective
  D. source-principal lifecycle disposition becomes SUSPENDED,
     SUPERSEDED, REVOKED, EXPIRED or RETIRED

ClaimDerivation::derive(capability, t1)
  -> lock reconciliation-authority scope
  -> read authority preview + assert claim absent
  -> Resolver::consume(capability, t1)
       checks exact registered object
       checks t1 < capability.expiresAt
       checks current PID/incarnation binding
       reads authority + issuance
       checks only their digests
       DOES NOT call inspect()
       DOES NOT call SourceResolver
       DOES NOT verify Root/native/source lifecycle/generation
  -> embeds consumption and publishes deterministic claim
```

The claim publication succeeds in the current model if only A, B, C or D
changed and the capability itself has not expired. `forwardComplete()` later
calls `inspect(..., true)` and may refuse. That is useful defense-in-depth but
does not authorize the earlier derived claim.

## Checks by cut

| Check | Issuance `issue()` | Capability `resolve()` | Capability `consume()` / claim publication | Forward completion | Read-only reconstruction |
| --- | --- | --- | --- | --- | --- |
| Exact issuance decision | absent | absent | absent | absent | absent |
| Separate issuance authority | absent | absent | absent | absent | absent |
| Root currentness | yes | yes | **no** | yes | historical admitted time |
| Native principal currentness | yes | yes | **no** | yes | historical admitted time |
| Source lifecycle/generation | yes | yes | **no** | yes | historical admitted time |
| Authority expiry | source-bounds output | yes | capability expiry only | yes | historical |
| Authority/issuance digests | publication | yes | yes | yes | yes |
| Typed exact-object custody | n/a | issued | yes | n/a | n/a |
| Claim absence/single winner | n/a | read-only check | yes under authority lock | consumed claim | exactly one claim required |
| Provider/credential edge | none | none | none | none | none |

## Existing reusable authority graph

```text
ImperatorPrincipalLifecycleReconstructionService(at)
  -> ACTIVE scoped principal
  -> DeterministicTransitionCallerAuthorityIssuanceService::issueImperator(
       exact transition, exact target, short expiry)
  -> domain decision
       exact basis + exact target + single-use issuance authority
  -> domain issuer governed cut
       re-read decision/basis/currentness
       consume caller/issuance authority for exact consumer
       publish issued artifact + immutable issuance evidence
```

Concrete fragments:

- `DurableProviderExecutionAuthorityIssuanceService` and
  `ProviderBindingActivationRevocationAuthorityIssuanceService`: decision,
  exact basis, `AuthorityConsumptionStore`, artifact and issuance publication;
- `OutboundEmailAuthorizationIssuanceService` and
  `ProviderBindingActivationIssuanceService`: exact caller-authority consumer,
  decision target and replay convergence;
- `CorridorDispositionPrincipalAuthorityRemediationProducer`: separately
  modeled issuance authorization, ACTIVE principal reconstruction, consumption
  and publication;
- `RecordReferenceValidator`: durable reference/digest/identity resolution; and
- `NativeEffectReconciliationAuthorityReconstructionService`: read-only
  receipt-to-Root joins that must remain non-authorizing.

## Target acyclic graph for later batches

```text
existing Root/native/effect source evidence
  -> NEW exact reconciliation issuance decision
  -> NEW separately sourced issuance authority
  -> NEW typed process-local issuance capability
  -> issuer governed cut
       currentness revalidation
       exact authority consumption
       deterministic authority + issuance publication
  -> EXISTING reconciliation resolver/capability
  -> claim governed cut
       currentness revalidation
       exact capability consumption + deterministic claim publication
  -> EXISTING claim consumption + no-provider receipt binding
  -> EXISTING read-only reconstruction
```

Prohibited edges:

```text
consumed native transition --------X-> derivation authorization
source provenance -----------------X-> issuer competence
issuer service possession ---------X-> issuance authority
deterministic output --------------X-> authorized issuance
resolution-time currentness -------X-> permanent capability validity
historical reconstruction ---------X-> new authority
```

Lock-order target: never acquire the existing reconciliation-authority lock and
then attempt to acquire an upstream issuance-decision/authority lock. The later
issuer cut should take one issuance root, revalidate sources, consume the exact
issuance authority and publish authority then issuance evidence. The existing
claim cut remains downstream. Multi-host locks, hostile storage and provider
effects remain deferred boundaries.
