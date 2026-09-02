# Blackquill Review TODOs

This backlog records the findings from the post-implementation Blackquill review of the 69-step Delegate mission lifecycle. Items are ordered by risk and recommended execution sequence.

## Critical integrity work

### Atomic transition evidence derivation remediation — selected 2026-09-01

- [ ] Replace caller-supplied adversarial proof booleans with typed evidence cases and deterministic expected results.
- [ ] Prevent empty or `ABSENT` evidence from proving replay, contention, committed-state or partial-write findings.
- [ ] Derive every adversarial finding from executed cases and bind it to an evidence digest.
- [ ] Replace constructed false action claims with typed non-authority evidence or narrow the claim explicitly.
- [ ] Add value-aware recursive secret and process-local capability exclusion, including generic containers and encoded values.
- [ ] Replace documentation-retention terminal checks with deterministic evidence-chain recomputation.
- [ ] Retain the prior campaign closure as `CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT` until remediation closes.

Selected campaign: `docs/next-campaign-atomic-transition-evidence-derivation-remediation.md`.
Only Preparation Batch 0 is authorized.

Preparation Batch 0 completed at
`PREPARATION_BATCH_0_COMPLETE_FALSE_PROOF_DERIVATION_BOUNDARIES_CLASSIFIED`.
Only Batch 1 typed case, mutation, expected-result and reusable fixture
contracts with pure validation is next authorized.

Batch 1 completed at
`BATCH_1_TYPED_CASE_MUTATION_EXPECTED_RESULT_AND_IMMUTABLE_FIXTURE_CONTRACTS_COMPLETE`.
Only Batch 2 deterministic pure case execution and finding derivation without
proof booleans is next authorized.

Batch 2 completed at
`BATCH_2_DETERMINISTIC_CASE_EXECUTION_AND_FINDING_DERIVATION_COMPLETE`.
Only Batch 3 evidence-bound read-only aggregate audit receipt, typed
action-capability manifest and value-aware secret-exclusion proof is next
authorized.

Batch 3 completed at
`BATCH_3_EVIDENCE_BOUND_AGGREGATE_AUDIT_COMPLETE`.
Only Batch 4 terminal read-only evidence-chain recomputation against the
complete sealed aggregate is next authorized.

Batch 4 completed at
`BATCH_4_TERMINAL_EVIDENCE_CHAIN_RECOMPUTATION_COMPLETE`.
Only Batch 5 read-only corrected-closure replacement from the sealed successful
terminal recomputation is next authorized.

Atomic Transition Evidence Derivation Remediation completed at
`ATOMIC_TRANSITION_EVIDENCE_DERIVATION_REMEDIATION_COMPLETE` with corrected
closure `CAMPAIGN_CLOSURE_ACCEPTED_AFTER_MATERIAL_EVIDENCE_REMEDIATION`.
There is no Batch 6.

### Atomic transition evidence provenance and operational proof remediation — selected 2026-09-01

- [ ] Requalify the corrected closure after the terminal adversarial rejection.
- [ ] Make caller-constructed cases, results and execution booleans inadmissible to closure.
- [ ] Bind every accepted result to a trusted executor, exact source/build identity and disposable mission root.
- [ ] Derive the actual recursive dependency-capability graph instead of sealing a hard-coded declaration.
- [ ] Scan the complete provenance-to-closure evidence chain for secrets and process-local capabilities.
- [ ] Run one explicitly authorized disposable real mission through the actual internal corridor without provider or external effect.
- [ ] Reconstruct independently without trusting producer conclusions.
- [ ] Repair, disable or subordinate the historical defective audit path before corrected closure.

Selected campaign:
`docs/next-campaign-atomic-transition-evidence-provenance-operational-proof-remediation.md`.
Only Preparation Batch 0 inventory and boundary classification is authorized.

Preparation Batch 0 completed at
`PREPARATION_BATCH_0_COMPLETE_TRUSTED_EXECUTION_PROVENANCE_BOUNDARY_CLASSIFIED`.
Only Batch 1 separately versioned execution-provenance and evidence-origin
contracts with pure validation may next be considered. The closure remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`.

Batch 1 completed at
`BATCH_1_AUTHORITY_EMPTY_EVIDENCE_ORIGIN_AND_EXECUTION_PROVENANCE_CONTRACTS_COMPLETE`.
Only Batch 2 trusted internal case execution that accepts no caller result or
execution, finding or match boolean may next be considered.

Batch 2 completed at
`BATCH_2_TRUSTED_INTERNAL_CASE_EXECUTION_AND_PROVENANCE_BOUND_RESULTS_COMPLETE`.
Only Batch 3 actual recursive dependency-capability graph derivation bound to
the exact executing source/build may next be considered.

Batch 3 completed at
`BATCH_3_ACTUAL_RECURSIVE_EXECUTOR_DEPENDENCY_CAPABILITY_GRAPH_DERIVED`.
The actual resolved Batch 2 executor object graph is recursively derived and
bound to the exact loaded source/build. Unknown, substituted, mutable and
effect-capable dependencies fail closed. Only Batch 4 complete-chain secret and
process-local capability exclusion may next be considered. The closure remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`.

