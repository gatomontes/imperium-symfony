# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Preparation inventory v1

`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED`
`DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE`
`FORMAL_CLOSURE_REFUSED_RECONCILIATION_DERIVATION_AUTHORITY_ABSENT`
`REVOCATION_AT_CONSUMPTION_UNPROVED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

Audited baseline: clean synchronized cached `main`, `origin/main` and
`origin/HEAD` at `3dceba3057497c6c80f019bd78835335cf69c774`. No fetch,
network request, external CI lookup, provider operation, credential access or
authority operation was performed.

## Decision

The prior campaign fixed trusted ingress, Root/native source provenance,
process-local typed custody, claim consumption and read-only reconstruction while the untimestamped Operator Root trust anchor remains currently eligible.
Those corrections remain accepted. They do not establish authority to perform
the new act of issuing reconciliation authority, and they do not make source
currentness stable between custody resolution and use.

`NativeEffectReconciliationAuthorityIssuanceService::issue(string
$admissionId, int $at, int $expiresAt)` accepts no decision, caller authority,
issuance authority or typed issuance capability. Any caller able to construct
the service—or obtain it from `CanonicalNativeEffectCorridor`—may cause a new
authority and issuance record to be published from an admission ID and chosen
valid timestamps. Source provenance proves where the inputs came from. It does
not prove that this caller was authorized to derive a new act from them.

`NativeEffectReconciliationAuthorityResolver::resolve()` checks current Root,
native-principal and source-principal state. Its later `consume()` does not.
A correctly minted capability can therefore survive a source revocation and be
used to publish a claim. A later `forwardComplete()` re-inspection may refuse,
but that is too late to make the claim publication authorized.

Preparation Batch 0 records these gaps and the smallest later correction. It
does not implement that correction. Batch 1 is not authorized.

## Exact unguarded issuance counterexample

The effective public operation is:

```php
$issued = (new NativeEffectReconciliationAuthorityIssuanceService($state))
    ->issue($admissionId, $at, $expiresAt);
```

or, through the application construction facade:

```php
$issued = $corridor->reconciliationAuthorityIssuer()
    ->issue($admissionId, $at, $expiresAt);
