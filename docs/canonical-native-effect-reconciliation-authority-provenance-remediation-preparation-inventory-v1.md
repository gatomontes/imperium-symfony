# Canonical Native Effect Reconciliation Authority Provenance Remediation — Preparation inventory v1

`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_AUTHORITY_PROVENANCE_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Audited baseline: clean synchronized local `main` at
`a0740ff65747838edaa5a58ded26487bb5bf9f6d`. At entry, `main`,
`origin/main` and `origin/HEAD` resolved to that same commit. No fetch, network
request, provider operation or external CI lookup was performed.

## Decision

The process-incarnation continuation correction and the separation of first
callback execution, read-only reconstruction and no-provider forward recovery
remain accepted substrate. Reconciliation-authority provenance does not.

`NativeEffectForwardRecoveryClaimAdmissionService::admit(array $authority,
int $at)` treats a caller-created array as authority. Its issuer, holder and act
are public constant strings. `NativeState::seal()` removes any supplied digest
and computes a public deterministic SHA-256 digest. The service validates shape,
time, false flags, digest integrity and durable effect lineage; it never resolves
a pre-existing issuance record, active Imperator competence, Operator Root
lineage, revocation source or typed custody object. It then persists the same
caller array in the alleged trusted authority directory and derives a durable
claim from it.

That is not a subtle provenance defect. It is authenticated issuance replaced
by neat formatting. A digest can prove that bytes did not change after the
caller chose them. It cannot prove who was competent to choose them.

Preparation Batch 0 defines the smallest later correction and proof sequence.
It creates no issuer, authority, capability, record or claim and changes no
runtime or service wiring. Batch 1 is not authorized.

## Exact present self-sealed-array counterexample

The same counterexample exists in four test fixture constructors:

- `CanonicalNativeEffectContinuationExclusivityRemediationBatch3Test::admitRecoveryClaim()`;
- `CanonicalNativeEffectContinuationExclusivityRemediationBatch4Test::addForwardRecoveryClaim()`;
- `CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch3Test::recoveryAuthority()`; and
- `CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch4Test::reconciliationAuthority()`.

Their effective operation is:

```php
$authority = NativeState::seal([
    'schema' => NativeEffectReconciliationAuthorityContract::SCHEMA,
    'authority_id' => 'native-effect-reconciliation-authority-'.$tupleId,
    'effect_admission' => NativeState::ref($admission, 'admission_id'),
    'callback_start' => NativeState::ref($callback, 'callback_start_id'),
    'sealed_response' => NativeState::ref($response, 'response_id'),
    'deterministic_receipt_id' => NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admissionId),
    'act' => NativeEffectReconciliationAuthorityContract::ACT,
    'holder' => NativeEffectReconciliationAuthorityContract::HOLDER,
    'issuer' => NativeEffectReconciliationAuthorityContract::ISSUER,
    'effective_at' => $responseAt,
    'expires_at' => $recoveryAt + 100,
    'provider_invocation_permitted' => false,
    'credential_resolution_permitted' => false,
    'callback_reinvocation_permitted' => false,
    'automatic_retry_permitted' => false,
    'single_purpose' => true,
    'sealed' => true,
]);
$claim = (new NativeEffectForwardRecoveryClaimAdmissionService($state))
    ->admit($authority, $recoveryAt);
