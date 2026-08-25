# Blackquill Review TODOs

This backlog records the findings from the post-implementation Blackquill review of the 69-step Delegate mission lifecycle. Items are ordered by risk and recommended execution sequence.

## Critical integrity work

### 1. Make credential custody real

- [ ] Remove provider credentials from the directly invokable Symfony platform service boundary.
- [ ] Introduce a credential broker that is the only component capable of resolving provider credentials.
- [ ] Require a valid, unexpired, mission-scoped Clavium lease before credential resolution.
- [ ] Consume the lease atomically when provider invocation is claimed.
- [ ] Ensure logs, records, exceptions, and serialized state never expose credential material.
- [ ] Add tests proving that bypassing Clavium cannot invoke a provider.

The current lease is ceremonial: the platform already possesses the credential and the gateway can invoke it directly. The recorded control does not technically mediate access.

### 2. Make provider invocation recoverable and effectively once-only

- [ ] Persist an `INVOCATION_STARTED` claim before making the external provider call.
- [ ] Couple invocation claim creation and lease consumption with compare-and-swap or an equivalent atomic transition.
- [ ] Generate and persist a stable provider idempotency key for each mission invocation.
- [ ] Pass that key through adapters where the provider supports idempotency.
- [ ] Define recovery rules for claimed-but-unresolved invocations after timeout or process death.
- [ ] Prevent automatic replay when the provider outcome is unknown.
- [ ] Record provider response identity before downstream processing.
- [ ] Add crash tests at every boundary around claim, external call, response persistence, and turn persistence.

The current provider call occurs before durable turn persistence. A crash in between can repeat an external call and its cost.

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
- [ ] Consolidate operational qualification, assembly, and binding mechanics (Steps 44-46) into a transactional workflow with explicit checkpoints. *(Step 20 added the atomic `CodexImperiiStore` foundation; service migration and the transition coordinator remain.)*
- [ ] Consolidate commission construction and readiness mechanics (Steps 51-52).
- [ ] Consolidate result disposition and return-authorization mechanics (Steps 67-68).
- [ ] Keep Oracle catalogue identity separate from runtime model binding.
- [ ] Keep cross-office decisions explicit even when they share infrastructure.

## Shared persistence primitives

- [ ] Extract an `ImmutableRecordStore`.
- [x] Extract a `MutableStateStore`.
- [ ] Extract an `AuthorityConsumptionStore`.
- [x] Extract an `AtomicTransition` coordinator.
- [ ] Extract a `RecordReferenceValidator`.
- [ ] Remove duplicated filesystem scanning, decoding, validation, locking, and write code after migration.

## Provider boundary

- [ ] Replace the arbitrary model-configuration array with provider- and model-specific validated configuration.
- [ ] Allowlist supported configuration keys and value ranges.
- [ ] Reject unknown or unsupported options before invocation.
- [ ] Keep the current registry honest as a strict DeepSeek adapter until a second provider proves the abstraction.
- [ ] Add adapter contract tests before claiming provider neutrality.

## Code quality

- [ ] Format all Delegate lifecycle classes to normal PSR-12-readable source.
- [ ] Expand compressed one-line classes and methods.
- [ ] Add linting/format checks that prevent malformed namespace-qualified construction such as `new\DateTimeImmutable`.
- [ ] Review the approximately 20 Delegate classes currently at ten lines or fewer for compressed logic.
- [ ] Replace repeated magic strings with bounded types or canonical constants where doing so improves validation.

## Test hardening

- [ ] Add concurrency tests for duplicate submissions and authority consumption.
- [ ] Add crash/fault-injection tests for every multi-write transition.
- [ ] Add tamper tests for records, references, hashes, identities, and timestamps.
- [ ] Add expiry-boundary tests using an injected clock.
- [ ] Add fail-stopped tests for missing, stale, superseded, malformed, and mismatched evidence.
- [ ] Cover the many exception paths directly; the suite currently exercises only a small fraction explicitly.
- [ ] Keep one full 69-step end-to-end flow test.
- [ ] Split the giant flow test into smaller office/service contract tests backed by reusable fixtures.

## Documentation reduction

- [ ] Establish one canonical lifecycle document.
- [ ] Establish one authority and consumption matrix.
- [ ] Establish one record-schema catalogue. *(The canonical Folium/Folia/Codex Imperii vocabulary and Codex contract are now defined; the catalogue must use them.)*
- [ ] Establish one terminal-audit specification.
- [ ] Archive historical step handoffs once their durable content is incorporated.
- [ ] Mark generated or historical documents clearly so they cannot compete with current contracts.

## Final evidence gate

- [ ] Run the complete PHPUnit suite locally and in CI.
- [ ] Run concurrency and crash-recovery suites repeatedly.
- [ ] Demonstrate that direct provider invocation without a valid credential lease is impossible.
- [ ] Demonstrate recovery from an unknown provider outcome without duplicate invocation.
- [ ] Demonstrate deterministic recovery from every partial terminal transition.
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
