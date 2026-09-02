# Atomic Transition Independently Verifiable Reproof Preparation Inventory v1

`PREPARATION_BATCH_0_COMPLETE_INDEPENDENTLY_VERIFIABLE_REPROOF_BOUNDARY_CLASSIFIED`

Prepared from clean main at `3c4f8b2328570bdd0467463204301cddca99007a`, the PR
#736 selection merge whose first parent is `4746f91`. The requested `1ac9ede`
object was unavailable: the ancestry command exited 128, not success. The
operator explicitly approved using `3c4f8b2` instead. No fetch, pull or other
external I/O was performed for preparation.

This is a documentary inventory, not a v2 contract, evidence package, verifier
report, receipt or execution result. The v1 disposition remains
`CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT`.
The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
Provider binding remains `BOUND_INACTIVE`; required v3 execution admission
remains `NOT_IMPLEMENTED`; `UNKNOWN_REPLAY_PROHIBITED` remains binding.

## Evidence basis and reading ledger

The campaign-ready handoff and all fifteen required sources were read:

1. `docs/next-campaign-atomic-transition-independently-verifiable-reproof.md`
2. `docs/handoffs/atomic-transition-evidence-independent-verification-remediation-complete.md`
3. `docs/atomic-transition-evidence-independent-verification-remediation-batch-4-local-refusal.md`
4. `docs/atomic-transition-evidence-independent-verification-remediation-preparation-inventory.md`
5. `docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-5-integrated-disposable-mission.md`
6. `docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json`
7. `src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceAdversarialCaseContract.php`
8. `src/Imperium/Runtime/Imperator/AtomicTransitionTrustedCaseExecutionCorridor.php`
9. `src/IndependentVerification/AtomicTransitionArtifactAndReceiptVerifier.php`
10. `src/IndependentVerification/AtomicTransitionIndependentVerificationAdmissionConsumer.php`
11. `tests/Imperium/Runtime/AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch5Test.php`
12. `tests/Imperium/Runtime/AtomicTransitionEvidenceIndependentVerificationRemediationBatch2Test.php`
13. `tests/Imperium/Runtime/AtomicTransitionEvidenceIndependentVerificationRemediationBatch5Test.php`
14. `docs/delegate-mission-flow.md`
15. `todo/blackquill-todos.md`

Additional source inspection covered the local-ready handoff, handoff index,
`tools/run-atomic-transition-disposable-proof.ps1`,
`tools/run-atomic-transition-integrated-mission.php`, current case/fixture/
mutation/expectation contracts, deterministic executor and disposable classifier,
graph deriver, complete-chain exclusion service, independent input/report/
identity/attestation contracts and validator, preflight, detached verifier,
terminal refusal auditor, legacy reconstructor/closure services and contracts,
`config/services.yaml`, and synthetic signing-test inheritance in remediation
Batch 3. Source searches were restricted to repository source/config/tools/tests.
No operator-local path was selected, enumerated, opened, copied or hashed.

Evidence labels below distinguish SOURCE_OBSERVED from PROPOSED_V2_REQUIREMENT.
Private receipt availability, integrity and custody are UNKNOWN_NOT_INSPECTED.
Historical preparation findings are not assumed current where later code differs.

## Missing acceptance-case evidence

SOURCE_OBSERVED: the public v1 summary has eight ordered labels and no
`acceptance_case_evidence_digest`. The integrated mission's `run()` passes one
EMPTY fixture, NONE mutation and ABSENT/NO_ACTION expectation through
`AtomicTransitionTrustedCaseExecutionCorridor::executeCase`. Its separate
`acceptanceMatrix()` constructs journal/winner/receipt arrays, calls the
classifier directly, compares eight hard-coded labels and returns only labels.
The receipt builder stores one case/fixture/mutation/expected/plan/trusted-result
chain, not all eight matrix inputs and observations. Source can describe those
inputs but cannot recreate retained evidence of the historical execution.