```

Every value needed to pass `validateAuthority()` is public or derived from
readable durable records. `NativeState::seal()` is public. The caller chooses
the authority ID and validity window. `admit()` itself writes the first supposed
authority record. Therefore an arbitrary caller who can call this boundary and
read the three lineage records can manufacture the accepted bytes. No private
fact, prior issuance or canonical source resolution distinguishes those bytes
from an Imperator-issued authority.

## Required distinctions

| Confused concepts | Present fact | Required later distinction |
| --- | --- | --- |
| Digest integrity vs authenticated issuance | `NativeState::seal()` and `ImmutableRecordStore` detect byte changes. Neither authenticates the author. | An issuer must derive an authority from resolved competent source records and retain immutable issuance evidence before the admission boundary sees it. |
| Constant issuer prose vs resolved provenance | `issuer`, `holder` and `act` are equality-checked public strings. | Resolve the exact authority record, issuance record, active principal version and Operator Root lineage; prose is descriptive only. |
| Durable record vs consumable authority | The caller array becomes durable, but no authority consumption occurs. | Durability preserves evidence. Separate atomic consumption establishes one permitted use. |
| Idempotent receipt replay vs reusable authorization | The same unconsumed claim can call `forwardComplete()` repeatedly before expiry; a single receipt happens to converge. | First mutation consumes one exact claim. Later exact receipt reads return the already-bound result and do not re-exercise authority. |
| Trusted storage vs trusted ingress | The local immutable store is trusted after a record enters it. `admit()` lets an arbitrary caller create the first authority record. | Only a canonical issuer writes the authority directory; admission accepts an ID or resolver-produced typed custody object, never caller authority bytes. |

## Classified surface inventory

| ID | Surface | Classification | Audited fact / required disposition |
| --- | --- | --- | --- |
| C01 | Reconciliation contract schema | `EXISTS_CANONICALLY` | Exact fields, forward-only act and false effect flags exist. They describe content, not provenance. |
| C02 | Authority construction in production | `ABSENT` | No production Operator Root/Imperator producer constructs this schema. |
| C03 | Authority construction in tests | `EXISTS_CANONICALLY` | Four fixture helpers construct and seal complete arrays directly. |
| C04 | Authority ID derivation | `EXISTS_FRAGMENTED` | Tests derive it from tuple ID; the admission service accepts any syntactically valid caller-selected ID. |
| C05 | Holder/issuer/act validation | `EXISTS_FRAGMENTED` | Constant equality is enforced, but the identities are unresolved prose. |
| I01 | Public deterministic seal | `EXISTS_CANONICALLY` | `NativeState::seal()` gives integrity only and is callable by every caller. |
| I02 | Immutable-store digest | `EXISTS_CANONICALLY` | `ImmutableRecordStore` reseals on put and checks on read. It cannot authenticate ingress. |
| I03 | Canonical issuer competence check | `ABSENT` | No active Imperator principal or scoped competence is resolved. |
| I04 | Operator Root provenance join | `ABSENT` | Reconciliation authority contains no source principal/root references. |
| I05 | Immutable issuance evidence | `ABSENT` | No issuance schema, directory or source decision exists for this authority. |
| I06 | Authenticated ingress | `ABSENT` | `admit(array)` is the authority-ingress boundary and trusts caller bytes after structural checks. |
| S01 | Authority serialization | `EXISTS_CANONICALLY` | It is a plain array and serializes/copies freely; no typed custody exists. |
| S02 | Authority persistence | `EXISTS_FRAGMENTED` | `admit()` writes it to `AUTHORITIES`, but storage occurs after untrusted ingress. |
| S03 | Claim persistence | `EXISTS_CANONICALLY` | A deterministic durable claim record is stored in `CLAIMS`. |
| S04 | Receipt persistence | `EXISTS_CANONICALLY` | `NativeEffectReceiptBindingService` creates one immutable deterministic receipt. |
| R01 | Authority source resolution | `ABSENT` | The caller array is never replaced by a record resolved from a canonical authority directory. |
| R02 | Exact lineage resolution | `EXISTS_CANONICALLY` | Admission, callback-start and sealed-response references/digests are resolved and cross-checked. |
| R03 | Issuer lineage resolution | `ABSENT` | No issuance, principal, lifecycle or Root source is resolved. |
| R04 | Read-only Imperator lifecycle reconstruction pattern | `EXISTS_CANONICALLY` | `ImperatorPrincipalLifecycleReconstructionService` resolves current status without creating authority. |
| R05 | Reconciliation authority reconstruction | `ABSENT` | No independent read-only reconstruction joins authority, issuance, competence, revocation and consumption. |
| V01 | Expiry at authority admission | `EXISTS_CANONICALLY` | Caller-chosen effective/expiry integers are checked at admission. |
| V02 | Claim expiry at forward use | `EXISTS_CANONICALLY` | `forwardComplete()` refuses before `admitted_at` and at/after claim expiry. |
| V03 | Revocation reference/store | `ABSENT` | Authority and claim have no revocation source or current-revocation resolution. |
| V04 | Stale principal/competence refusal | `ABSENT` | No principal record participates, so staleness cannot be detected. |
| V05 | Substituted effect lineage refusal | `EXISTS_CANONICALLY` | Reference and digest substitution for admission/callback/response is checked. |
| V06 | Substituted issuer lineage refusal | `ABSENT` | There is no issuer lineage to substitute or validate. |
| D01 | Claim derivation | `EXISTS_FRAGMENTED` | Deterministic ID binds authority ID and response digest, but derivation follows untrusted authority ingress. |
| D02 | Typed recovery custody | `ABSENT` | Admission takes `array`; recovery takes a string claim ID. No resolver-issued typed object exists. |
| D03 | Reconciliation authority consumption | `ABSENT` | `AuthorityConsumptionStore` is not used; multiple authority IDs can derive multiple claims for one response. |
| D04 | Forward claim consumption | `ABSENT` | Claim use is validated but never marked consumed. |
| D05 | Atomic consumption pattern | `EXISTS_CANONICALLY` | `AuthorityConsumptionStore` provides deterministic source/consumer-bound, retry-convergent single use elsewhere. |
| D06 | Same-process exact-object capability pattern | `EXISTS_CANONICALLY` | The corrected continuation issuer uses PID, private nonce, issuer registry and exact object identity. |
| D07 | Credential capability pattern | `EXISTS_FRAGMENTED` | It has exact issuer-object recognition and consumption, but lacks the continuation capability's explicit transfer guards. It is not a recovery-authority substitute. |
| U01 | Claim admission replay | `EXISTS_CANONICALLY` | Exact same caller array converges on the same authority and claim records. That is repeatable admission, not proved authorization. |
| U02 | Competing authority IDs | `EXISTS_FRAGMENTED` | Distinct caller-selected IDs use distinct admission locks and can create distinct claims for the same lineage. |
| U03 | Competing forward claimants | `EXISTS_FRAGMENTED` | Admission-continuation lock makes receipt publication converge, but claims are not exclusive or consumed. |
| U04 | First forward mutation | `EXISTS_FRAGMENTED` | Exact durable lineage and no-provider flags are checked, but the claim is reusable and provenance-free. |
| U05 | Existing receipt replay | `EXISTS_CANONICALLY` | The same receipt is returned while the claim remains valid; no second receipt or provider callback occurs. |
| U06 | Replay after claim expiry | `EXISTS_CANONICALLY` | Current code validates claim expiry before reading the existing receipt and therefore refuses. A distinct read-only receipt API already exists. |
| U07 | Read-only receipt reconstruction | `EXISTS_CANONICALLY` | `NativeEffectDoubleExecutionService::reconstruct()` reads by receipt ID and grants no continuing authority. |
| U08 | Provider/callback/credential absence in recovery | `EXISTS_CANONICALLY` | Recovery APIs and sources have no continuation, callback, payload, key, credential resolver or transport input. |
| U09 | Receipt binder reachability | `EXISTS_CANONICALLY` | Constructed only by first-execution and forward-recovery services; no public corridor method exposes it directly. |
| L01 | Recovery admission construction | `EXISTS_CANONICALLY` | Production source constructs it only through `CanonicalNativeEffectCorridor`; tests instantiate it directly. |
| L02 | Forward recovery construction | `EXISTS_CANONICALLY` | Production source constructs it only through the corridor; tests and the disposable worker instantiate it directly. |
| L03 | Service lifetime | `EXISTS_FRAGMENTED` | Corridor and `NativeState` are default shared Symfony services, but each corridor method returns a fresh recovery service/store wrapper. |
| L04 | ProviderTransition autowiring | `EXISTS_CANONICALLY` | Namespace is excluded; only `NativeState` and `NativeBindingReader` are explicit. |
| L05 | Corridor visibility | `EXISTS_FRAGMENTED` | Auto-discovered service has public PHP factory methods; Symfony production service is private by default. Test kernel makes it public. |
| L06 | Production command/canonical consumer | `ABSENT` | No production command or application service calls recovery admission or forward completion. |
| L07 | Disposable worker | `EXISTS_FRAGMENTED` | Worker can forward-complete an already-created claim; tests create the authority/claim in the parent process. |
| P01 | Root-provenanced principal contract | `EXISTS_CANONICALLY` | Constitution/principal records carry source Operator Root and scoped competence. |
| P02 | Root authority producer | `EXISTS_FRAGMENTED` | The retained `ImperatorPrincipalProvenanceFixtureStore` validates and stores fixtures; it is evidence plumbing, not authenticated live Root ingress. |
| P03 | Active Imperator caller-authority issuance | `EXISTS_CANONICALLY` | `DeterministicTransitionCallerAuthorityIssuanceService::issueImperator()` reconstructs an active principal and checks scoped competence. |
| P04 | Source decision to issued authority | `EXISTS_CANONICALLY` | Durable provider, outbound email and binding activation issuers resolve source decisions and exact basis records. |
| P05 | Issuance-authority consumption | `EXISTS_CANONICALLY` | Durable provider issuance uses `AuthorityConsumptionStore`; other older issuers use local scans or caller-authority consumers and are less reusable. |
| P06 | Immutable issuance record | `EXISTS_CANONICALLY` | Canonical issuers retain source decision, consumed issuance authority, issued artifact and issuer identity. |
| P07 | Canonical reference resolution | `EXISTS_CANONICALLY` | `RecordReferenceValidator::resolve()` verifies path, record digest, reference digest and optional identity. |
| P08 | Native source resolution | `EXISTS_CANONICALLY` | `NativeState::source()` resolves configured source kinds and exact digest/schema. Its fixed source map does not include reconciliation authority. |
| P09 | Atomic receipt convergence | `EXISTS_CANONICALLY` | Continuation then claim scopes and immutable receipt store give one local-host receipt. |
| P10 | Cross-host/distributed coordination | `DEFERRED_BOUNDARY` | `flock` and local immutable directories prove only one host/cooperative filesystem. |
| X01 | Loss before authority persistence | `EXISTS_FRAGMENTED` | Current caller can simply resubmit; later canonical issuance must distinguish absent from partially consumed issuance authority. |
| X02 | Loss after authority persistence before claim | `EXISTS_FRAGMENTED` | Current put sequence is inside an outer lock but not a multi-file transaction; deterministic retry converges if exact. No authority consumption exists. |
| X03 | Loss after authority consumption before claim (target) | `ABSENT` | Later design must let exact retry finish the deterministic claim without allowing a competing claimant. |
| X04 | Loss after claim consumption before receipt (target) | `ABSENT` | Later design must let exact retry finish the receipt while treating consumption, not replay, as the authority cut. |
| X05 | Loss after response before receipt | `EXISTS_CANONICALLY` | No-provider forward recovery is the accepted process-loss boundary and must remain. |
| X06 | Loss after receipt | `EXISTS_CANONICALLY` | Read-only reconstruction suffices. |
| G01 | Process-custody stage chain | `EXISTS_CANONICALLY` | Preparation and Batches 1–4 have separate commit/merge pairs through `83fc4d6`; Batch 5 merged at `c47adc5`. |
| G02 | Process-custody CI evidence | `EXISTS_CANONICALLY` | Retained run `33826904446` is SHA-bound to `c47adc531d1d6191b3e00f20f056ed69975289d2`; this inventory does not re-query it. |
| G03 | Post-closure merge | `EXISTS_CANONICALLY` | Closure evidence merged at `b188a0b849f27ebec4d3e14f98c471eead15b484`. |
| G04 | Current campaign selection merge | `EXISTS_CANONICALLY` | Merge `a0740ff65747838edaa5a58ded26487bb5bf9f6d` has parents `b188a0b...` and `da0918c...`. |
| G05 | Current Preparation Batch 0 CI | `ABSENT` | No external lookup or CI execution is authorized or claimed. |
| F01 | Controlling refusal | `EXISTS_CANONICALLY` | Current review, flow, handoff README and todo correctly refuse formal provenance closure. |
| F02 | Terminal audit acceptance marker | `EXISTS_FRAGMENTED` | Historical terminal audit still says bounded formal closure accepted and zero stages remain. It is evidence of its tree, not the current verdict. |
| F03 | Campaign-complete handoff | `EXISTS_FRAGMENTED` | Historical handoff repeats completion and zero stages; top-level current consumers now requalify it. |
| F04 | Evidence-ledger terminal verdict | `EXISTS_FRAGMENTED` | Ledger v2 retains the historical accepted verdict. It must remain immutable evidence and be interpreted through the later refusal. |
| F05 | Flow/README process-custody sections | `EXISTS_FRAGMENTED` | They retain `COMPLETE`/accepted prose below a controlling current refusal section. Precedence is understandable but the markers remain stale as current formal-closure claims. |
| F06 | Blackquill todo process-custody section | `EXISTS_FRAGMENTED` | It retains “zero campaign stages remain” as historical evidence immediately below the current refused campaign. |
| B01 | Hostile same-process memory/filesystem administrator | `DEFERRED_BOUNDARY` | Local PHP contracts cannot authenticate against an attacker controlling process memory or trusted storage. |
| B02 | Authenticated live Operator Root ingress | `DEFERRED_BOUNDARY` | Existing evidence fixtures and operationalization records are reusable lineage, but this campaign must not invent a new Root ceremony. |
| B03 | Provider, credential and live-trial behavior | `DEFERRED_BOUNDARY` | Explicitly outside Batches 0–5; Batch 7 remains suspended. |

## Current construction and call-site inventory

Production runtime has no reconciliation-authority producer and no canonical
recovery consumer. The only production construction path is the auto-discovered
facade:

```text
Symfony App\ discovery
  -> excludes ProviderTransition namespace
  -> explicitly constructs shared NativeState(root)
  -> auto-discovers private-by-default CanonicalNativeEffectCorridor(state)
       -> recoveryClaimAdmission(): new ClaimAdmissionService(state)
       -> forwardRecovery(): new ForwardRecoveryService(state)
            -> new ReceiptBindingService(state)
