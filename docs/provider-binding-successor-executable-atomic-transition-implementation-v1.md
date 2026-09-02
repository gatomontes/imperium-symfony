# Executable Atomic Transition implementation v1

## Authorization and scope

The operator instructed: "Complete the campaign, uninterrupted if possible.
Perform phpUnit tests after each batch, correcting/fixing if necessary."
This authorizes sequential implementation and local disposable proof, including
correction batches. Preparation-era next-batch restrictions remain historical.
No provider, credential, external effect or live installation activation is part
of this work. The terminal audit remains a separate stage after Batch 7 is merged
locally to clean main. No remote publication is inferred.

## Batch 1 design

The new `ProviderTransition` protocol does not mutate inert predecessor schemas.
One operation root is the canonical hash of instance, binding identity and exact
operation digest. It deliberately excludes successor, decision and authority so
substituting those values cannot obtain a second winner. An immutable aggregate
will contain all seven records; its single publication is the commit point.
The old binding descriptor remains `BOUND_INACTIVE`; a committed operation-scoped
successor projection changes the effective binding only for that operation.

Trust boundary: an operator-pinned, exact narrow scope grant identifies the
competent principal generation, activation, original binding, completed successor
and creation lineage, assurance and execution boundary. The grant pin is trusted
deployment configuration, never supplied by an execute request. Grant provisioning
is an explicit Operator Root act; ordinary callers cannot authorize themselves by
sealing arrays. No live grant is provisioned in this campaign. The implementation
will refuse missing or changed grants and never turn fixture stores into production
sources. Test grants are synthetic and prove code behavior, not live competence.

This new root-grant route is an explicit integration boundary. It does not claim
to reconstruct the existing Imperator lifecycle from hashes or to migrate an
existing principal automatically. Before live deployment, the operator must verify
and pin actual source lineage. Issuance and consumption will bind that exact grant;
revocation and consumption use the same lock. No secret or process-local capability
is stored. Hashes and integer times are the only variable grant values.

Implementation services are excluded from automatic discovery until deployment
provides the trusted pin and dedicated storage. This is not a production activation.

## Batch ledger

Batch 1: new authority-empty scope/admission/consumption/write-set boundary.
Results and qualifications follow below.

## Additional reading ledger

- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionService.php` (production method and reconstruction entry).
- `src/Bootstrap/CanonicalJson.php`.
- `tests/Imperium/Runtime/TransactionalAuthorityConsumptionBatch12CoverageTest.php` (coverage and perimeter gates).
- `tests/Imperium/Runtime/FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest.php` (adversarial coverage gates).
- `docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv`.
- `config/services.yaml` (discovery boundary).
- `src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationContract.php` (native activation shape during integration review).
- `src/Imperium/Runtime/LaCortine/ProviderBindingActivationReconciliationContractValidator.php` (target, decision and successor validation entry points through line 160).
- `src/Imperium/Runtime/Imperator/ImperatorRuntimePrincipalVersionV3Contract.php` (six native scope fields; no exact transition scope).
- `C:/Users/gatom/.codex/skills/blackquill/SKILL.md` (reserved for terminal audit).

New campaign source, tests and batch documents are read as they are implemented.

Batch 1 validation: 8 tests, 1701 assertions passed including exact runtime coverage. An initial inventory mismatch was corrected by declaring the explicit authority field contract; no coverage assertion was relaxed.

Batch 2: canonical existing-directory store, domain lock, exact immutable replay, full write/flush/fsync and fail-stopped pending files. Single aggregate publication avoids nested lock cycles. 10 tests, 1708 assertions passed; no physical power-loss claim.

Batch 3: pinned-grant issuer and seven-part atomic consumer implemented. Duplicate execution refuses with EAT_ALREADY_COMMITTED_READ_ONLY_REPLAY; receipt reconstruction owns replay. Original binding remains immutable. 12 tests, 1723 assertions passed. The new protocol is opt-in and no native lifecycle migration or live grant is claimed.

Batch 4: two actual PHP processes, shared start gate and canonical-path alias contention produced exactly one commit and one losing-path refusal. 13 tests, 1733 assertions passed. This proves cooperative process exclusion on this host, not hostile-writer or distributed storage safety.

Batch 5: nine real child-process termination cuts around authority, journal and aggregate publication; expiry and revocation refusal; restart prohibits execution from incomplete commit state. 24 tests, 1808 assertions passed. Short/truncated pending-file refusal is separately synthetic. Checkpoint closure is trusted fault-harness configuration only. No power-loss or arbitrary-kill timing claim.

Batch 6: separate read-only reconstructor checks seven record joins, root, grant, authority, journal and receipt without calling the consumer. Five outcome states preserve no-repair/no-retry. 29 tests, 1830 assertions passed with before/after file hashes unchanged.

Batch 7 adversarial implementation corrections: clock values are no longer
execute-request arguments; time is sampled under the lock and again after journal
publication. Issuance refuses pending outcomes. The grant now binds canonical
physical storage identity, preventing a copied grant from being used at another
configured root. Malformed journal shape cannot be described as valid incomplete
evidence. Resealed substitutions in all seven committed records refuse.
Focused result: 40 tests, 1849 assertions, including unchanged exact coverage.

### Material canonical-integration finding

`EXECUTABLE_ATOMIC_TRANSITION_NATIVE_PROVENANCE_INTEGRATION_NOT_PROVED`.

The new pinned-grant protocol is executable and its local mechanics are tested.
It does **not** implement the full canonical campaign claim. The principal,
activation, source binding, successor creation, assurance and execution-boundary
hashes are operator-attested input values; their native source records are not
loaded or independently validated. The tests demonstrate this directly: their
grants commit successfully without native principal/successor files. Pin integrity
does not prove that those native objects exist or are currently eligible.

The new admission schema is `imperium.provider-successor-executable-admission/v3`.
It is not an implementation of the existing
`imperium.la-cortine.governed-provider-execution-admission/v3` contract, whose
`NOT_IMPLEMENTED` state remains unchanged. The aggregate's operation-scoped
binding projection has no migrated native consumer. These are integration gaps,
not evidence that the required original admission or binding was activated.

Consequently Batches 3–6 establish the new protocol's bounded mechanics only.
A terminal audit must refuse an unqualified canonical-production completion.
The directory remains service-excluded and no live grant is provisioned. Closing
the gap requires native source resolution and lifecycle validation, an eligible
production successor route, a canonical v3 admission producer/consumer and the
native binding-state reader. The operator's broad continuation permits fixes;
the choice between those native routes and an explicitly accepted new provisioning
boundary has been surfaced rather than silently changing the campaign objective.

The first full-suite attempt overlapped source corrections and therefore used
mixed loaded/source versions. Its 25 errors and four failures are not accepted
as a stable-tree result. A fresh full-suite run was started after the corrections.

Stable-tree full-suite result after Batch 7: **1836 tests, 43877 assertions passed**
on PHP 8.4.14 / PHPUnit 13.3.0. All new PHP files pass lint. No test gate was
weakened. This validates the bounded implementation, not native source eligibility.
