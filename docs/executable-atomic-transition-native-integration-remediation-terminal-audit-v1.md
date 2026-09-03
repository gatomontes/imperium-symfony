# Native Integration Remediation terminal Blackquill audit v1

`NATIVE_INTEGRATION_TERMINAL_AUDIT_ACCEPTED_BOUNDED_PRE_EFFECT`

## Subject and verdict

Batch 7 began separately from clean locally merged Batch 6 main at
`88ed24bed037101903356e519f34eb89475844a3`. Its tree equals tested Batch 6 commit
`0a3bc30`; the audit branch is `codex/native-integration-remediation-terminal-audit`.
The user authorized full campaign implementation, PHPUnit after each batch, and
the proposed signed Operator Root trust route. This is a separately sequenced
Blackquill review by the same agent, not a claim of a second human reviewer.

The first candidate passed 1942 tests / 44998 assertions, but a subsequent
orphan-retirement reader check failed. Acceptance was held. Batch 6A corrected
that reader, first demonstrated the failing regression, then passed 1939 tests /
44961 assertions. The audit resumed from clean locally merged correction main
`9f335e3b00513f842539d82ea0d7955350612115`, tree equal to tested correction commit
`760590b`. This additional correction is recorded in
`docs/handoffs/executable-atomic-transition-native-integration-remediation-batch-6a-correction-complete.md`.

**Verdict: accept the exact native pre-effect transition within the stated local
trust and storage bounds. All eight planned stages are complete.** No live
deployment or provider execution is accepted or performed.
One additional reader correction batch was required.

The earlier verdict
`EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT`
remains correct for the isolated pinned-grant protocol and is retained in its
original documents. This verdict applies to the corrected native route; it does
not relabel the old protocol as canonical success.

## Original refusal findings reassessed

| Claim | Evidence on the merged tree | Judgment |
| --- | --- | --- |
| Competent principal and decision provenance (old T8-03) | NativeRootActs verifies a signed exact act against independently operator-owned public trust; NativePrincipal loads original native v2/v3 state, separate signed lifecycle and current generation. NativeAuthority loads that principal and the exact successor into backward decision/custody/authority joins. A configured hash is insufficient. | Corrected for the explicitly approved route. Historical constitution APIs are not globally authenticated by this change. |
| Eligible native successor (old T8-04) | NativeSuccessor loads original runtime activation and production plus original attestation, assurance, boundary and descriptor. Root explicitly signs the activation/production basis. Native creation target/decision/successor/winner publish together. Missing, resealed, stale or retroactively dated sources refuse. | Corrected. Synthetic tests call the actual production and activation services before native creation; they do not import an offline successor. |
| Selected canonical v3 admission (old T8-05) | NativeAdmission produces `imperium.la-cortine.governed-provider-execution-admission/v3`; selected validator checks the result. NativeConsumer publishes it with consumption, adoption, binding outcomes, winner and receipt. The old schema remains separately rejected. | Corrected. Historical NOT_IMPLEMENTED is the inert boundary status; actual result is ADMITTED_PRE_EFFECT. Neither shape validation nor a constant change alone admits execution. |
| Effective operation binding (old T8-06) | The discovered Symfony command calls NativeConsumer, which calls NativeBindingReader. The reader requires native current sources, exact complete commit and independent stored-edge verification. Missing authority invalidates interpretation; orphan retirement refuses even without a journal. Untouched unrelated operation stays inactive. | Corrected, including Batch 6A. This is a consumed operation interpretation; the eleven historical descriptor readers retain their separate legacy meanings. |

## Remaining claims pressure-tested

| Claim | Evidence | Bound or residual uncertainty |
| --- | --- | --- |
| Single winner | Two separate PHP processes use different exact native principals/successors and the same stable operation root; one commits and one receives a read-only replay refusal. A canonical source writer proves the actual immutable lock is held. | Cooperative writers on one local physical root. No hostile writer or distributed lock claim. |
| Atomic effect-free write set | One commit contains ordered authority_consumption, v3_admission, adoption_join, source_binding_transition, successor_binding_activation, winner_target and receipt_target. A durable journal and legacy retirement precede it. | Precursor events have separate durable publications. There is no rollback across them; uncertainty is a terminal no-retry state. |
| Interruption handling | 24 real process exits span before-open, before-publish and after-publish for principal, activation, revocation, issuance, successor, journal, retirement and final transition. Final-flush expiry is tested. | Process termination and local fsync/rename only. Directory fsync, physical power failure and arbitrary device corruption are not proved. |
| Lifecycle and replay | Current source generation, signed revocation, grant/source expiry and original creation time are rechecked. Existing attempt state dominates later expiry. A committed outcome is read-only on replay; incomplete state remains UNKNOWN_REPLAY_PROHIBITED. | Public anchor removal/rotation/revocation invalidates historical trust too. It is not silently replaced by archived or fixture trust. |
| Independent reconstruction | NativeReconstructor does not call NativeAdmission/NativeAuthority/NativeSuccessor builders, the consumer or binding reader. It verifies exact persisted joins and stable source snapshots without writes or locks. Resealed secret fields, schema substitution, renamed authority and missing retirement refuse. | The canonical principal loader, pure activation/v3 validators and digest primitives are shared. Snapshot equality is not hostile-filesystem isolation or a fresh permission to execute. |
| Legacy migration | Explicit operator-owned complete inventory; registered empty stores locked and retired. Any existing old grant/outcome/pending state refuses before native publication. Old issuance refuses retirement. | No migration of populated stores, automatic reset, arbitrary unregistered locations or historical-success conversion. Completeness is a deployment prerequisite. |
| Secret exclusion | Exact field sets and source joins, no signing API, fixed command diagnostics and public receipt-only reconstruction. Every seven-member secret-field injection refuses after resealing. | Identifiers/digests are not universal secret detectors. Public trust and source integrity remain trusted host responsibilities. |
| Application ingress | `imperium:provider-transition:execute` accepts existing authority-id only, obtains fixed kernel project directory and current time, and uses the native consumer. Tests exercise its invalid-input/missing-authority refusal; native success is exercised through the same consumer service. | No live command invocation, deployment/container warm-up or live identity provisioning was used as evidence. |

