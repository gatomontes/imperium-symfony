# Executable Atomic Transition Native Integration Remediation preparation inventory v1

`PREPARATION_BATCH_0_COMPLETE_NATIVE_INTEGRATION_GAPS_CLASSIFIED`

## Basis and limits

Preparation Batch 0 only, reviewed 2026-09-02 from clean `main` at
`a018ab44ebe42e3e62634da2831ecb2b65e0e457`, equal to locally recorded
`origin/main`. No network refresh was performed. The campaign-ready handoff
contains `EXECUTABLE_ATOMIC_TRANSITION_NATIVE_INTEGRATION_REMEDIATION_CAMPAIGN_READY`.
All 22 required sources were read. Historical instructions in those sources
are evidence, not authorization to repeat their actions or continue implementation.

`EXISTS_CANONICALLY` describes an existing bounded contract or implementation,
not live eligibility. `EXISTS_FRAGMENTED` means pieces exist without the required
native join. `ABSENT` means no route was found in the listed source corridor and
symbol searches. `DEFERRED_BOUNDARY` marks a later authorization or excluded effect.
No live instance records, private receipts, credentials or capabilities were read.
The earlier terminal refusal remains controlling:
`EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT`.

## Classified findings

Every finding below has exactly one classification. Later sections expand these
findings and describe correction obligations; they do not install new contracts.