Batch 4 completed at
`BATCH_4_COMPLETE_CHAIN_SECRET_AND_PROCESS_LOCAL_CAPABILITY_EXCLUSION_PROVED`.
Twelve exact typed origin-to-closure sections now undergo recursive key, value,
container, nested/alternative encoding, split-value, process-local identity and
sanitized-exception inspection. Only Batch 5 separately authorized disposable
real-mission execution may next be considered. The closure remains qualified.

Batch 5 completed at
`BATCH_5_INTEGRATED_DISPOSABLE_INTERNAL_MISSION_OPERATIONAL_EVIDENCE_ACCEPTED`.
One source/build-bound internal mission produced the trusted result, actual
executor graph, complete acceptance matrix and digest-bound private receipt;
only its sanitized summary is committed. Only Batch 6 independent
reconstruction and historical-audit disposition may next be considered.

Batch 6 completed at
`BATCH_6_INDEPENDENT_RECONSTRUCTION_COMPLETE_HISTORICAL_CLOSURE_PATHS_DISABLED`.
The retained operational package reconstructs from pinned bindings without
importing its producer disposition. Both historical counterfeit closure paths
are container-excluded and fail closed. Only Batch 7 terminal adversarial audit
and conditional closure may next be considered.

Batch 7 terminal adversarial audit passed at
`BATCH_7_TERMINAL_ADVERSARIAL_AUDIT_PASSED`. Atomic Transition Evidence
Provenance and Operational Proof Remediation is closed at
`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_AUTHENTICATED_OPERATIONAL_EVIDENCE_PROOF`.
The retained operational package survived independent reconstruction and both
historical counterfeit closure paths remain disabled. No further batch in this
campaign is authorized.