The companion
`docs/atomic-transition-independently-verifiable-reproof-preparation-cases-v1.tsv`
enumerates all eight cases, legacy outcomes, missing inputs, expectations and
observations. Every row is NOT_RETAINED_IN_V1_MATRIX_NOT_EXECUTED_IN_BATCH_0.
The empty fixture resembles the first matrix case; no explicit per-matrix-case
binding proves it is the retained operand of that matrix evaluation.

PROPOSED_V2_REQUIREMENT for every row: retain a stable ordered case ID/kind,
root and cut, exact primary and optional comparison fixture bytes, source schema
versions, plan, mutation descriptor and replacement material (including explicit
null/absence), independently predeclared expectation, observed classification,
directive, comparison, sanitized validator error, finding codes and executor
identity. Bind input/expectation/observation digests into one ordered case-set
and result-set manifest. Reject missing, extra, duplicate or reordered rows.
Expectation is a target, not proof; the verifier derives observations again
and compares both runner observations and predeclared expectations.

Exclusion evidence is additional to these eight rows. Retain the exact scanned
artifact set/order and policy/version, bounded decoding and split-value rules,
safe synthetic negative-vector IDs and sanitized refusal observations. A bare
`complete_chain_content_exclusion_observed=true` is insufficient. Never retain
an actual forbidden value merely to prove its refusal.

## Current schemas and proof/verifier coupling

All current schemas below are v1 and remain unchanged. “Receipt” can refer to
an inert transition fixture or to an operator-local proof-package receipt;
these are not interchangeable.

| Surface | Current source/schema and behavior | Classification / v2 obligation |
| --- | --- | --- |
| Runner | PowerShell launcher plus integrated mission script; no separate runner-result schema | SOURCE_OBSERVED: launcher exports source bindings and output locators; direct inclusion of the PHP script runs the mission. Read as text only. |
| Typed case | `AtomicTransitionEvidenceAdversarialCaseContract`, `imperium.imperator.atomic-transition-evidence-adversarial-case/v1` | References fixture(s), mutation and expected result; lacks complete eight-case receipt retention. |
| Fixture/mutation/expectation | `AtomicTransitionEvidenceFixtureContract`, `AtomicTransitionEvidenceMutationContract`, `AtomicTransitionEvidenceExpectedResultContract`; corresponding `imperium.imperator.atomic-transition-evidence-{fixture,mutation,expected-result}/v1` | Fixtures retain evidence; replacement digest alone is insufficient without permitted replacement material. Partial-write shape must be explicitly representable, not mislabeled COMPLETE. |
| Trusted result | `AtomicTransitionProvenanceBoundCaseResultContract`; produced only after executor expectation match | Source-derived observations exist for one case; provenance fields are copied from validated origin. Validation is not execution-origin authentication. |
| Private package | `imperium.private-atomic-transition-integrated-disposable-mission/v1`, inline builder in `run()` | One case chain plus matrix labels; receipt bytes remain UNKNOWN_NOT_INSPECTED. No repair, replacement or schema extension of v1. |
| Public summary | `imperium.sanitized-atomic-transition-integrated-disposable-mission-evidence/v1` | Digests, matrix labels, booleans, PROVED; self-hash pins producer output but does not prove its conclusions. |
| Verifier input | `imperium.atomic-transition-independent-verification-input/v1` | Caller-supplied input/summary/receipt/artifact byte arrays; availability/locator flags are assertions. No source loader or independently authenticated intake is established by this method. |
| Report | `imperium.atomic-transition-independent-verification-report/v1` | Eight domains: source_and_build, receipt_structure, origin_and_provenance, trusted_result, dependency_graph, acceptance_matrix, complete_chain_exclusion, non_authority_perimeter. |
| Preflight | `imperium.atomic-transition-local-verification-preflight/v1` | Checks eight matrix entries plus digest syntax; eligibility is not verification or authorization. Never add a digest to v1 to defeat its refusal. |
| Public identity / attestation | `imperium.atomic-transition-independent-verification-public-identity/v1`, `imperium.atomic-transition-independent-verification-detached-attestation/v1` | Ed25519 purpose-bound public verification; authority-empty contract validation accepts only unsigned attestation shape, detached verifier handles signed records separately. Future phases must preserve that distinction. |
| Admission | `imperium.atomic-transition-independent-verification-admission/v1` | Requires all report domains PASS and pinned identity/key/verifier digests, then detached verification; returns pending-terminal-audit with qualification_removed=false and campaign_closed=false. |
| Terminal refusal | `imperium.atomic-transition-independent-verification-terminal-audit/v1` | Accepts only retained preflight refusal; cannot issue accepted reproof closure. |