```

Direct test construction occurs in the process-custody Batch 3/4 tests and
continuation/exclusivity Batch 3/4 tests. The disposable worker directly
constructs only `NativeEffectForwardRecoveryService` for an already-persisted
claim. `NativeEffectReceiptBindingService` is constructed only by
`NativeEffectDoubleExecutionService` and `NativeEffectForwardRecoveryService`.

## Smallest acyclic later design

The later design should add no new Root. It should reuse the established active
Imperator principal and source-record machinery:

```text
retained Operator Root lineage
  -> read-only active Imperator principal reconstruction
  -> exact scoped caller/issuance authority
  -> canonical reconciliation issuer
       -> resolve admission + callback + response
       -> persist authority + issuance evidence
       -> consume issuance authority

caller supplies authority ID only
  -> trusted authority resolver reads canonical record + issuance + principal
  -> checks current expiry/revocation and exact effect lineage
  -> returns non-serializable resolver-issued typed custody object
  -> claim admission consumes exact authority and writes deterministic claim

forwardComplete(claim ID)
  -> resolve claim and current policy
  -> atomically consume claim for this exact consumer/source
  -> bind one deterministic receipt without callback/provider/credential input
  -> exact retry after the consumption cut completes/returns that receipt

reconstruct(receipt ID)
  -> read-only result replay; no authority use