Post-closure Blackquill review rejected
`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_AUTHENTICATED_OPERATIONAL_EVIDENCE_PROOF` at
`AUTHENTICATED_OPERATIONAL_EVIDENCE_CLOSURE_REJECTED_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.
The reconstructor matches copied constants rather than underlying artifacts,
the terminal auditor repeats the same implementation, and the private receipt
cannot be independently inspected from its digest. Historical counterfeit
closure paths remain correctly disabled.

Atomic Transition Evidence Independent Verification Remediation is selected.
Only Preparation Batch 0 inventory and boundary classification may next be
considered. No receipt inspection, signing-key handling, verifier execution,
mission rerun, provider invocation, external I/O, runtime mutation or closure
restoration is authorized.

Preparation Batch 0 completed at
`PREPARATION_BATCH_0_COMPLETE_INDEPENDENT_VERIFICATION_BOUNDARY_CLASSIFIED`.
Copied constants, producer/verifier overlap, unverified operator-local receipt
availability, artifact-recomputation gaps, detached trust-anchor custody,
sanitized-report requirements, counterfeit refusals and closure-consumer
reachability are classified. Only Batch 1 authority-empty verifier-input,
sanitized-report, public verification-identity and detached-attestation
contracts may next be considered.

Batch 1 completed at
`BATCH_1_AUTHORITY_EMPTY_VERIFICATION_CONTRACTS_COMPLETE`. Separately
versioned verifier-input, sanitized-report, public-identity and detached-
attestation contracts now fail closed on producer conclusions and premature
signing. Only Batch 2 separate read-only artifact-and-receipt verifier may next
be considered.

Batch 2 completed at
`BATCH_2_SEPARATE_READ_ONLY_VERIFIER_COMPLETE_RETAINED_V1_MATRIX_INDETERMINATE`.
The separate verifier derives artifact and receipt domains but refuses to copy
the retained v1 acceptance conclusions. Batch 3 completed at
`BATCH_3_SYNTHETIC_COUNTERFEIT_AND_DETACHED_ATTESTATION_REFUSAL_PROVED`.

Batch 4 terminated local verification before private intake at
`BATCH_4_LOCAL_VERIFICATION_REFUSED_BEFORE_PRIVATE_INTAKE_ACCEPTANCE_CASE_EVIDENCE_NOT_RETAINED`.
No receipt or live signing capability was handled. Batch 5 completed at
`BATCH_5_ATTESTED_REPORT_CONSUMER_COMPLETE_LEGACY_CLOSURE_DISABLED`; only exact
fully passing, pre-trusted detached reports are admissible and the legacy
terminal closure is disabled.

Batch 6 terminates the campaign at
`CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT`. Closure is
not restored. The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

### 1. Make credential custody real

- [x] Remove provider credentials from the directly invokable Symfony platform service boundary. *(Credential-boundary Batch 17.)*
- [x] Introduce a credential broker that is the only component capable of resolving provider credentials.
- [x] Require a valid, unexpired, mission-scoped Clavium lease or separately typed claim-bound authority before credential resolution.
- [x] Consume the lease and native cognition authority atomically when provider invocation is claimed.
- [x] Ensure logs, records, exceptions, and serialized state never expose credential material.
- [x] Add tests proving that bypassing Clavium cannot invoke a provider.

Closed by the separately bounded Operational Cognition Access and credential-boundary remediation programs. The executable inventory now reports zero direct agents, zero credential-bearing platform definitions, five classified claim-bound provider callers, and `system_wide_gate_closed=true`.

### 2. Make provider invocation recoverable and effectively once-only

- [x] Persist a durable pre-I/O invocation reservation before making the external provider call.
- [x] Couple invocation claim creation and lease consumption with compare-and-swap or an equivalent atomic transition.
- [x] Generate and persist a stable provider idempotency identity for each mission invocation.
- [x] Pass that identity through the DeepSeek adapter.
- [x] Define recovery rules for claimed-but-unresolved invocations after timeout or process death.
- [x] Prevent automatic replay when the provider outcome is unknown.
- [x] Record provider response identity before downstream processing.
- [x] Add crash tests around claim, external call, response persistence, and turn persistence.

Closed by provider-journal hardening, Operational Cognition Access Batch 5, the four-demonstration crash program, and the final credential-boundary proof. Unknown outcomes are terminally non-replayable.

### 3. Replace race-prone filesystem transitions

- [ ] Introduce a shared transactional persistence abstraction.
- [ ] Lock the complete read/validate/transition/write sequence, not only individual writes.
- [ ] Add atomic compare-and-swap semantics for mutable state.
- [ ] Make authority consumption single-winner under concurrency.
- [ ] Guarantee immutable-record uniqueness under simultaneous writers.
- [ ] Add multi-process concurrency tests for every consumable authority and replay boundary.

The current scan/read/validate/write pattern is not transactional. Per-write `LOCK_EX` does not protect the decision that preceded the write.

### 4. Make terminal retirement atomic

- [ ] Treat terminal record creation, custody retirement, and binding replacement as one recoverable transition.
- [ ] Define an explicit intermediate state if true atomicity across stores is unavailable.
- [ ] Add deterministic resume and rollback rules for every partial terminal state.
- [ ] Add fault-injection tests after each terminal write.

The current terminal sequence can tear, leaving records, custody, and bindings in disagreement.

### 5. Correct terminal audit scope and naming — completed in Hardening Step 11

- [x] Decide whether the terminal audit must validate all 69 lifecycle steps or only operational evidence.
- [x] If comprehensive, expand it to verify every required record and cross-reference. *(Not selected; the claim is intentionally bounded.)*
- [x] If intentionally limited, rename it to state its actual 14-record operational scope.
- [x] Document completeness guarantees and exclusions.
- [x] Preserve fail-stopped omission, substitution, tampering, and stale-reference validation; source identity is now checked as well as digest.

## Consistency and replay safety

- [ ] Define one replay/idempotency contract for all lifecycle services.
- [ ] Require replay lookup to validate the complete authoritative input fingerprint, not only a source identifier.
- [ ] Reject conflicting reuse of an existing source identifier.
- [ ] Standardize consumed, expired, missing, stale, and superseded authority behavior.
- [ ] Add contract tests that every lifecycle service must pass.

## Consolidation work

Preserve independent authorities, emitted records, and cross-office handoffs. Consolidate implementation mechanics, not governance boundaries.

- [x] Replace the trust, security, and usability question implementations with jurisdiction-parameterized engines. *(Authorship completed in Step 13; subsequent commission issuance completed in Step 14; subsequent commission disposition completed in Step 15; dispatch authorization completed in Step 16; physical dispatch completed in Step 17; testimony response completed in Step 18.)*

  Closeout Step 19 reserves `S790`–`S795` for the six shared engines' unsupported-jurisdiction fail-stop errors, without changing any established jurisdiction-specific runtime error.
- [x] Consolidate operational qualification, assembly, and binding mechanics (Steps 44-46) into a transactional workflow with explicit checkpoints. *(Step 20 added the atomic `CodexImperiiStore`; Step 21 routes first execution and replay through the recoverable coordinator.)*
- [x] Consolidate commission construction and readiness mechanics (Steps 51-52). *(Step 23 shares only record reads, digest validation, immutable persistence, and error mapping; the two judgments and authorities remain separate.)*
- [x] Consolidate result disposition and return-authorization mechanics (Steps 67-68). *(Step 24 shares only evidence reads, digest/source validation, immutable persistence, and error mapping; result acceptance and return authority remain separate decisions.)*
- [ ] Keep Oracle catalogue identity separate from runtime model binding.
- [ ] Keep cross-office decisions explicit even when they share infrastructure.

## Shared persistence primitives

- [x] Extract an `ImmutableRecordStore`.
- [x] Extract a `MutableStateStore`.
- [x] Extract an `AuthorityConsumptionStore`. *(The canonical primitive exists and is used by
  Delegate mission-turn recovery; system-wide adoption remains fragmented and is selected for a
  separately prepared campaign.)*
- [x] Extract an `AtomicTransition` coordinator.
- [x] Extract a `RecordReferenceValidator`. *(Step 25 establishes the canonical primitive and migrates the consolidated Steps 51–52 and 67–68 substrates first.)*
- [ ] Remove duplicated filesystem scanning, decoding, validation, locking, and write code after migration. *(Canonical validation now covers the critical runtime corridors, and the deployment authorization/custody/activation corridor uses immutable or recoverable shared persistence; remaining legacy services still require bounded migration.)*

## Provider boundary

- [x] Replace the arbitrary model-configuration array with provider- and model-specific validated configuration. *(Step 33 establishes the exact Delegate DeepSeek contract.)*
- [x] Allowlist supported configuration keys and value ranges. *(Only numeric `temperature` from `0.0` through `2.0` is supported for `deepseek-v4-flash`.)*
- [x] Reject unknown or unsupported options before invocation. *(Both the gateway and brokered invoker fail with `CT312` before credential resolution or provider I/O.)*
- [x] Keep the current registry honest as a strict DeepSeek adapter until a second provider proves the abstraction. *(Step 34 removes the misleading provider-neutral platform interface and binds the registry to an explicit DeepSeek contract.)*
- [x] Add adapter contract tests before claiming provider neutrality. *(Step 34 proves the exact DeepSeek provider, service, model, credential reference, operation, implementation, and configuration identity; provider neutrality remains deliberately unclaimed.)*

## Code quality

- [ ] Format all Delegate lifecycle classes to normal PSR-12-readable source. *(Cleanup Batches 1–3 expand the critical construction/deployment corridors and the complete compressed model-bound Senate chain; remaining runtime-wide audit and minor legacy formatting remain.)*
- [ ] Expand compressed one-line classes and methods. *(Cleanup Batches 1–3 expand twenty-one heavily compressed runtime services into bounded readable source.)*
- [ ] Add linting/format checks that prevent malformed namespace-qualified construction such as `new\DateTimeImmutable`.
- [x] Review the approximately 20 Delegate classes currently at ten lines or fewer for compressed logic. *(Cleanup Batches 1–3 expand twenty-one severe candidates across construction, deployment, and the model-bound Senate chain.)*
- [ ] Replace repeated magic strings with bounded types or canonical constants where doing so improves validation.

## Codex runtime-source audit — 2026-08-25

- [x] Inventory every PHP source under `src/Imperium/Runtime`. *(376 files audited at merged commit `7bafa498a18f10d122771c65b13b7250c8be9f51`.)*
- [x] Separate confirmed token damage from readability debt. *(The cleanup regression that split `<=>` was repaired in PR #329; the audit found no second literal `<= >` occurrence.)*
- [x] Expand the 18 severely compressed runtime files identified in `docs/handoffs/runtime-source-audit-codex.md`. *(Cleanup Batch A expands thirteen Delegate control-plane targets; Cleanup Batch B expands the final five Authorship, Foundry, and Senate targets.)*
- [x] Require PHP lint before PHPUnit for every source-formatting batch; a green behavioral suite is not a substitute for parsing every changed file. *(Cleanup Batches A and B passed explicit local PHP lint before the complete PHPUnit suite.)*
- [x] Treat the 146 files over 240 characters as measured secondary debt, not as a crash-demonstration gate and not as proof of PSR-12 noncompliance.
- [x] Keep the 240-character check explicitly described as an Imperium readability guard; PSR-12 does not establish that hard limit.
- [x] Widen the formatting regression guard only as audited clusters become compliant; do not conceal legacy debt behind an allowlist. *(Cleanup Batches A and B guard all eighteen severe audit targets; the broader runtime is not falsely declared compliant.)*
- [x] Complete a final runtime-wide rescan of the severe-compression set before marking the cleanup gate complete; schedule broader style normalization separately. *(All 376 runtime PHP files rescanned at `15227cf8cf6ca467c7cf71f64182073ea1a7ba7a`; zero severe-compression files remain.)*

## Test hardening

- [ ] Add concurrency tests for duplicate submissions and authority consumption. *(Provider journal start and the Steps 44–46 operational Folium boundary now have multi-process single-winner proofs; remaining consumable authorities still require coverage.)*
- [ ] Add crash/fault-injection tests for every multi-write transition. *(Steps 44–46, terminal retirement, and deployment custody now have forward-recovery checkpoint coverage; remaining multi-write transitions still require proof.)*
- [ ] Add tamper tests for records, references, hashes, identities, and timestamps. *(Canonical validation now protects the Steps 51–52, Steps 67–68, bounded-turn, and terminal-return reference boundaries; remaining runtime services still require migration and direct tamper coverage.)*
- [ ] Add expiry-boundary tests using an injected clock.
- [ ] Add fail-stopped tests for missing, stale, superseded, malformed, and mismatched evidence.
- [ ] Cover the many exception paths directly; the suite currently exercises only a small fraction explicitly.
- [ ] Keep one full 69-step end-to-end flow test.
- [ ] Split the giant flow test into smaller office/service contract tests backed by reusable fixtures.

## Documentation reduction

- [x] Establish one canonical lifecycle document. *(`docs/delegate-mission-flow.md`, reconciled in Step 35.)*
- [x] Establish one authority and consumption matrix. *(`docs/delegate-mission-authority-consumption-matrix.md`.)*
- [x] Establish one record-schema catalogue. *(`docs/delegate-mission-record-schema-catalogue.md` uses the canonical Folium/Folia/Codex Imperii vocabulary.)*
- [x] Establish one terminal-audit specification. *(`docs/delegate-mission-terminal-operational-evidence-audit.md` states the exact fourteen-record claim and exclusions.)*
- [x] Archive historical step handoffs once their durable content is incorporated. *(Retained in place for provenance and classified as historical by `docs/handoffs/README.md`.)*
- [x] Mark generated or historical documents clearly so they cannot compete with current contracts. *(`docs/handoffs/README.md` defines precedence and canonical reading order.)*

## Final evidence gate

- [ ] Run the complete PHPUnit suite locally and in CI.
- [ ] Run concurrency and crash-recovery suites repeatedly.
- [x] Demonstrate that direct provider invocation without a valid claim-bound credential authority is impossible. *(Credential-boundary Batch 17 removes every direct agent and credential-bearing platform, classifies all five brokered provider callers, and installs an executable repository-wide bypass scan.)*
- [x] Demonstrate recovery from an unknown provider outcome without duplicate invocation. *(Crash Demonstration 3 has operator-retained proof against source commit `bd3620ccd32e1511c96d53caacb60806348cf995`: the sealed-response recovery completed with `provider_reinvoked=false`.)*
- [x] Demonstrate deterministic recovery from every partial terminal transition. *(Crash Demonstration 4 has operator-retained proof against source commit `598cbcdf749fc804b979a2ddfb310bf025b385b2`: all five checkpoints recovered, with one winner under divergent contention and zero surviving authority.)*
- [ ] Capture and retain evidence for the terminal audit's stated scope.

## Recommended execution order

1. Credential broker and durable invocation claim.
2. Transactional persistence primitives.
3. Crash and concurrency tests.
4. Standardized replay and idempotency semantics.
5. Terminal audit correction.
6. Senate question-engine consolidation.
7. Same-actor mechanical workflow consolidation.
8. Formatting and persistence deduplication.
9. Documentation consolidation and archival.
10. Live operational evidence gathering.

## Exit criterion

The work is complete when the credential boundary, provider invocation, authority consumption, state transitions, and terminal retirement are enforced by the runtime under concurrency and failure—not merely described by records—and the test suite proves those properties.


Post-campaign Blackquill review records `TERMINAL_REFUSAL_SUBSTANTIVELY_VALID_WITH_MATERIAL_TEST_PERIMETER_REGRESSION`. The evidence campaign's terminal refusal remains authoritative, but PR #728 weakened exact candidate/snapshot coverage, store-user equality and complete perimeter scanning while bundling unrelated runtime changes.

Frozen Runtime Coverage Tripwire Restoration Preparation Batch 0 is complete at `PREPARATION_BATCH_0_COMPLETE_FROZEN_RUNTIME_TRIPWIRE_REGRESSION_CLASSIFIED`. Only Batch 1 mechanical tripwire restoration may next be considered. The evidence posture remains `CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