The approved Root act's exact execution basis is critical: older production
services still accept governed input arrays. Merely writing such an aggregate
does not authorize this route. A separately authenticated Root endorsement and
original native source loading are both required. This closes the selected route
without pretending to reform every historical institutional ingress.

## Validation and reading ledger

Every implementation batch had a full PHPUnit pass. Final Batch 6: **1927 tests,
44912 assertions**. Batch 7 adds direct selected-schema/native-reader proof,
copied-root and public-anchor substitution refusals, missing-authority reader
refusal, and documentary sequencing/classification checks. Final Batch 7 counts
are recorded below after the unchanged-source run.

Final unchanged-source PHPUnit: **1954 tests, 45049 assertions passed**, PHP
8.4.14 / PHPUnit 13.3.0. Focused terminal set: **15 tests, 88 assertions passed**.
Diff whitespace checks pass. The terminal branch has no runtime delta from the
clean corrected base. No failure remains unresolved.

| Stage | PHPUnit evidence |
| --- | --- |
| Preparation 0 | Focused documentary: 12 tests, 296 assertions |
| 1 | Full: 1858 tests, 44285 assertions |
| 2 | Full: 1864 tests, 44326 assertions |
| 3 | Full: 1872 tests, 44367 assertions |
| 4 | Full: 1883 tests, 44414 assertions |
| 5 | Full: 1899 tests, 44628 assertions |
| 6 | Final full: 1927 tests, 44912 assertions |
| 6A correction | Full: 1939 tests, 44961 assertions; regression failed before the fix |
| 7 resumed terminal | Final full: 1954 tests, 45049 assertions |

Preparation's 22 required sources and 48 additional sources were read as recorded
in preparation v1. The implementation v1 ledger records each additional followed
source. Terminal review follows both inventories and the implementation ledger,
campaign selection/ready and Batch 6 handoff, the original Batch 8 audit/refusal,
all Native*.php implementations and the command, the retained TransitionStore,
TransitionAuthority and TransitionReconstructor, AtomicTransition and immutable
source-lock semantics, the selected v3 contract/validator, and native production /
activation joins already listed in the implementation ledger. NativeTransition
Batch1–6 tests and the native worker supply executable evidence; Batch7Test and
inventory v2 are new reviewed artifacts. Source rechecks after the clean merge
specifically covered the first 150 lines of PrincipalActivationDecisionAuthorityProvenanceProductionService,
the entire native worker, and symbol searches for NativeConsumer/Admission/Reader
and all eleven direct descriptor readers. Searches are not claimed as full-file
rereads. The merge tree was verified equal to tested Batch 6.
The resumed audit additionally reads the entire Batch 6A correction test and
handoff, the two-line absent-reader delta and its independent reconstruction
call. Preparation and Batch 1/2 handoffs were rechecked for exact validation
claims: Preparation used focused documentary PHPUnit (12 tests / 296 assertions),
while implementation Batches 1–7 and correction 6A used the full suite.

The Blackquill skill was applied to claims and evidence, not to manufacture a
success verdict from green tests. No private or live runtime record was read.

## Preserved perimeter

BOUND_INACTIVE remains the immutable descriptor. Historical native v3
NOT_IMPLEMENTED remains the inert contract; the exact new result is pre-effect.
UNKNOWN_REPLAY_PROHIBITED remains irreversible uncertainty with no retry grant.
No live Root signing/provisioning, principal/successor creation, transition,
credential/capability handling, provider invocation, external I/O/effect, retry,
Iron Gate or Lazaretto opening occurred. Tests use disposable local state only.

Live rollout is a separate boundary requiring provisioned public trust, exact
signed acts, eligible native sources and explicit legacy inventory. This audit
does not authorize rollout or require manufacturing those objects to close a
code-and-proof campaign. No remote push or publication occurred.