SOURCE_OBSERVED coupling:

- Trusted corridor imports the deterministic case executor and provenance
  validator. The executor imports the runtime classifier/reconstructor; the
  matrix calls that same classifier outside the trusted case corridor. A second
  wrapper around these evaluators would not be an independent verifier.
- The current artifact verifier imports neutral CanonicalJson and the shared
  independent-verification contract validator, not the runner or legacy closure
  evaluator. It hashes supplied artifact bytes and seals. Origin/provenance,
  trusted-result and graph domains largely compare section digests; they do not
  independently derive all semantic links or execute the retained cases.
- Its acceptance_matrix domain is unconditionally INDETERMINATE. Other PASS
  domains must not be inflated into semantic proof. Non-authority checks read
  five booleans; exclusion uses limited recursive patterns rather than the
  producer's richer structural/encoding-aware policy.
- Remediation Batch 2 tests use minimal sealed synthetic sections and require
  all other domains PASS. Batch 3's `passingReport()` manually changes the
  acceptance domain and disposition to PASS; `signed()` creates synthetic
  signing material. Batch 5 inherits those helpers. These prove consumer
  mechanics with synthetic inputs, not a verified operational package. They
  are read, not run, during this preparation.
- Source-review gaps for later proof include report verifier_identity binding
  to the pinned identity, report-to-summary/receipt/source joins, complete
  domain semantics, exception sanitization and dependency-set derivation.
  No new exploit execution or conclusion of live compromise is claimed.

PROPOSED_V2_REQUIREMENT: separate verifier-owned case evaluation, validation,
graph/exclusion derivation and conclusion construction from producer evaluators.
Only specified neutral serialization, hashing and audited cryptographic
primitives may be shared. Bind exact verifier implementation and its dependency
set, and test independence transitively rather than through import-name checks.

## Public, operator-local and forbidden evidence

| Class | Permitted contents | Custody / release rule |
| --- | --- | --- |
| PUBLIC_REPOSITORY | Versioned contracts and preparation documents; safe synthetic test descriptions; non-sensitive case IDs and ordered digest commitments; sanitized candidate/report with stable reason codes; public purpose-bound identity and detached signature after authorization | No evidence admission now. A candidate is not an admitted report. Publish only independently sanitized fields after the appropriate later gates. |
| OPERATOR_LOCAL_ONLY | Exact new v2 case/fixture/mutation/expectation/observation chain, private proof receipt, local source/build snapshots, detailed graph and exclusion scope, receipt locator, private diagnostics and custody records | Retain for later explicit Batch 6 intake. No private bytes, paths, host/user metadata or detailed topology in Git, prompts, logs or public reports. Availability remains unknown until authorized local intake. |
| FORBIDDEN_EVIDENCE_PAYLOAD | Live provider credentials, secret values, environment dumps, capability objects/handles, private signing keys/seeds, memory dumps, serialized execution authority | Never valid receipt/case/report contents, even encrypted, split, encoded or hashed as a substitute for safety. Refuse without retaining the offending value. |
| SEPARATE_SIGNING_CUSTODY | Operator-controlled private signing material or narrow non-exportable signing capability, only later Batch 6 | Not evidence payload and not accessible to the runner. No key selection, creation, path lookup or signing now. Sign only the sanitized fully passing report. |