Frozen Runtime Coverage Tripwire Restoration is complete at
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_COMPLETE`. Batch 1 restored exact
versioned path coverage, Batch 2 individually adjudicated the PR #728 runtime
and vocabulary changes, and Batch 3 proved the four unapproved synthetic
addition classes fail closed while explicit candidate classification passes.
The independent-verification evidence posture remains qualified and unchanged.


## Activation Disposition Vocabulary Tripwire Correction — selected

- [x] Preparation Batch 0: inventory detector semantics, lexical forms,
  false-positive surfaces, dynamic-construction limits and mutation gaps.
- [x] Batch 1: replace quote-sensitive discovery with deterministic
  quote-independent exact-value detection and adversarial mutation proof.
- [x] Batch 2: perform a separately sequenced terminal Blackquill audit after
  Batch 1 is merged.
- [x] Supersede the rejected prior closure only if Batch 2 passes. *(Accepted for the literal-value boundary only; historical rejection retained.)*

The current disposition-vocabulary detector accepts a double-quoted governed
value without inventory admission. The prior tripwire-restoration closure is
therefore requalified as
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CLOSURE_REJECTED_WITH_MATERIAL_VOCABULARY_TRIPWIRE_GAP`.
The broader independent-verification evidence qualification remains in force.


Local campaign entrypoint:
`docs/handoffs/activation-disposition-vocabulary-tripwire-correction-preparation-batch-0-local-ready.md`.
The operator begins from clean synchronized `main` and performs Preparation
Batch 0 inventory only.