| ID | Boundary | Classification | Source evidence and consequence |
| --- | --- | --- | --- |
| N00 | Retained executable entry | EXISTS_CANONICALLY | `TransitionConsumer::execute(string $requestDigest)` is the isolated callable, with configured store, custody and clock. `config/services.yaml` excludes the namespace. No native caller outside it was found. |
| N01 | Exact Operator Root transition competence | ABSENT | `ImperatorPrincipalConstitutionAuthorityContract` grants initial binding-activation scope only through its validator; v3 adds executor-principal activation-decision scope, not `DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION`. No constituting issuer/loader for that exact scope exists. Operator Root must own the explicit grant; MasterMason may only commit its authorized result. |
| N02 | Initial principal constitution mechanics | EXISTS_CANONICALLY | `FutureInstanceImperatorPrincipalConstitutionService::constitute()` requires an unsealed future instance; `ExistingInstanceImperatorPrincipalRemediationService::remediate()` requires the exact operationalization seal and missing principal. Both consume authority and write generation-one v2 `PENDING_ACTIVATION` under `var/imperium/runtime/imperator-principal-versions`. Neither widens an existing generation. |
| N03 | Constitution source authenticity | EXISTS_FRAGMENTED | Both constitution services load authority from `ImperatorPrincipalProvenanceFixtureStore::CONSTITUTION_AUTHORITIES`, an evidence directory. The store validates operator id/decision/digests supplied in an array, not an independently loaded Operator Root act. No native authenticated transition-scope ingress was found. Configured hashes cannot repair this ownership gap. |
| N04 | Existing-instance scope-successor precedent | EXISTS_FRAGMENTED | `CorridorDispositionPrincipalAuthorityRemediationProducer` loads a source principal, checks next-generation uniqueness, consumes a grant, commits a pending successor and separately activates it. Its only added competence is corridor disposition. The executor-decision scope grant/successor contracts likewise name a different permission. Neither can authorize this transition by analogy. |
| N05 | Native principal store and loader | EXISTS_FRAGMENTED | `ImperatorPrincipalLifecycleReconstructionService::reconstruct()` reads the runtime principal directory but accepts only `imperium.imperator-runtime-principal/v2`. The v3 principal is validated in Batch5B fixtures and embedded in the decision-provenance production aggregate, not resolved by this loader. A new exact scope requires a compatible native producer/store/loader chain. |
| N06 | Lifecycle, expiry and revocation | EXISTS_FRAGMENTED | The v2 loader reads evidence lifecycle dispositions, rejects multiple effective matches and maps ACTIVATE/SUSPEND/RENEW/SUPERSEDE/REVOKE/EXPIRE/RETIRE. With a matching disposition it skips its fallback principal-expiry branch. It neither traverses a full current-generation chain nor consumes/authenticates every disposition. Existing caller checks cannot establish current transition competence. |
| N07 | Native decision-provenance production | EXISTS_FRAGMENTED | `PrincipalActivationDecisionAuthorityProvenanceProductionService::produce()` writes one aggregate under `var/imperium/runtime/principal-activation-decision-authority-provenance-productions`. It accepts source, successor, disposition, envelope and eligibility arrays; validation precedes `decision-provenance-production:<hash>` lock. `reconstruct()` checks aggregate keys after a digest-valid read. It does not reload current source lineage under the transition lock. |
| N08 | Exact decision and issuance-target shapes | EXISTS_CANONICALLY | `ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract` has a value-shaped target containing authority id/schema, consumer, transition, root and single-use. It avoids demanding a future authority digest. Its v3/adoption references remain inert boundary references, not future admitted-result evidence. |
| N09 | Native transition decision-to-authority execution | ABSENT | Existing deterministic caller issuance allowlists four email/binding-activation transitions and the v2 principal loader. Transition issuance/custody/delivery contracts are `CONTRACT_ONLY_NOT_ISSUED`, `CONTRACT_ONLY_EMPTY`, `CONTRACT_ONLY_NOT_DELIVERED`. `TransitionAuthority::expected()` derives its decision from the configured grant. No native principal-to-decision-to-custody-to-consumer resolver exists. |
| N10 | Executor principal activation | EXISTS_FRAGMENTED | `ProviderExecutorPrincipalActivationService` can write/reconstruct a combined activation record in `var/imperium/runtime/provider-executor-principal-activations`; decision, attestation, assurance and boundary are supplied arrays. Canonical resolution-admission/input stores remain evidence stores. This executor activation is distinct from Imperator decision competence. |
| N11 | Completed-successor evidence | EXISTS_CANONICALLY | Reconciled lifecycle-successor contract binds decision, target, ACTIVE executor activation, descriptor, assurance, execution boundary, operation/root, consumed activation authority, validity and non-authorities. Fixture store loads only `var/imperium/evidence/provider-binding-activation-state-reconciliation/lifecycle-successors`. Contract/fixture existence proves no production event. |
| N12 | Native successor creation producer and loader | ABSENT | `ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract::STATUS` is `INERT_NOT_EXECUTED`. Production-adoption store persists caller v2 decisions/authorities and adoption targets in evidence paths. No canonical completed-successor plus creation-consumption producer/store/loader joins those records to the transition. |
| N13 | Source shape and eligibility joins | EXISTS_FRAGMENTED | Reconciliation validator expects `activation_id` and flat principal id/generation/process fields, and flat descriptor `provider_id`. Native activation uses `principal_activation_id` and nested `principal`; native descriptor uses `provider_implementation.provider_id`. A caller flattening/resealing arrays cannot become provenance. Typed native resolution must bind the original records and reject identity, generation, instance, source-digest, scope, expiry or revocation mismatch. |
| N14 | Selected canonical admission model | EXISTS_CANONICALLY | `GovernedProviderExecutionSuccessorAdmissionV3Contract` selects `imperium.la-cortine.governed-provider-execution-admission/v3`; validator checks exact shape, joins and false action invariants. Native status remains `NOT_IMPLEMENTED`. It validates supplied evidence without loading production sources. |
| N15 | Canonical v3 producer and consumer | ABSENT | No executable implementation of the selected v3 schema was found. The isolated admission uses `imperium.provider-successor-executable-admission/v3`. Existing admission services resolve legacy `ATTESTED_INERT`/`ACTIVATED_UNCONSUMED` lineage and commit effect-start permission; they cannot be called as a pre-effect v3 adapter. |
| N16 | Immutable initial binding | EXISTS_CANONICALLY | `ProviderImplementationBindingService::bind()` loads Imperator binding authority and canonical tool, then writes `BOUND_INACTIVE` to `var/imperium/offices/la-cortine/provider-implementation-bindings`, followed by generic authority consumption. The descriptor is not a mutable operation-state register. |
| N17 | Existing binding readers | EXISTS_FRAGMENTED | Eleven direct descriptor readers are identified below. They resolve initial binding/legacy activation or historical result evidence. None reads the isolated commit's successor projection. A label claiming operation-scoped activation cannot alter their behavior. |
| N18 | Authoritative operation-scoped binding reader | ABSENT | No reader joins native completed successor, canonical v3 admission, adoption, consumption and receipt into effective state for the exact operation. Raw descriptor readers must retain historical interpretation; future transition admission must actually use the new authoritative reader. |
| N19 | Cooperative aggregate persistence | EXISTS_CANONICALLY | `TransitionStore` realpaths a provisioned directory, rejects nested locks, uses `domain.lock`, exclusive `.pending`, exact write length, flush/fsync and rename. Seven logical outcomes share one `commit.json` publication. Generic `AtomicTransition`, immutable and consumption stores offer separate lock/rename operations, not a shared multi-store transaction. |
| N20 | Native revalidation and writer serialization | ABSENT | The isolated lock covers grant/authority/journal/commit only. Native constitution, lifecycle, decision and binding writers use other locks or fixture writes. Checking native files twice under an unrelated lock cannot exclude a concurrent native revocation or generation change. |
| N21 | Root identity and lock ownership | EXISTS_FRAGMENTED | Isolated root hashes instance/binding/operation; physical identity hashes normalized realpath. Reconciliation root is a six-field object including principal activation; later v3 root is a string. No native mapping, common writer domain or cross-protocol exclusion exists. Successor or principal changes must not generate another winner for the same operation. |
| N22 | Expanded durable write set | EXISTS_FRAGMENTED | Seven isolated records plus grant/authority/journal/revocation/refusal exist, but carry configured hashes. Native act, lifecycle, issuance, creation, snapshot and reader-version references are missing; exact additions and store owners are inventoried below. Generic authority consumption cannot be published independently of a native combined winner. |
| N23 | Separate-process and interruption evidence | EXISTS_FRAGMENTED | Historical Batches 4–7 proved isolated contention and nine authority/journal/commit process cuts; required Batch7 tests cover clock, substitution and copied roots. They do not cover native lifecycle writers, native creation, migration or reader visibility. No runtime proof was run during this preparation. |
| N24 | Replay and migration | ABSENT | No compatibility dispatcher or legacy-outcome reconciliation exists. A different root/schema could otherwise permit a second attempt. Legacy records must remain protocol-scoped evidence; their success cannot be grandfathered into native admission. Pending/unknown legacy outcomes cannot authorize retry. |
| N25 | Independent reconstruction | EXISTS_FRAGMENTED | `TransitionReconstructor` independently checks the isolated seven-record chain and returns ABSENT/REFUSED/INCOMPLETE/COMMITTED/UNKNOWN_REPLAY_PROHIBITED without locks or repair. It reconstructs grant-derived decisions, not source competence. Missing grant returns ABSENT before checking pending/orphan outcomes; a native reader must classify the entire root, not infer emptiness from one missing file. |
| N26 | Secret and capability exclusion | EXISTS_FRAGMENTED | Isolated exact keys/digest-shaped values, native key scanners and false effect flags provide bounded checks. A digest-shaped value can encode a secret; generic native stores accept broader arrays. Complete native-input, durable-field, diagnostic and reconstruction exclusion remains unproved. |
| N27 | Platform and operational provisioning | DEFERRED_BOUNDARY | Local cooperative PHP/filesystem assumptions only. Directory fsync, physical power-loss, hostile-writer resistance, network/distributed storage and deployment are unproved. Service exclusion remains; no operator grant, storage root or native object is provisioned here. |
| N28 | Provider and execution perimeter | DEFERRED_BOUNDARY | Credentials/capabilities, provider invocation, external I/O, effect start, retry, Iron Gate and Lazaretto remain closed. Preparation does not implement v3 or change binding interpretation. `BOUND_INACTIVE`, native v3 `NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED` remain binding. |
| N29 | Further implementation authorization | DEFERRED_BOUNDARY | Only Preparation Batch 0 is authorized now. Seven planned stages remain, with correction stages added if required. Terminal Batch 7 must start separately from clean merged Batch 6 main. Passing documentary tests cannot close the native campaign. |