The operator-local category does not permit secrets in receipts. Safe synthetic
markers may later test exclusion, but do not use real secrets or publicly known
synthetic test keys as an operational trust anchor. Public hashes of detailed
artifacts do not grant receipt access or prove availability. A signer identity
must be trusted independently of the package asking for admission.

## Provenance derivation and acyclic binding

| Binding | SOURCE_OBSERVED v1 | PROPOSED_V2_REQUIREMENT |
| --- | --- | --- |
| Source commit/tree | Launcher uses clean git status, HEAD and HEAD tree object; tree digest is SHA-256 of the textual tree object ID | Declare object format, encoding and exact projection. Verify commit-to-tree-to-blob membership from retained objects; supplied matching hashes alone do not establish this chain. |
| Lock/runner/mission | Launcher hashes actual local files; PHP accepts environment-provided values | Retain exact source-commit-relative artifacts, raw-byte hash rules including line endings, and verify checked-out/loaded bytes match pinned snapshot. Exclude environment values from evidence. |
| Build | SHA-256 of LF-joined commit, tree digest, lock digest, runner digest, mission digest | Call this a defined build identity, not a compiled artifact or installed dependency attestation. Bind actual dependency/install projection and runtime metadata or explicitly limit the claim. |
| Runtime/version | PHP_VERSION is observed by producer; origin uses it | Bind supported runtime identity and observation source; treat unavailable independent corroboration as a limit, not copied-string proof. |
| Origin/authorization | Fixed experiment IDs, stub authority/principal/dossier references and fixed validity times | A new unique mission/proof identity and separate exact execution authorization are required. Do not present stubs or an authority-empty origin as competent execution permission. |
| Case roots | Fixture/mutation/expectation/case/package roots are hashes of labels | Derive roots from exact ordered retained artifacts; no placeholder roots or future final receipt digest in pre-execution input. |
| Executor graph | Reflects allowed resolved object dependencies, hashes current class files, scans effect patterns, de-duplicates nodes by class | Bind actual nodes/edges and coverage limits; runtime object graph excludes launcher/output writer and static/global/native dependencies. Define whole runner/verifier boundary and separately inspect persistence/process/environment edges. |
| Report/identity | Verifier identity is caller-supplied; admission constructor pins separate identity/key/verifier digests | Bind report identity to those exact trusted facts, artifact roots, receipt and case-set; independently derive verifier dependency digest and key purpose. |

PROPOSED_V2_REQUIREMENT seal order: pre-execution case profile and artifact
manifest -> origin/authorization reference -> per-case execution observations
and graph/exclusion observations -> ordered result manifest -> private receipt
-> sanitized candidate -> independent report -> detached attestation -> repository
admission -> separate terminal decision. No earlier sealed object requires a
later object's final digest. Signing uses domain-separated, versioned sanitized
report bytes/digest, never a producer summary alone. Recomputed agreement proves
defined evidence consistency; executor provenance still needs the separately
authorized trusted execution/custody record.

## Persistence, interruption and replay boundaries

SOURCE_OBSERVED: the eight legacy cuts are in-memory classifier snapshots, not
crash tests of durable runtime transitions. `same_root_contention` compares two
arrays; it does not demonstrate simultaneous-process lock exclusion. The runner
writes the private receipt and then summary using separate `file_put_contents`
calls with LOCK_EX. There is no pairwise atomic publication, no-overwrite guard,
durable package state machine or single-run consumption journal. Fixed output
names can be overwritten by a later invocation. Clean tracked worktree checks
do not prove no writes to ignored runtime locations. None of these findings
authorizes reproducing a write or inspecting existing output.