Preparation Batch 0 is now complete at
`PREPARATION_BATCH_0_COMPLETE_VOCABULARY_DETECTOR_SEMANTICS_CLASSIFIED`.
The completed inventory and actual-detector characterization are recorded in
`docs/activation-disposition-vocabulary-tripwire-correction-preparation-inventory.md`.
Only Batch 1 mechanical correction and adversarial proof are next authorized;
Batch 2 remains separately sequenced after Batch 1 merge. The active handoff is
`docs/handoffs/activation-disposition-vocabulary-tripwire-correction-preparation-batch-0-complete.md`.
The prior closure remains rejected and the controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

Batch 1 is complete at
`BATCH_1_COMPLETE_QUOTE_INDEPENDENT_VOCABULARY_TRIPWIRE_PROVED`.
The detector contract is
`docs/activation-disposition-vocabulary-tripwire-correction-detector-contract-v1.md`.
Only the separately sequenced Batch 2 audit may follow its merge into main.
Active handoff:
`docs/handoffs/activation-disposition-vocabulary-tripwire-correction-batch-1-complete.md`.

The campaign is complete at
`ACTIVATION_DISPOSITION_VOCABULARY_TRIPWIRE_CORRECTION_COMPLETE`.
Batch 2 started after Batch 1 merged locally into main and passed at
`BATCH_2_TERMINAL_BLACKQUILL_AUDIT_PASSED_LITERAL_VOCABULARY_CLAIM`.
The corrected closure is
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_CORRECTED_CLOSURE_ACCEPTED_LITERAL_VOCABULARY_ONLY`.
Dynamic construction, indirection, concatenation and semantic-role limits remain
explicit; no universal producer claim is reinstated. No campaign batch remains.
Active handoff:
`docs/handoffs/activation-disposition-vocabulary-tripwire-correction-campaign-complete.md`.
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling.

Atomic Transition Independently Verifiable Reproof is selected as a distinct
v2 proof, not a rehabilitation of the v1 package. Only Preparation Batch 0
inventory is authorized. Later mission execution, independent
verification/signing, repository admission and terminal Blackquill audit remain
separate authority boundaries.