```

The issuer resolves the admission, committed native transition, native
authority, current native principal, Root act, callback start and sealed
response. It bounds expiry to the source authority/principal expiry and derives
deterministic IDs. It then takes
`canonical-native-effect-reconciliation-issuance:<hash(authorityId)>` and writes
the authority followed by issuance evidence.

There is no input or read for an exact reconciliation-issuance decision. There
is no separately sourced `ISSUE_EXACT_RECONCILIATION_AUTHORITY` grant. There is
no scoped caller authority or issuance capability. There is no
`AuthorityConsumptionStore::consume()` call before publication. The
`issuer_service` constant identifies code; it does not establish competence.
The deterministic ID makes identical unauthorized calls converge; convergence
does not authorize the first call.

Current invocation sites are all test/support code, not a production command:

- the prior provenance Batch 2 and Batch 4 tests;
- continuation/exclusivity Batches 3 and 4;
- process-custody/formal-closure Batches 3 and 4;
- `tests/Imperium/Runtime/Support/reconciliation_authority_worker.php`; and
- the public corridor factory, whose issuer is asserted by prior Batch 3 and
  container tests.

No production command, worker or transport presently calls `issue()`. That
limits reachability; it does not cure the public construction/competence defect.

## What the source native decision actually authorizes

`NativeAuthority::chain()` creates one
`imperium.imperator.native-transition-decision/v1` with:

- disposition `AUTHORIZED_EXACT_TRANSITION`;
- an issuance target naming the exact native authority, consumer,
  `TransitionContract::SCOPE`, native root and `authority_single_use: true`;
- an exact successor/creation winner when applicable; and
- `continuing_authority: false` on the decision, custody and authority.

`NativeConsumer::execute()` uses that authority only for the governed native
pre-effect transition. `NativeAdmission::records()` constructs
`imperium.la-cortine.native-transition-consumption/v1`; the durable consumption
is recorded inside the committed native transition at
`transition.records.authority_consumption`. The preceding journal explicitly
says `PREPARED_NO_AUTHORITY_CONSUMED`; only the transition commit makes the
consumption durable.

That consumed authority authorized the exact native transition and nothing
after it. `continuing_authority: false` is a denial of derivation by
continuation. Treating the committed transition as an issuance grant would make
single use meaningless: every consumed authority would become an unbounded
factory for later acts. Historical provenance remains evidence; it is not a
renewed authorization.

## Required distinctions

| Distinction | Current fact | Required later rule |
| --- | --- | --- |
| Source provenance vs derivation authorization | Root/native resolution proves the source transition chain. | A separate exact decision/grant must authorize issuance of one reconciliation authority. |
| Issuer service identity vs issuer competence | `issuer_service` is a public constant and the service class is known. | Resolve an active scoped principal and exact issuance authorization at the use cut. |
| Construction access vs authority | Public constructors and corridor methods let code obtain the issuer. | Possession of a service object must not satisfy its authority input. |
| Deterministic output vs authorized issuance | IDs derive from admission/response and exact retries converge. | Determinism is an idempotency property after authorization, not evidence of it. |
| Resolution-time validity vs use-time currentness | `resolve()` checks the source chain. | The same chain must be revalidated inside the governed consumption/publication cut. |
| Historical evidence vs continuing power | The native transition and its consumption are durable. | `continuing_authority: false` prohibits treating them as a new grant. |

## Classified surface inventory

| ID | Surface | Classification | Finding |
| --- | --- | --- | --- |
| I01 | Reconciliation issuer service and deterministic output | `EXISTS_CANONICALLY` | Publishes v2 authority plus issuance evidence from exact lineage. |
| I02 | Exact issuance-decision input | `ABSENT` | `issue()` has only admission ID and times. |
| I03 | Separately sourced reconciliation issuance authority | `ABSENT` | No contract, store, producer or resolver exists. |
| I04 | Typed issuance capability | `ABSENT` | The existing capability is custody for authority-to-claim derivation, not authorization to issue that authority. |
| I05 | Issuance-authority consumption with publication | `ABSENT` | Issuer never calls `AuthorityConsumptionStore`. |
| I06 | Issuer identity | `EXISTS_CANONICALLY` | Constant service identity is persisted. It is descriptive only. |
| I07 | Issuer competence | `EXISTS_FRAGMENTED` | Source principal competence is resolved, but no scope authorizes this derived issuance act. |
| I08 | Caller/issuer binding | `ABSENT` | Caller identity and exact consumer are not supplied or consumed. |
| I09 | Exact target and expiry derivation | `EXISTS_FRAGMENTED` | Target IDs are deterministic and expiry is source-bounded; caller still chooses times without an authorizing decision. |
| I10 | Direct construction | `EXISTS_CANONICALLY` | Public service constructor is used by tests and worker. |
| I11 | Corridor construction exposure | `EXISTS_CANONICALLY` | `reconciliationAuthorityIssuer()` returns a fresh unguarded issuer. |
| I12 | Production command/worker invocation | `ABSENT` | No production call site invokes reconciliation issuance. |
| I13 | Test/support invocation | `EXISTS_CANONICALLY` | Six test families plus the reconciliation worker exercise issuance. |
| S01 | Root/native source resolution | `EXISTS_CANONICALLY` | Admission -> commit -> native authority -> native principal -> signed Root act is resolved. |
| S02 | Native transition exact decision | `EXISTS_CANONICALLY` | Authorizes only `AUTHORIZED_EXACT_TRANSITION`. |
| S03 | Native transition single-use consumption | `EXISTS_CANONICALLY` | Embedded in committed transition records, not the generic consumption store. |
| S04 | Native `continuing_authority: false` | `EXISTS_CANONICALLY` | Present on decision, custody, authority and consumption. |
| S05 | Reconciliation derivation permission in source decision | `ABSENT` | No field names or authorizes reconciliation issuance. |
| R01 | Authority/issuance integrity validation | `EXISTS_CANONICALLY` | Resolver checks shape, seals, references and immutable evidence. |
| R02 | Root currentness at resolve | `EXISTS_CANONICALLY` | `NativeRootActs::verify()` checks anchor revocation and time. |
| R03 | Native-principal currentness at resolve | `EXISTS_CANONICALLY` | Activation, revocation, expiry and native lifecycle are checked. |
| R04 | Source-principal lifecycle at resolve | `EXISTS_CANONICALLY` | v2 lifecycle reconstruction must be ACTIVE; v3 lifecycle requires migration. |
| R05 | Source-generation winner at resolve | `EXISTS_CANONICALLY` | Higher constituted generation at/before `at` refuses. |
| R06 | Root currentness at capability consume | `ABSENT` | `consume()` does not invoke Root verification. |
| R07 | Native-principal currentness at capability consume | `ABSENT` | No `NativePrincipal::load()` call occurs. |
| R08 | Source lifecycle/generation at capability consume | `ABSENT` | No source rescan or lifecycle reconstruction occurs. |
| R09 | Capability expiry at consume | `EXISTS_CANONICALLY` | Own `expiresAt` and process/incarnation binding are checked. |
| R10 | Authority/issuance bytes at consume | `EXISTS_CANONICALLY` | Stored digests must equal capability digests. |
| R11 | Full source re-inspection at forward completion | `EXISTS_CANONICALLY` | `forwardComplete()` calls `inspect(..., allowConsumed: true)` before claim consumption. |
| R12 | Unauthorized claim prevented by later re-inspection | `ABSENT` | Later refusal cannot undo claim already derived after stale capability use. |
| C01 | Typed process custody | `EXISTS_CANONICALLY` | Exact object, resolver registry, PID/incarnation, no clone/serialization. |
| C02 | Authority-to-claim atomic local exclusion | `EXISTS_CANONICALLY` | One reconciliation-authority lock and immutable deterministic claim. |
| C03 | Durable authority-consumption record | `EXISTS_FRAGMENTED` | Consumption is embedded in the claim; no standalone record exists. |
| C04 | Claim-to-receipt consumption | `EXISTS_CANONICALLY` | Generic store binds exact claim digest to deterministic receipt. |
| C05 | Deterministic retry after authority publication cut | `EXISTS_CANONICALLY` | Orphan authority is unresolvable; exact issuer retry publishes issuance. |
| C06 | Deterministic retry after in-memory capability consumption cut | `EXISTS_CANONICALLY` | No claim means no durable consumption; fresh resolution may retry. |
| C07 | Process-loss recovery after claim consumption | `EXISTS_CANONICALLY` | Exact source/consumer consumption converges, then receipt binds. |
| C08 | Read-only receipt-to-Root reconstruction | `EXISTS_FRAGMENTED` | Joins evidence without issue/consume/bind/provider calls while current Root trust remains eligible; an untimestamped Root revocation blocks historical reconstruction. |
| C09 | Present-tense reconstruction for authority-issuing use | `ABSENT` | Existing reconstruction is historical/read-only, not an issuance-use guard. |
| L01 | Issuance lock | `EXISTS_CANONICALLY` | Outer issuance scope, then authority immutable directory, then issuance directory. |
| L02 | Derivation lock | `EXISTS_CANONICALLY` | Reconciliation authority scope encloses preview, capability consume and claim put. |
| L03 | Forward lock order | `EXISTS_CANONICALLY` | Admission-continuation -> exact claim -> authority-consumption -> receipt immutable store. |
| L04 | Atomic currentness + issuance-authority consumption + publication | `ABSENT` | These operations do not yet share one governed cut. |
| L05 | Multi-host/distributed exclusion | `DEFERRED_BOUNDARY` | `flock` proves cooperative local-host semantics only. |
| P01 | Canonical active-Imperator caller authority pattern | `EXISTS_CANONICALLY` | `DeterministicTransitionCallerAuthorityIssuanceService` reconstructs ACTIVE scope and issues short-lived exact target authority. |
| P02 | Exact decision plus issuance-authority pattern | `EXISTS_CANONICALLY` | Durable provider and revocation issuers validate a decision and consume its issuance authority. |
| P03 | Caller-authority consumer pattern | `EXISTS_CANONICALLY` | Outbound email and binding activation bind exact transition/target/consumer. |
| P04 | Separate issuance-authorization pattern | `EXISTS_CANONICALLY` | Corridor disposition producer validates a scoped authorization, reconstructs ACTIVE principal, consumes authorization, then publishes. |
| P05 | Canonical reference validation | `EXISTS_CANONICALLY` | `RecordReferenceValidator::resolve()` checks digest and optional identity. |
| P06 | Reusable pattern already complete as one component | `EXISTS_FRAGMENTED` | Pieces exist in several domains; no reconciliation-specific acyclic chain joins them. |
| A01 | Root resolve -> revoke -> consume race | `ABSENT` | Prior test revokes before fresh resolution only. |
| A02 | Native principal resolve -> revoke -> consume race | `ABSENT` | No post-resolution test or consume-time check. |
| A03 | Source generation resolve -> supersede -> consume race | `ABSENT` | No post-resolution test or consume-time check. |
| A04 | Source lifecycle resolve -> disposition -> consume race | `ABSENT` | No post-resolution test or consume-time check. |
| A05 | Authority expiry before consume | `EXISTS_CANONICALLY` | Capability expiry rejects at `at >= expiresAt`. |
| A06 | Issuance-authority replay/substitution | `ABSENT` | No such authority exists yet. |
| A07 | Competing issuers | `EXISTS_FRAGMENTED` | Deterministic output converges but no authorized winner is selected. |
| A08 | Competing claimants | `EXISTS_CANONICALLY` | Local single winner is proved for capability-to-claim publication. |
| A09 | Fresh-process custody | `EXISTS_CANONICALLY` | Fresh process must resolve new local capability from durable evidence. |
| A10 | Stale closure consumers | `EXISTS_FRAGMENTED` | Historical terminal audit/handoff/ledger retain zero-stage acceptance below newer controlling refusal sections. |
| G01 | Prior stage commit/merge chain | `EXISTS_CANONICALLY` | Preparation through Batch 5 merges are retained through `2303473`. |
| G02 | Prior exact-SHA CI evidence | `EXISTS_CANONICALLY` | Historical run `33874716024`, job `101028835208`, exact SHA `98f9777...`, Ubuntu/PHP 8.4.25. |
| G03 | Current selection merge | `EXISTS_CANONICALLY` | `3dceba3` selects this six-stage campaign. |
| G04 | Current Preparation external CI result | `ABSENT` | No external lookup or CI result is claimed. |
| G05 | Windows/Linux path identity | `EXISTS_CANONICALLY` | Native/transition identities lowercase Windows paths and preserve Linux case; `flock` remains platform/filesystem dependent. |
| B01 | Provider, credential, mission and live-trial edge | `DEFERRED_BOUNDARY` | Explicitly outside this campaign; Batch 7 remains suspended. |
| B02 | Authenticated live Root ceremony | `DEFERRED_BOUNDARY` | Existing signed lineage may be resolved; no new Root ceremony is authorized here. |
| B03 | Hostile same-process/filesystem administrator | `DEFERRED_BOUNDARY` | Cooperative local trust boundary remains. |

## Smallest acyclic later design

```text
active scoped Imperator principal + exact effect lineage
  -> exact reconciliation-issuance decision
  -> separately sourced single-use issuance authority/capability
  -> governed issuance cut
       re-resolve decision + Root/native/source currentness
       consume issuance authority for exact issuer/target
       publish deterministic reconciliation authority
       publish immutable issuance evidence
  -> existing authority resolver and typed process custody
  -> governed claim-publication cut
       revalidate Root/native/source currentness
       consume exact capability
       publish one deterministic claim
  -> existing no-provider claim consumption and receipt binding
  -> existing read-only receipt-to-Root reconstruction, conditional on current Root eligibility
