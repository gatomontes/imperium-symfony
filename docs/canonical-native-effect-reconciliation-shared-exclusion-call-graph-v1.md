# Canonical Native Effect Reconciliation Shared-Exclusion — accepted-base call graph v1

`PREPARATION_BATCH_0_CALL_GRAPH_ONLY`

## Current accepted chain

```text
issue(admissionId, at, expiresAt)
  -> SourceResolver::resolve()                         [NO LOCK]
     -> immutable admission/transition reads
     -> NativeAuthority::load()
        -> NativePrincipal::load()
           -> NativeRootActs::verify()
           -> source principal/lifecycle/generation reads
     -> callback/response reads
  -> derive deterministic authority bytes             [NO LOCK]
  -> reconciliation-issuance target lock               [DISJOINT]
     -> immutable authority put
     -> immutable issuance-evidence put

resolver::resolve(authorityId, t0)
  -> inspect()                                         [NO SHARED LOCK]
     -> authority/issuance validation
     -> SourceResolver::resolve() currentness
  -> resolver-private capability custody

claimDerivation::derive(capability, t1)
  -> reconciliation-authority target lock              [DISJOINT]
     -> claim-absence preview
     -> resolver::consume()
        -> exact object/PID/incarnation/expiry
        -> authority + issuance digest reads
        -> NO Root/native/source currentness
     -> immutable claim put

forwardComplete(claimId, t2)
  -> continuation lock -> forward-recovery claim lock
     -> resolver::inspect(... allowConsumed=true)
     -> generic claim consumption
     -> receipt publication
```

## Mutation graph

```text
NativePrincipal constitute/activate/revoke
  -> NativeState::locked
     -> native-provider-transition
     -> all sorted source/trust immutable locks
     -> event TransitionStore domain.lock -> commit

source lifecycle writer
  -> exact lifecycle immutable-directory lock -> commit

source generation constitution writer
  -> imperator-principal-constitution:{authority hash}
     -> authority consumption locks
     -> exact principal-version immutable-directory lock -> commit
```

The source/lifecycle writers collide with the corresponding locks already held
by `NativeState::locked()`. The issuer and claim derivation never acquire that
shared exclusion, so those protections are irrelevant to their validation/use
windows.

## Harness traces

- DP01 — `ORDERING_HAZARD`: accepted `issue()` validates before acquiring its
  disjoint publication lock. The accepted base has no operational issuance
  decision publisher or validation-to-publication checkpoint, so stale
  *decision* publication cannot be executed deterministically here. This is a
  missing surface, not proof of safety.
- IU01 — `DEFERRED_BOUNDARY`: the accepted base has only constants-only issuance
  decision/authority/custody contracts. No runtime issuance authority,
  issuance capability, resolver, consume method or publication service exists.
- CU01 — `DISJOINT_LOCK_RACE_REPRODUCED`: worker resolves current authority and
  emits `CU01_CURRENTNESS_RESOLUTION_PASSED`; parent commits native `REVOKE` via
  `NativePrincipal::lifecycle()` or source `SUSPEND` via the accepted lifecycle
  store; parent releases worker; `derive()` consumes stale custody and emits
  `STALE_CLAIM_PUBLISHED`.

The CU01 process barrier proves the order. A later forward refusal cannot turn
that published claim into an authorized one.