## Exact constitution and acyclic lineage obligations

N01/N03/N09 are `ABSENT`/`EXISTS_FRAGMENTED` as classified above. The smallest
lawful existing-instance route is an explicit Operator Root act for the exact
transition competence, referencing the current native principal generation and
preserved scopes; a mechanical next-generation pending principal; separately
authorized activation and unambiguous retirement/supersession of the prior
competence; then a native current-generation loader. The initial missing-principal
route must not overwrite an existing principal, reopen founding personnel, or
turn ordinary lifecycle `authority_scope_changed=false` into scope widening.
No concrete instance or principal is selected here. The Root-act issuer and
trusted ingress remain design obligations, not an invented installed route.

N08 is `EXISTS_CANONICALLY` as a target shape. N09 remains `ABSENT` as execution.
The required seal dependency order is:

1. Root act and fixed target values, then pending principal and separate lifecycle evidence.
2. Current competent principal evidence and completed production successor/creation evidence.
3. Pre-effect admission/adoption **targets**, then exact attributed decision with a value-shaped issuance target.
4. Custody and delivery **boundary targets**, then issuance referencing the sealed decision and those targets, then the single-use authority.
5. Locked native revalidation, then consumption, actual canonical v3 result, adoption and operation binding result within one publication.
6. Winner over prior records, then receipt over the winner; the aggregate encloses both.

