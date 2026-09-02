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
Validation result recorded after the batch run.

## Additional reading ledger

- `src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionService.php` (production method and reconstruction entry).
- `src/Bootstrap/CanonicalJson.php`.
- `tests/Imperium/Runtime/TransactionalAuthorityConsumptionBatch12CoverageTest.php` (coverage and perimeter gates).
- `tests/Imperium/Runtime/FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest.php` (adversarial coverage gates).
- `docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv`.
- `config/services.yaml` (discovery boundary).
- `C:/Users/gatom/.codex/skills/blackquill/SKILL.md` (reserved for terminal audit).

New campaign source, tests and batch documents are read as they are implemented.

Batch 1 validation: 8 tests, 1701 assertions passed including exact runtime coverage. An initial inventory mismatch was corrected by declaring the explicit authority field contract; no coverage assertion was relaxed.

Batch 2: canonical existing-directory store, domain lock, exact immutable replay, full write/flush/fsync and fail-stopped pending files. Single aggregate publication avoids nested lock cycles. 10 tests, 1708 assertions passed; no physical power-loss claim.

Batch 3: pinned-grant issuer and seven-part atomic consumer implemented. Duplicate execution refuses with EAT_ALREADY_COMMITTED_READ_ONLY_REPLAY; receipt reconstruction owns replay. Original binding remains immutable. 12 tests, 1723 assertions passed. The new protocol is opt-in and no native lifecycle migration or live grant is claimed.