PROPOSED_V2_REQUIREMENT: distinguish semantic case replay from proof-run retry
and package-publication recovery. A unique proof/run ID and immutable input
root must bind all artifacts. Exact recovery may validate already retained
bytes without re-executing a mission. Changed inputs, reused identities,
stale receipts or uncertain execution state must refuse automatic rerun.

| Future package cut | Required later classification and action |
| --- | --- |
| Before reservation/execution | No receipt or success claim; explicit future authorization still required |
| Reserved or case execution started, incomplete observations | Incomplete/unknown; no automatic mission restart or fabricated completion |
| Case artifacts complete, no final receipt | Unadmitted partial package; validate immutable artifacts under a separately specified recovery rule |
| Receipt write incomplete | Refuse truncation/invalid digest; preserve failure evidence locally without replacing v1 |
| Receipt finalized, summary absent | Permit only a future specified deterministic projection from exact retained v2 receipt; never rerun cases to reconstruct missing execution evidence |
| Candidate finalized, verification absent/refused/indeterminate | Not admissible; signing blocked |
| Passing report exists, signature absent | Not admissible; signer needs separate authorization and exact report binding |
| Attestation exists, admission absent | Verify trust/purpose/source/report joins; no automatic closure |

Later package writes need no-overwrite identities, confined operator-selected
roots, bounded staging/finalization, readable-state rules and failure cleanup
that cannot touch runtime state or private unrelated paths. These are design
obligations, not implemented state names or permissions. Unknown provider-effect
replay remains prohibited even though this campaign authorizes no provider effect.

## Execution, receipt and signing custody

Batch 5 alone may receive explicit operator authorization for exact source,
runner command, case profile, disposable root, evidence destinations and one
new v2 event. The runner must own case execution, not accept caller results.
It must retain independently needed observations with no live authority or
provider access. Existing v1 locators and filenames must not be reused.

Batch 6 is a separate operator-authorized process: select only the exact new
receipt and artifacts, verify with an independently pinned implementation,
sanitize all success/refusal/exception outputs, and allow signing only after
every required domain passes. The runner cannot supply a trusted signing
identity or invoke the signer. Missing custody, missing inputs, untrusted key,
expired/wrong-purpose identity and report substitution remain fail-stop cases.
Define private temporary-data cleanup and operator retention/backup policy
before execution; no retention availability is asserted now.

Batch 7 receives only passing sanitized report/evidence, pre-trusted public
identity and detached attestation. It verifies binding again and may admit
pending-terminal evidence only. Batch 8 alone may adjudicate closure, starting
from merged Batch 7 main. Admission is not closure authority.

## Closure consumers and historical bypasses

SOURCE_OBSERVED reachability search covered src, config, tools and tests; no
container, reconstructor, verifier, admission method or terminal method ran.

| Surface | Current reachable behavior and required preservation |
| --- | --- |
| `ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService` | Direct call throws PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED; excluded from broad service discovery. Preserve. |
| `AtomicTransitionEvidenceCorrectedClosureService` | Direct call throws PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED; container-excluded. Preserve. |
| `AtomicTransitionEvidenceTerminalAdversarialAuditor` | Throws PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED before commented historical body; container-excluded. Preserve. |
| `AtomicTransitionEvidenceIndependentReconstructor` | Still directly constructible and not explicitly container-excluded; compares copied v1 constants and sets reconstruction booleans. Its result has qualification_removed=false/campaign_closed=false; not valid independent proof. |
| `AtomicTransitionEvidenceAuthenticatedClosureContract` / `AtomicTransitionEvidenceCorrectedClosureContract` | Historical accepted status vocabularies remain. Commented legacy use and exclusion-service schema allowlist do not create an accepted closure. Never reuse as a v2 bypass. |
| `AtomicTransitionArtifactAndReceiptVerifier` / preflight | Direct methods remain available, broadly discoverable by source configuration; v1 matrix is indeterminate and preflight is only eligibility. Do not relax either to rehabilitate v1. |
| `AtomicTransitionDetachedAttestationVerifier` | Verifies purpose, seals and detached signature; owns no signing capability. Later v2 must prove complete semantic bindings, not signature validity alone. |
| `AtomicTransitionIndependentVerificationAdmissionConsumer` | Direct constructor requires pinned trust facts; explicitly container-excluded. All-PASS admission remains pending audit with no qualification removal. No production caller found in bounded source search. |
| `AtomicTransitionIndependentVerificationTerminalAuditor` | Returns evidence-insufficient terminal refusal only; cannot consume v2 admission into accepted closure. A distinct later terminal boundary is needed. |
| Tests and documentation | Synthetic signing/forced-PASS tests and historical success prose are not operational evidence. Current flow, handoff index and ledger must point to preparation completion with the qualification intact. |