No decision may require the digest of the admission it is authorizing. No scope
grant may require a successor digest that itself includes that grant's final
digest. Use exact target identity/value commitments for forward edges and sealed
references only for backward edges. Distinguish predeclared delivery boundaries
from an actual custody/delivery result; process-local delivery identity must not
be persisted or reconstructed as a capability. Exact consumer and transition
values need validation, not generic identifier acceptance. This is an obligation
for Batch 1, not a new executable schema.

## Binding-reader inventory

N17 is `EXISTS_FRAGMENTED`; the following are the eleven direct descriptor
readers found by `ProviderImplementationBindingService::BINDINGS` and literal
directory searches across `src`. Method/resolve/status portions were inspected.

| Reader | Classification | Existing meaning and migration obligation |
| --- | --- | --- |
| `ProviderBindingActivationDecisionService::decide()` | EXISTS_FRAGMENTED | Reads descriptor against execution claim; requires inactive binding. Preserve this initial/legacy meaning. |
| `ProviderBindingActivationIssuanceService::issue()` | EXISTS_FRAGMENTED | Joins legacy decision, claim and inactive descriptor; cannot issue native transition authority. |
| `SingleExecutionProviderBindingActivationService::activate()` | EXISTS_FRAGMENTED | Resolves descriptor for a claim-bound legacy activation. No successor projection read. |
| `SingleOperationProviderBindingActivationIssuanceService::issue()` | EXISTS_FRAGMENTED | Resolves boundary, attestation and binding for legacy single-operation activation. |
| `DurableProviderExecutionAuthorityIssuanceService::issue()` | EXISTS_FRAGMENTED | Requires legacy lineage and inactive descriptor; separate effect authority is not transition authority. |
| `ProviderBindingActivationRevocationAuthorityIssuanceService::issue()` | EXISTS_FRAGMENTED | Resolves descriptor for legacy activation revocation; not a native successor lifecycle authority. |
| `GovernedProviderExecutionAdmissionService::admit()` | EXISTS_FRAGMENTED | Original legacy admission, including effect-start checkpoint; excluded from this repair's pre-effect route. |
| `GovernedProviderExecutionCombinedAdmissionService::admit()` | EXISTS_FRAGMENTED | Legacy activation-keyed combined consumption/effect-start; selected v3 must not fall back to it. |
| `GovernedStationaryCredentialResolutionService::prove()` | DEFERRED_BOUNDARY | Descriptor resolution within credential corridor; no invocation or migration authorized here. |
| `GovernedStationaryCredentialResolutionV2Service::prove()` | DEFERRED_BOUNDARY | Same separate credential boundary for combined admission. |
| `GovernedToolResultReconstructionService::reconstruct()` | EXISTS_FRAGMENTED | Historical result-to-descriptor reconstruction; cannot report native transition success. |

Contract-only consumer postures (Curia authorization, Clavium validation,
request encoder/evidence decoder/receipt reconstruction) are not callable native
binding readers. The new authoritative reader must be called by the native
transition/admission path and independently by reconstruction. It must resolve
one complete commit, exact operation, original descriptor, successor and lifecycle;
no commit means no inferred successor. It must refuse missing, partial, mixed,
substituted, expired or revoked eligible state while preserving archival outcomes.
Unrelated operation queries retain their original descriptor interpretation.

## Roots, stores and durable-field delta

N21/N22 are `EXISTS_FRAGMENTED`. This table inventories necessary future additions;
logical names are design obligations, not provisioned filesystem paths.