```

The key ingress rule is mechanical: no public method in the recovery corridor
may accept reconciliation-authority bytes. Only the canonical issuer may write
the authority directory. A resolver-created typed object may carry resolved
custody within a process, but durable process-loss recovery remains rooted in
the stored authority/claim records, not in serializing that object.

The later lock order must be one-way:

1. exact source/issuer scope;
2. issuance-authority consumption scope;
3. reconciliation-authority immutable scope;
4. reconciliation issuance-evidence immutable scope;
5. reconciliation-authority consumption scope;
6. deterministic claim immutable scope;
7. admission-continuation scope;
8. claim-consumption scope; and
9. receipt immutable-store scope.

No recovery path may acquire native/tuple/credential/provider locks, and no
filesystem lock may cross a callback because recovery has no callback. Batch 1
must decide the exact expiry/revocation cut: before first claim consumption a
stale or revoked source refuses; after an exact consumption cut, retry may only
finish or return the already-determined receipt and cannot authorize a new act.

## Staged proof sequence

1. **Batch 1 — contracts only.** Define issuer competence, source decision,
   issuance record, authority record, revocation/freshness, typed resolution,
   authority/claim consumption and reconstruction contracts. Explicitly state
   that strings and caller digests do not authenticate.
2. **Batch 2 — rooted issuance and custody.** Implement the issuer, canonical
   authority store/resolver, immutable issuance evidence and retry-convergent
   authority consumption. No provider edge.
3. **Batch 3 — admission replacement and integration.** Remove `admit(array)`;
   admit only resolved canonical custody, consume the authority, integrate the
   established corridor and eliminate direct/self-sealed bypasses.
4. **Batch 4 — adversarial/application/process proof.** Prove counterfeit,
   stale, revoked, substituted, competing, interrupted and fresh-process cases,
   plus real container and canonical consumer reachability, without provider or
   credential access.
5. **Batch 5 — separately sequenced terminal audit.** Begin only from clean
   merged Batch 4 `main`; independently reconstruct the full chain, run focused
   and full tests and retain exact SHA-bound GitHub CI evidence.

Five stages remain after this Preparation Batch 0. Batch 7 is not one of them
and remains independently suspended.

## Preparation boundary retained

This batch produced documents and a documentary guard only. It did not modify
production runtime behavior, configuration or service wiring; create Batch 1
contracts or tests; create an issuer, authority, capability, authority record or
claim; derive or consume authority; complete a receipt; access a credential;
invoke a provider; perform network/external I/O; execute a mission/live trial or
email; open Iron Gate/Lazaretto; fabricate CI; claim terminal closure; or restore
Batch 7.