## Smallest ordered v2 sequence and Batch 1 boundary

Eight stages remain after this preparation; none is performed here.

| Stage | Ordered result required before progression | Authority boundary |
| --- | --- | --- |
| Batch 1 | Authority-empty v2 case input, expected result, observation, ordered matrix, artifact/provenance manifest, private receipt, sanitized candidate, independent report, identity/attestation and publication/replay design contracts | Contracts only under a later instruction; no producer, validator execution, persistence, mission, verifier or signing |
| Batch 2 | Inert evidence-complete disposable runner retaining all required case artifacts and explicit packaging transitions | No real mission; source/provenance and output-writing boundary separated from case evaluator |
| Batch 3 | Separate v2 verifier and preflight that derive all eight report domains from artifacts, not producer outcomes | No operator receipt intake or live signing; no runner-evaluator reuse |
| Batch 4 | Counterfeit/interruption proof on safe synthetic artifacts: omission/order/duplication, changed evidence, placeholder roots, source/dependency substitution, producer conclusion injection, partial writes, replay ambiguity, sanitization and trust-binding refusal | No real mission; do not promote synthetic signatures or caller-edited PASS to operational proof |
| Batch 5 | One new evidence-complete disposable execution, exact retained private receipt and sanitized candidate | Separately authorized execution; no v1 replacement |
| Batch 6 | Independently derived all-PASS sanitized report and detached attestation under pre-trusted purpose-bound identity | Separately authorized verification and signing; missing/indeterminate/refused evidence cannot be signed into success |
| Batch 7 | Strict public repository admission binding candidate/report/identity/attestation and exact source/case roots | No unsigned, producer-authored, v1 or indeterminate route; pending audit only |
| Batch 8 | Terminal Blackquill audit of actual admitted v2 chain from merged Batch 7 main | Separately sequenced; only then may CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF be considered |

The smallest Batch 1 scope is finite authority-empty descriptions of required
data, canonicalization, custody classes, references, ordered roots, failure
vocabulary and allowed transitions. Do not implement these contracts in Batch 0.
Avoid extending v1 schemas, self-referential receipt/signature digests, generic
caller success booleans, undefined build artifacts or a case matrix without
underlying operand bindings. Future implementation details must satisfy this
inventory rather than silently narrow the proof claim.

## Preparation exit and exclusions

The inventory, eight-case TSV, documentary tests, completion handoff, campaign
steps, Delegate flow, handoff index and Blackquill ledger constitute Batch 0.
Tests read only explicitly named public repository documents/source as text.
They do not import runtime classes, instantiate or execute a mission/verifier,
derive a new operational provenance record or create/use signing material.
No old verifier or signing test suite is run as a preparation check.

Do not inspect private operator-local material, implement v2 contracts or runtime
behavior, execute a mission or verifier, create or use signing material, invoke
a provider, perform external I/O, handle a live credential or capability, mutate
runtime state, repair or replace v1 evidence, admit v2 evidence, remove the
independent-verification qualification, or close the campaign. This preparation
authorizes no execution, signing, admission or closure. Only Batch 1 contracts
may next be considered under a new instruction. Stop at the preparation marker.