| Domain | Classification | Existing storage/root and required durable additions |
| --- | --- | --- |
| Constitution/competence | EXISTS_FRAGMENTED | Runtime principal versions plus evidence constitution/lifecycle directories. Require authenticated Root act id/schema/digest and source identity; exact instance, principal, binding, source/next generation, allowed scope delta, preserved scopes, target, issuance/consumption winner and separate activation references. Add native lifecycle ownership/current-generation/revocation source; do not reuse evidence paths as provenance. |
| Decision/custody | EXISTS_FRAGMENTED | Decision-provenance aggregate is for executor activation; isolated `authority.json` is grant-derived. Require native decision and issuance authorization references, attributed principal/generation/scope, exact issuance target, consumer/transition, custody/delivery boundary, single-use identity, validity, revocation and consumption state. Physical store mapping and trusted ingress are absent. |
| Successor/creation | ABSENT | Require a production namespace, original successor/descriptor/activation/assurance/boundary references, exact operation, source generation, decision and creation authority/custody/consumption, creation winner, effective/expiry/revocation, immutable output digest and producer identity. The completed successor is an input to adoption, not synthesized from a grant. |
| Contention identities | EXISTS_FRAGMENTED | Retain instance + stable binding + exact operation as exclusion domain, independent of contender successor/principal/authority. Bind a canonical physical store and native instance; define validated mapping from six-field legacy roots and v3 string roots. Add generation-wide scope issuance uniqueness and authority-wide single-use exclusion across operation roots. Root format/version and mapping evidence must be durable and reconstructible. |
| Native snapshot/journal | ABSENT | Require original source id/schema/digest plus lifecycle version/disposition/revocation references, observed current generation, validation time and minimum expiry, operation/root/consumer, target identities, protocol version and reader interpretation version. Journal intent grants no authority. Source revisions must be fenced through publication, not merely timestamped. |
| Combined publication | EXISTS_FRAGMENTED | Retain seven logical keys below in one visibility boundary. Replace grant-only references with authenticated native source and validation references. Winner binds the complete ordered record set; receipt binds winner/root/schema/operation/native snapshot, outcome, commit time and explicit no-effect/no-retry limits. Native consumption outside the aggregate must not become an independently visible second truth. |
| Refusal and recovery | EXISTS_FRAGMENTED | Existing `refusal.json`, `revocation.json`, journal and pending files. Require sanitized stable reason codes, exact request/protocol/root/source references where known, and distinction between no attempt, refusal, prepared/incomplete and unknown. Missing or corrupt provenance never means permission to recreate an outcome. |
| Migration | ABSENT | Require explicit legacy/new protocol discrimination and same-operation exclusion evidence. Preserve legacy source schema/digests and status; no live conversion, automatic copy, reseal, reset or aliasing into native stores. Any future mapping/disposition record adds its own cut and independent reconstruction obligation. |

The retained combined write-set keys are:

1. `authority_consumption`
2. `v3_admission`
3. `adoption_join`
4. `source_binding_transition`
5. `successor_binding_activation`
6. `winner_target`
7. `receipt_target`

N20 is `ABSENT`: one transition lock must cover read, validation, native lifecycle
revalidation, reservation and commit. Native lifecycle/revocation/generation
writers must participate in the same ordering or an equivalent guarded revision
protocol. Inventory every participating writer before choosing an order. Generic
locks currently nest authority then immutable-directory locks; new code must not
reenter the non-reentrant domain lock or acquire native locks in reverse order.
Keep all seven outcomes invisible until one aggregate commit. If added native
stores cannot share that commit point, define a recoverable visibility protocol
and prove it before adoption; multiple successful renames are insufficient.

## Migration, cuts, replay and reconstruction obligations

N24 is `ABSENT`. Retain the isolated implementation and historical proof as a
distinct opt-in protocol. Keep service discovery excluded. Do not relabel old
admissions, promote configured hashes into provenance, or treat legacy COMMITTED
as native success. Future routing must reject a pinned-only request at the native
entry, preserve legacy read-only interpretation and prevent same-operation reuse
across protocols. Incomplete/unknown legacy state requires explicit later
disposition, never automatic retry. No live state inspection or migration occurred.

N23 is `EXISTS_FRAGMENTED`. Expand the nine historical process cuts to every
actual new durable boundary, preserving source/build, inputs, child observations,
restart result and unchanged-source evidence. Required cut families are:

| Cut family | Classification | Required observation |
| --- | --- | --- |
| Root act, scope issuance/consumption, pending principal, activation/supersession/revocation publication | EXISTS_FRAGMENTED | Before and after each write, flush and rename; no double grant, two current generations or active scope from a pending record. Include consumption-before-output and output-before-index gaps. |
| Decision, issuance target, custody, authority and delivery | EXISTS_FRAGMENTED | Before/after each durable publication and delivery acknowledgement if introduced; restart cannot redeliver consumed authority or reconstruct process-local capability. |
| Production successor and creation-consumption winner | ABSENT | Before/after reservation, consumption, successor and winner visibility; no completed successor without exact creation provenance, and no orphan creation promoted into eligibility. |
| Lock acquisition, native snapshot and revalidation | ABSENT | Race expiry, revocation and competing generation/successor changes before lock, after snapshot, after journal and immediately before publication. A blocked writer must lose or serialize; a stale reader must refuse. |
| Journal and combined commit | EXISTS_FRAGMENTED | Before open, short/failed write, flush/fsync failure, before rename, after rename and before return; all seven records visible together, no winner-only or receipt-only native outcome. |
| Native binding read and restart reconstruction | ABSENT | Read during every cut; no partial state accepted, stale source substitution refused, exact committed archive remains read-only. Test absent grant with orphan/pending records, mixed roots and changed schemas. |
| Compatibility routing/mapping/disposition | ABSENT | If later introduced, cut before/after each migration record and reader switch; same operation cannot gain a second winner by protocol or path alias change. |

