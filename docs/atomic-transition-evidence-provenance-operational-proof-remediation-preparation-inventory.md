# Atomic Transition Evidence Provenance and Operational Proof Remediation Preparation Batch 0

## Result

`PREPARATION_BATCH_0_COMPLETE_TRUSTED_EXECUTION_PROVENANCE_BOUNDARY_CLASSIFIED`

Preparation requalifies the prior corrected closure at
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`. It
inventories only. It executes no case or mission, creates no operational proof,
changes no runtime source and repairs or disables no historical audit path.

## Counterfeit-evidence acceptance surfaces

| Acceptance surface | Exact current behavior | Counterfeit route | Classification |
| --- | --- | --- | --- |
| Case input | `AtomicTransitionEvidenceAggregateAuditBuilder::build()` accepts caller-supplied sealed arrays. | A caller chooses every case field, identifier, root and reference and computes the unkeyed SHA-256 seal. | `EXISTS_COUNTERFEITABLE` |
| Result input | The same builder accepts caller-supplied sealed result arrays. | A caller sets `expectation_matched`, `case_executed` and `finding_derived` to `true`, supplies false action flags and seals the result without invoking the deterministic executor. | `EXISTS_COUNTERFEITABLE` |
| Reference integrity | Aggregate validation checks result-to-case references but does not resolve plan, fixture, mutation or expected-result references to authoritative records. | The Batch 5 focused test uses repeated-character 64-byte stub digests and still obtains corrected closure. | `EXISTS_COUNTERFEITABLE` |
| Producer identity | Cases, results, manifest, proof, aggregate, terminal recomputation and closure carry no authenticated producer. | Record digests establish post-construction stability only; any caller can reproduce them. | `ABSENT` |
| Execution identity | No accepted record binds an executor invocation, executor run identifier or execution receipt. | The builder, recomputer and closure service never call `AtomicTransitionEvidenceDeterministicCaseExecutor`. | `ABSENT` |
| Source and build identity | The accepted chain contains no source commit, tree, build artifact, dependency lock or executable digest. | Identical caller claims can be resealed against an unknown or substituted build. | `ABSENT` |
| Mission root | `replay_contention_root` joins caller records but is not bound to an actually opened disposable mission or immutable mission dossier. | A caller-selected string is treated as the root without proof that a mission existed or ran. | `EXISTS_COUNTERFEITABLE` |
| Terminal recomputation | The recomputer reconstructs the manifest and proof with the same constructors and compares them with caller inputs. | Self-reproduction proves internal consistency, not independent execution provenance. | `EXISTS_FRAGMENTED` |
| Corrected closure | The closure service reruns that same recomputation and then emits `qualification_removed=true` and `campaign_closed=true`. | Internally consistent counterfeit inputs can reach an accepted closure. | `EXISTS_COUNTERFEITABLE` |
| Focused acceptance test | `AtomicTransitionEvidenceDerivationRemediationBatch5Test::chain()` and `derivedCaseResult()` manually construct and seal the full successful chain. | The test demonstrates constructibility; it does not prove trusted execution. | `EXISTS_COUNTERFEITABLE` |

No caller-supplied result, execution boolean, finding boolean, match boolean,
producer claim or record digest is admissible as operational proof merely
because it is well formed or self-consistent.

## Execution-provenance inventory

| Boundary | Classification | Required correction |
| --- | --- | --- |
| Trusted executor entry point | `ABSENT` | One internal entry point must own validation, case execution and result production; it must accept no caller result. |
| Evidence-origin record | `ABSENT` | A separately versioned immutable origin must bind the exact mission, experiment, source, build, executor, fixtures, recovery plan, case-set and sanitized package. |
| Producer authentication | `ABSENT` | Accepted results must be emitted inside the trusted executor boundary and reference its origin and execution receipt; a class-name assertion is insufficient. |
| Exact source and build binding | `ABSENT` | Bind commit and tree identity, dependency-lock digest, build artifact digest and runtime version before execution. |
| Disposable mission identity | `ABSENT` | Bind one explicitly authorized disposable mission and unique root; arbitrary roots and reused mission identities must refuse. |
| Fixture and plan custody | `EXISTS_FRAGMENTED` | Typed references exist, but accepted results do not prove authoritative resolution or custody of the complete referenced records. |
| Case-set derivation | `EXISTS_FRAGMENTED` | Eight required kinds and ordered digests exist, but the caller still supplies the case set and results. |
| Execution receipt | `ABSENT` | A trusted, immutable receipt must bind origin, start and completion, ordered cases, produced results, derived dependency graph and complete-chain exclusion proof. |
| Freshness and replay | `ABSENT` | No nonce, authorization window, execution time, prior-root exclusion or exact replay disposition is authenticated. |
| Independent reconstruction | `EXISTS_FRAGMENTED` | Seals and references recompute, but reconstruction trusts producer-shaped inputs and the producer's constructors. |
| Operational acceptance consumer | `ABSENT` | No closure consumer requires authenticated origin plus trusted execution receipt before accepting results. |

## Smallest trusted proof boundary

The smallest trusted boundary begins before fixture resolution and ends only
after immutable result and execution-receipt production:

1. accept one explicit disposable-mission authorization and immutable evidence
   origin; accept cases or case requirements, but never caller results;
2. attest the exact source tree, build artifact, dependency lock, runtime and
   trusted executor implementation before resolving fixtures;
3. resolve the complete plan, fixture, mutation and expected-result records
   from the origin-bound custody root;
4. derive the required case set, execute it inside the trusted executor and
   derive every observation, finding and match result there;
5. derive the actual recursive dependency-capability graph from the executing
   build, not from a declared evaluator list;
6. inspect the complete origin-through-receipt chain for prohibited secret and
   process-local capability material; and
7. seal one immutable execution receipt consumed by an independent
   reconstructor that imports no producer conclusion.

Trust is intentionally narrow: the executor boundary may attest its own exact
build and produce evidence, but it may not decide closure. The independent
reconstructor and final closure consumer must reject absent, substituted,
stale, incomplete or unauthenticated origin and execution evidence.

## Evidence-origin schema

The minimum acyclic `atomic-transition-evidence-origin/v1` record must contain:

| Field group | Required binding |
| --- | --- |
| Identity | `evidence_origin_id`, `experiment_id`, `disposable_mission_id`, `replay_contention_root`, schema and generation. |
| Authorization | Exact disposable-mission authorization reference, authorized case profile, issue/expiry times and single-use posture. |
| Source | Repository identity, source commit, source tree digest and dirty-tree refusal result. |
| Build | Build identifier, build artifact digest, dependency-lock digest, PHP/runtime version and canonical build command identity. |
| Executor | Executor principal/version, executor implementation digest, entry-point identity and execution environment class. |
| Inputs | Mission dossier reference, fixture-set root, recovery-plan reference, mutation-set root, expected-result-set root and derived case-set root. |
| Custody | Authoritative evidence root, fixture custodian, origin producer and immutable write boundary. |
| Freshness | Issued, not-before, expiry and execution-start window plus prior-origin/replay disposition. |
| Limitations | No provider, credential, external effect or live adoption; one authoritative filesystem root only unless later proved otherwise. |
| Package | Sanitized evidence-package identifier and digest, retention class and explicit raw-private-evidence exclusion. |
| Integrity | Ordered predecessor references, `sealed=true` and record digest. The digest is integrity, not producer authentication. |

No credential value, credential reference in clear text, process-local
capability, callback or object identity, environment value, private fixture or
raw mission material may appear in this origin.

## Capability-manifest derivation gaps

| Gap | Current evidence | Classification |
| --- | --- | --- |
| Recursive dependency traversal | `AtomicTransitionEvidenceAggregateAuditBuilder::EVALUATORS` is a four-class constant. | `ABSENT` |
| Container-resolved dependency graph | Constructor and service-container reachability are not traversed. | `ABSENT` |
| Build binding | `dependency_closure_digest` hashes only the declared class-name list. | `ABSENT` |
| Transitive capability classification | Filesystem, process, network, environment, clock, randomness, persistence and provider-capable dependencies are not recursively classified. | `ABSENT` |
| Unknown/substituted dependency refusal | The exact four strings are compared, but executable code or service substitution is not detected. | `EXISTS_FRAGMENTED` |
| Mutable runtime dependency refusal | No proof detects runtime decoration, replacement, mutable registries or process-local injected dependencies. | `ABSENT` |
| Prohibited capability flags | All capabilities are assigned `false` by construction rather than derived from dependencies. | `EXISTS_COUNTERFEITABLE` |

The future manifest must be derived from the actual resolved executor graph for
the exact build and must fail closed on unknown, substituted, mutable or
effect-capable dependencies.

## Complete-chain secret and capability exclusion gaps

The current proof scans only supplied result records. It does not scan the
evidence origin, mission authorization, dossier, provenance, source/build
attestation, fixtures, recovery plan, mutations, expected results, cases,
dependency graph, aggregate, exceptions, terminal recomputation or closure.

Its fixed four-vector vocabulary and one strict Base64 decoding layer remain a
useful bounded detector, but they do not establish typed structural allowlists,
normalization, multiple or alternative encodings, split values, exception and
trace sanitation, archive/package contents, or complete-chain secret
exclusion. This boundary is `EXISTS_FRAGMENTED` and may not be described as a
complete proof.

## Historical audit-service reachability

| Historical surface | Reachability | Classification |
| --- | --- | --- |
| `ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService` | Auto-discovered under `App\` by `config/services.yaml`; container services are private by default, but the class remains internally resolvable when referenced and publicly constructible in PHP. | `REACHABLE_INTERNAL_UNSUBORDINATED` |
| Production caller | Repository-wide reference search finds only the focused Batch 6 test and the class itself. | `NO_PRODUCTION_CONSUMER_PROVED` |
| Disablement or deprecation | No service exclusion, alias replacement, disable marker or runtime refusal prevents later internal reuse. | `ABSENT` |
| Repair | The service still accepts eight caller booleans and may pass empty evidence. | `NOT_REPAIRED` |
| Subordination | No closure consumer proves the historical result is rejected unless authenticated operational evidence also passes. | `ABSENT` |
| New corrected-closure services | The builder, recomputer and closure service are also auto-discovered, have no production consumer, and do not make the historical service unreachable. | `EXISTS_FRAGMENTED` |

Preparation does not repair, remove, exclude or subordinate the historical
service. Its reachability remains an explicit Batch 6 disposition gate.

## Disposable real-mission acceptance matrix

This matrix designs later acceptance; it authorizes no mission now.

| Case | Required later evidence | Acceptance condition |
| --- | --- | --- |
| Exact bounded run | One explicitly authorized disposable mission through the actual internal transition corridor. | Trusted receipt binds the exact origin, build, executor, case set and terminal state; no provider or external effect boundary is reachable. |
| Interruption cuts | One pre-commit and every declared partial transition cut under the same origin. | No pre-commit proof is fabricated; recovery converges only according to the sealed plan. |
| Exact replay | A second attempt using the exact origin and authoritative inputs. | It converges on the same immutable receipt without re-executing a completed effect or creating a second winner. |
| Changed evidence | Same root with any origin, case, fixture, plan, build or result-affecting change. | Refused before acceptance as changed evidence. |
| Same-root contention | Competing executor attempts under one root. | Exactly one execution/receipt winner; the loser cannot emit accepted results. |
| Partial write | Every missing, reordered or extra predecessor and receipt cut. | Refused or recovered without accepting an incomplete chain. |
| Tamper | Altered origin, dependency graph, case, result, receipt, reference, timestamp or package. | Independent reconstruction refuses the chain. |
| Secret injection | Prohibited material placed in every chain kind, generic container, encoded form, exception and package boundary. | Refused before accepted receipt or sanitized package emission. |
| Executor substitution | Changed executable, dependency, container binding, decorator or runtime version under the claimed build. | Refused by exact build and derived dependency-graph mismatch. |
| Provider/effect reachability | Attempt to introduce credential, provider, network, process or external-effect capability. | Refused; the disposable mission remains internal and effect-free. |

Passing tests against hand-assembled arrays is insufficient. Later Batch 5
acceptance requires retained machine-readable evidence from the actual internal
corridor, plus an independently reconstructed sanitized evidence package.

## Smallest lawful remediation sequence

1. define the evidence-origin and trusted execution-provenance contracts;
2. implement the trusted executor corridor that accepts no caller result;
3. derive and bind the actual dependency-capability graph;
4. enforce complete-chain typed secret and process-local capability exclusion;
5. run the separately authorized disposable real mission and acceptance matrix;
6. reconstruct independently and repair, disable or subordinate the historical
   audit path; and
7. permit terminal closure only after authenticated operational evidence passes
   adversarial review.

## Closed perimeter

Preparation creates no evidence origin, proof artifact, mission, execution or
authority. It invokes no provider, performs no external I/O, handles no live
credential or capability, mutates no runtime state, repairs no audit and does
not remove the closure qualification.

The provider binding remains `BOUND_INACTIVE`. Required v3 execution admission
remains `NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.