```

The decision/issuance authority must not depend on the reconciliation authority
it authorizes, so the graph remains acyclic. The consumed native transition is a
source reference only. A fresh process may reconstruct durable decision and
issuance evidence, but must obtain fresh typed custody. Exact retries may finish
the same deterministic publication; they may not select a new target, caller,
issuer or validity window.

## Staged proof sequence

1. Batch 1 defines exact decision, authorization, capability, currentness,
   consumption, publication, retry and reconstruction contracts only.
2. Batch 2 implements Root/Imperator-provenanced decision and typed issuance
   authority, atomically consumed with deterministic authority/issuance
   publication; no provider edge.
3. Batch 3 requires that typed authority at the public issuer and repeats
   present-tense Root/native/source validation inside issuer and claim-use cuts.
4. Batch 4 proves all adversarial, race, substitution, replay, contention,
   interruption, fresh-process, container/worker and platform cases, including
   the split between current untimestamped Root revocation and timestamped
   native/source lifecycle history during post-receipt reconstruction.
5. Batch 5 begins separately from clean merged Batch 4 `main`, reconstructs the
   full chain and retains exact-SHA local/CI evidence before any bounded verdict.

No later stage, authority creation/consumption, claim, receipt, provider effect
or Batch 7 action is authorized by this inventory.