N25 is `EXISTS_FRAGMENTED`. Independent reconstruction must start from durable
root contents and trace Root act → current-at-commit principal/lifecycle → exact
decision → issuance/custody/authority → consumption → creation/successor →
canonical v3/adoption → operation binding → winner/receipt. Recompute every
reference against source records and source ownership; do not copy producer
eligibility booleans. Distinguish validity at commit from current eligibility:
later expiry/revocation preserves attribution but grants no new execution.
Exact replay is read-only; changed fingerprints, partial writes, contradictory
winners, missing source records and unprovable outcomes refuse with
`UNKNOWN_REPLAY_PROHIBITED`. No automatic repair, reissuance or retry follows.

N26 remains `EXISTS_FRAGMENTED`: allowlist every added durable field and inspect
native input, journal, receipt, diagnostics and read-only output, including nested
and encoded synthetic canaries. No actual credential or capability is test input.
N27 remains `DEFERRED_BOUNDARY`: real separate-process proof is required later;
process termination does not prove physical power-loss durability. Native records
must fit the retained store's 65536-byte/32-level bounds or receive a separately
justified correction; unchecked growth must not bypass the publication contract.

## Smallest ordered correction sequence

N29 is `DEFERRED_BOUNDARY`. Seven planned stages remain; none is implemented here.

1. **Batch 1 — native principal competence and authority lineage.** Resolve authentic Operator Root ingress, exact scope delta and versioned pending successor, separate lifecycle activation/current-generation/revocation loader, then finite target-before-result decision/issuance/custody/authority production. Prove missing/resealed/expired/revoked inputs refuse. Settle root/writer ordering and protocol discrimination before any native authority becomes usable.
2. **Batch 2 — native successor provenance.** Add production creation and exact source readers. Resolve nested native shapes without caller resealing; bind original activation, descriptor, assurance, boundary, operation and creation consumption/winner. Preserve the inactive descriptor. Missing native sources refuse before any adoption.
3. **Batch 3 — canonical v3 admission and binding consumer.** Implement the selected La Cortine contract's governed semantics and actual operation-scoped reader, with real call sites and explicit legacy compatibility. Neither a status edit nor the old effect-start admission service supplies this behavior. Keep credential/provider effects closed.
4. **Batch 4 — native atomic integration.** Replace pinned-only input provenance at the native entry, revalidate/fence all native lifecycle sources through one combined commit, join native consumption and publish a minimally reconstructible receipt. Preserve isolated protocol interpretation and cross-protocol exclusion.
5. **Batch 5 — native contention, interruption and lifecycle proof.** Exercise real separate processes, competing principals/successors, all new cut families, expiry/revocation races, path aliases, unknown restart and reader visibility through the actual native producer-to-consumer chain.
6. **Batch 6 — reconstruction and adversarial audit.** Independently reconstruct original sources and every native receipt join; challenge resealed source substitution, forged Root acts, schema relabeling, reader bypass, legacy promotion, orphan state and secret leakage. Retain measured platform limits and failures.
7. **Batch 7 — separate terminal Blackquill audit.** Start only from clean merged Batch 6 main and decide the bounded canonical integration claim. Refuse closure if any native join is still supplied by configuration, fixtures or unread projections. Preserve the prior refusal unless actually superseded by proved native integration.

PHPUnit must run after each subsequently authorized batch, with corrections before
advancing. Preparation's focused documentary checks do not execute the isolated
protocol, activate native records, or prove these future runtime properties.

## Reading ledger

Required sources, read in full (including the entire flow and Blackquill ledger):

<!-- REQUIRED_SOURCES_START -->
- `docs/next-campaign-executable-atomic-transition-native-integration-remediation.md`
- `docs/provider-binding-successor-executable-atomic-transition-batch-8-terminal-audit-v1.md`
- `docs/handoffs/provider-binding-successor-executable-atomic-transition-terminal-audit-refused.md`
- `docs/provider-binding-successor-executable-atomic-transition-implementation-v1.md`
- `docs/provider-binding-successor-executable-atomic-transition-preparation-inventory-v1.md`
- `src/Imperium/Runtime/ProviderTransition/TransitionContract.php`
- `src/Imperium/Runtime/ProviderTransition/TransitionAuthority.php`
- `src/Imperium/Runtime/ProviderTransition/TransitionConsumer.php`
- `src/Imperium/Runtime/ProviderTransition/TransitionStore.php`
- `src/Imperium/Runtime/ProviderTransition/TransitionReconstructor.php`
- `src/Imperium/Runtime/Imperator/ImperatorRuntimePrincipalVersionV3Contract.php`
- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionService.php`
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciledLifecycleSuccessorContract.php`
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3Contract.php`
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionSuccessorAdmissionV3ContractValidator.php`
- `src/Imperium/Runtime/LaCortine/ProviderImplementationBindingContract.php`
- `src/Imperium/Runtime/LaCortine/ProviderImplementationBindingService.php`
- `tests/Imperium/Runtime/ExecutableTransitionBatch7Test.php`
- `tests/Imperium/Runtime/ExecutableTransitionBatch8Test.php`
- `config/services.yaml`
- `docs/delegate-mission-flow.md`
- `todo/blackquill-todos.md`
<!-- REQUIRED_SOURCES_END -->

Additional sources are listed below with bounded-read qualifications where used.
Search-only hits were not treated as full-file reads. Historical transitive links
were followed only to resolve exact producers, stores, lifecycle or readers.

<!-- ADDITIONAL_SOURCES_START -->
- `docs/handoffs/executable-atomic-transition-native-integration-remediation-campaign-ready.md` — entry and exact required-source list.
- `docs/handoffs/executable-atomic-transition-native-integration-remediation-preparation-batch-0-local-ready.md` — documentary deliverables and test command.
- `docs/handoffs/README.md` — first 65 lines; continuation index and historical precedence.
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalConstitutionAuthorityContract.php` — initial routes, Root identity and fixed scope.
- `src/Imperium/Runtime/Imperator/FutureInstanceImperatorPrincipalConstitutionService.php` — future-instance producer/store.
- `src/Imperium/Runtime/Imperator/ExistingInstanceImperatorPrincipalRemediationService.php` — existing-instance producer and absence gate.
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalProvenanceFixtureStore.php` — constitution/lifecycle input custody and validators.
- `src/Imperium/Runtime/Imperator/ImperatorRuntimePrincipalVersionContract.php` — v2 schema, identity, scope and lifecycle.
- `src/Imperium/Runtime/Imperator/ImperatorPrincipalLifecycleReconstructionService.php` — current-state loader and expiry/revocation limits.
- `src/Imperium/Runtime/Imperator/CorridorDispositionPrincipalAuthorityRemediationProducer.php` — bounded scope-successor production precedent.
- `src/Imperium/Runtime/Imperator/ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract.php` — different Root competence.
- `src/Imperium/Runtime/Imperator/ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract.php` — different pending-successor route.
- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator.php` — first 120 lines; v3 pending principal and envelope joins.
- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore.php` — evidence-only v3 storage.
- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceReadOnlyAggregateReconstructionService.php` — caller-supplied offline eligibility, not source loader.
- `src/Imperium/Runtime/LaCortine/DeterministicTransitionCallerAuthorityIssuanceService.php` — exact transition allowlist and v2 loader.
- `src/Imperium/Runtime/LaCortine/DeterministicTransitionCallerAuthorityConsumer.php` — source revalidation and separate consumption lock.
- `src/Imperium/Runtime/Imperator/ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract.php` — acyclic issuance target.
- `src/Imperium/Runtime/Imperator/ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract.php` — inert issuance links.
- `src/Imperium/Runtime/Imperator/ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract.php` — exact authority and validity fields.
- `src/Imperium/Runtime/Clavium/ProviderBindingSuccessorAtomicLiveTransitionAuthorityDurableCustodyBoundaryContract.php` — custody ownership.
- `src/Imperium/Runtime/Clavium/ProviderBindingSuccessorAtomicLiveTransitionAuthorityProcessLocalDeliveryBoundaryContract.php` — delivery/non-persistence boundary.
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationService.php` — actual activation producer/store/reconstruction.
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationContract.php` — native nested activation shape.
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationCanonicalFixtureStore.php` — evidence-only resolution inputs.
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciledTargetContract.php` — operation, root and validity fields.
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciliationContractValidator.php` — first 230 lines; target/decision/successor, scope/root/validity checks.
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciliationFixtureStore.php` — fixture writer/loader.
- `src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract.php` — inert creation boundary.
- `src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorProductionAdoptionFixtureStore.php` — segregated v2 decision/authority/target evidence.
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionAdmissionService.php` — legacy admission, source resolution and effect-start semantics.
- `src/Imperium/Runtime/LaCortine/GovernedProviderExecutionCombinedAdmissionService.php` — legacy combined admission, source resolution, replay and effect-start semantics.
- `src/Imperium/Runtime/Imperator/ProviderBindingActivationDecisionService.php` — direct descriptor reader.
- `src/Imperium/Runtime/Imperator/ProviderBindingActivationIssuanceService.php` — issue/descriptor-resolution/status portions.
- `src/Imperium/Runtime/Imperator/ProviderBindingActivationRevocationAuthorityIssuanceService.php` — issue/descriptor-resolution portions.
- `src/Imperium/Runtime/Imperator/DurableProviderExecutionAuthorityIssuanceService.php` — issue/descriptor-resolution/status portions.
- `src/Imperium/Runtime/LaCortine/SingleOperationProviderBindingActivationIssuanceService.php` — issue/descriptor-resolution portions, including lines 66–105.
- `src/Imperium/Runtime/LaCortine/SingleExecutionProviderBindingActivationService.php` — activate/descriptor-resolution/status portions.
- `src/Imperium/Runtime/LaCortine/GovernedToolResultReconstructionService.php` — reconstruct/descriptor-resolution portions.
- `src/Imperium/Runtime/Clavium/GovernedStationaryCredentialResolutionService.php` — prove/descriptor-resolution/status portions only; no credentials inspected.
- `src/Imperium/Runtime/Clavium/GovernedStationaryCredentialResolutionV2Service.php` — prove/descriptor-resolution/status portions only; no credentials inspected.
- `src/Imperium/Runtime/Persistence/AtomicTransition.php` — native lock scope.
- `src/Imperium/Runtime/Persistence/ImmutableRecordStore.php` — immutable publication and read semantics.
- `src/Imperium/Runtime/Persistence/AuthorityConsumptionStore.php` — native consumption root and replay.
- `src/Bootstrap/CanonicalJson.php` — digest encoding.
- `tests/Imperium/Runtime/ExecutableAtomicTransitionNativeIntegrationRemediationCampaignReadyTest.php` — required documentary gate.
- `tests/Imperium/Runtime/ProviderBindingSuccessorExecutableAtomicTransitionPreparationBatch0Test.php` — documentary test precedent.
- `composer.json` — local PHP/PHPUnit requirements; no scripts or installation run.
<!-- ADDITIONAL_SOURCES_END -->

Source/path searches covered `src` and `config` for principal constitution/v3,
lifecycle callers, direct descriptor readers, successor production, selected v3
admission and `ProviderTransition` callers. No applicable AGENTS.md was found.
Missing `phpunit.dist.xml` and `tests/bootstrap.php` were not source reads.
No runtime console command, provider tool, operational verifier or network call ran.
The Blackquill skill was read at `C:/Users/gatom/.codex/skills/blackquill/SKILL.md`.
The new inventory, handoff and documentary test were reviewed after creation.

## Validation and completion

PHP 8.4.14 / PHPUnit 13.3.0: the campaign-ready and Preparation Batch 0
documentary set passed **12 tests, 296 assertions**. PHP lint and diff whitespace
checks passed. The first run exposed a documentary reader-name parser that omitted
the digit in `V2`; it was corrected without weakening the exact eleven-reader
equality check. Tests cover the required-source ledger, allowed classifications,
actual descriptor-reader inventory, seven-part write set, source-shape mismatch,
closed native status, handoff consumers and ordered correction/proof gates.
These are documentary checks, not operational proof. No full runtime suite was
run in this documentary-only batch. This batch changes documentation and
documentary tests only.
No corrections or runtime behavior are implemented. No principal or successor is
created or activated; no authority issued or consumed; no transition state written;
no live transition lock acquired; no v3 admission implemented; no binding
interpretation changed; no live grant provisioned. No credentials/capabilities,
provider invocation, external I/O, effect, retry, Iron Gate or Lazaretto action.
`BOUND_INACTIVE`, native v3 `NOT_IMPLEMENTED` and `UNKNOWN_REPLAY_PROHIBITED` remain.
Preparation completion does not close the remediation or predecessor campaign.
