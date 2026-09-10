# O2-B1 implementation surfaces and required gates

Preparation only; no listed new runtime class exists as a result of this pass. Paths below are the bounded proposed implementation inventory. Read the [contract](../contracts/provider-onboarding-ledger-custody.md) before coding. Existing runtime inventory/frozen snapshots stay unchanged in preparation; actual new consuming candidates require truthful additive entries during implementation.

| Surface | Intended change and invariants |
| --- | --- |
| src/Imperium/Runtime/Onboarding/AuthorityAdmission/AuthorityStore.php | Strict v1/v2 decoding; preserve every retained admission field and new B1 map; no read-time migration |
| src/Imperium/Runtime/Onboarding/AuthorityAdmission/Resolver.php | Extract current verification mechanically; preserve public static-only semantics and R1-R3 refusals |
| src/Imperium/Runtime/Onboarding/AuthorityAdmission/CurrentAuthority.php (new) | Internal no-lock verifier reused by public Resolver and B1 atomic coordinator; no ingress state/verified switch |
| src/Imperium/Runtime/Onboarding/AuthorityAdmission/Admission.php, Act.php, Enrollment.php | Compatibility inspection and narrow v2 preservation changes only if needed; first provenance, historical replay, public trust and exact slot expiry unchanged |
| src/Imperium/Runtime/Onboarding/Ledger/StateMigration.php, CommandLedger.php, CompletionResolver.php, StepReadiness.php (new) | Explicit migration, F1 identity/recognition and finite F2/retry readiness; no CLI or later effect adapters |
| src/Imperium/Runtime/Onboarding/Ledger/ClaimCoordinator.php, CustodyCoordinator.php, Recovery.php (new) | Atomic original consumption and maximum reservation; monotone custody checkpoints; immutable response lineage and evidence-only resume |
| src/Imperium/Runtime/Onboarding/Ledger/PreparedOperation.php, SourceAuthority.php, CredentialCustody.php, ResponseCustody.php (new) | Versioned fixed interfaces/default refusals; offline doubles only, no O3 implementation or fake genuine holder |
| src/Imperium/Runtime/Citadel/Formation/SharedExposure.php (new) | Pure entire-state typed exposure calculator and restrictive source association; no independent journal/balance |
| src/Imperium/Runtime/Citadel/Formation/FormationCognition.php | Invoke shared gate in actual call reservation; settlement must remain visible to shared calculation; no bypass from new session/phase |
| src/Imperium/Runtime/Clavium/FormationClaimCustodyBroker.php | Shared recheck at all actual custody stages, preserving its existing source/lease/operation validation and recovery |
| src/Imperium/Runtime/Citadel/Formation/SessionExposure.php | Preserve existing validators/positive FC maxima and settlement; bootstrap zero-fee access has its own typed validator |
| src/Imperium/Runtime/Citadel/Formation/FormationJournal.php | Reuse changeAtHead/inspect unchanged; frame schema, fixed root, lock and publish point remain |
| tests/Imperium/Runtime/ProviderOnboardingLedgerTest.php, ProviderOnboardingSharedExposureTest.php, ProviderOnboardingCustodyTest.php, ProviderOnboardingLedgerProcessTest.php (new) | Real producer and negative/concurrent/recovery proof in temporary roots; bounded process timeouts; no authority-map insertion |
| tests/Imperium/Runtime/Support/OnboardingLedgerFixture.php, onboarding-ledger-worker.php (new) | Synthetic public originals and fixed counting/fault ports; host-independent barriers, no network |
| docs/citadel-mission-formation-runtime-inventory-v1.tsv | Add actual new/changed runtime coverage classifications when code exists; never regenerate historical baseline/counts |

FormationPreparedOperation, FormationSessionAuthority, FormationSessionLeaseService, ProviderResponseEnvelopeService, CredentialBroker and BoundedFormationTransport are compatibility reference surfaces. They are not already valid onboarding adapters. A necessary edit outside this inventory must be recorded with its concrete invariant and regression impact before implementation; never broaden operational wiring implicitly. services.yaml, config/reference.php, dependency locks, O1 runtime, original policy/workload and historical reviewer tests are protected.

## Required matrix

| Gate | Required positive and adversarial evidence |
| --- | --- |
| Migration | Exact v1 to v2 one-frame transition; replay no write; absent trust/unknown keys/versions/corrupt chain refuse; original bytes and FC unsettled exposure preserved; B0 admit/revoke/read works after v2; old decoder cannot silently write new state |
| Command identity | Registration uniqueness; every semantic field changes fingerprint; equivalent JSON bytes recognize; malformed input no mutation; duplicate before stale head/expiry; advance/resume namespace conflict; preview/status pure; at-capacity recognition |
| Head and sequence | Same-head competing commands yield one reservation; stale predecessor/head refuse; pending/unknown cannot be overtaken; completion leaves immutable command ref; evidence resume changes aggregate head but not logical predecessor |
| Authority consumption | Real signed S and permitted P exact terms; nonce across steps/sequences and the same policy slot across sequences/commands cannot be reused; a different policy does not reset original budget/source fences; pure steps consume only step; retained B0 admission/O1 projection cannot complete a step; ambiguous signed slots refuse |
| Currentness | Before/at/after trust, act, policy, signed/P slot and lease expiry; deliberately unequal expiries; issuer/act/policy/source revocation before reservation and each custody checkpoint; resupplied originals preserve first provenance; historical replay remains non-authorizing |
| Dynamic prerequisites | Real B1 completion chain; forged/resealed/missing source refuses; exact mapping/evidence scope; genuine later-stage source absent means refusal before consumption, never a mock source accepted by production defaults |
| Shared exposure | FC first/onboarding second and reverse; race with only enough budget for one; combined per-call/total/typed meters; zero-cost access handled without weakening FC; settled plus unsettled; overflow and unknown tariff; same source different policy/session/budget alias cannot reset; unmapped FC source blocks new shared work |
| Global fences | New sequence/command/policy, credential alias, session/phase, expiry and restart cannot clear unknown effect; source association conflict refuses; default concurrency one; no silent independent-budget inference |
| Custody ports | Fixed operation/host/wire digest, claim, executor/holder, capability scope/expiry/currentness; cross-root/foreign claims, replayed callback, changed wire and fake consume return refuse; secrets/exceptions never reach public output; default ports cause zero issue/consume/dispatch |
| Process loss | Kill before/after reservation publish; delivery before/after issue; consumption before callback; dispatch fence before/after remote-double effect; metadata before envelope; envelope before receipt; receipt before outcome. Restart recognizes only exact committed evidence; counter proves no reissue/redispatch and exposure remains conservative |
| Revocation ordering | Actual competing signed revocation vs reservation, delivery, consume and dispatch stages; barriers prove both serialized orders. No claim that post-fence revocation cancels an external request |
| Response/outcome | Original metadata/envelope/claim binding; missing usage, wrong operation, response ID, provider provenance, tamper/conflict keep unknown exposure; completion/settlement atomic; duplicate evidence produces no duplicate outcome; expired/revoked work never gains new authority from history |
| Retry/group | W1-W2-W3 ordering, first success skips retries, four distinct slots; frozen input/holder/configuration; group result uniqueness; actual empty allowlist refuses retries; hypothetical safe-failure scenarios separated from production defaults; terminal outcome does not imply zero cost |
| Later application seam | B0/O1/transport results cannot apply; no assignment writes in B1; later whole-set consumer must share atomic step/authority/application transaction |
| Compatibility | Unchanged B0 reviewer cases, prior source pins, CY/FC process recovery and operational flags; additive inventory/tripwire adversarial checks; no nested-lock deadlock under bounded worker timeout |

## Implementation validation selections

Run locked PHP >=8.4.1 / PHPUnit with no dependency updates. Record actual commands, versions, exit codes, exact source identities, skips and interrupted runs separately.

1. New ledger/exposure/custody/process suites above; all four existing ProviderOnboardingAuthorityAdmissionTest, ProviderOnboardingAuthorityProcessTest, ProviderOnboardingAuthorityReviewRegressionTest and ProviderOnboardingAuthorityCorrectionTest suites, unchanged requirements.
2. ProviderOnboardingOfflineSelectionTest, ProviderOnboardingResponseValidationTest and ProviderOnboardingBaseSelectionTest.
3. FormationClaimCustodyTest, FormationClaimCustodyProcessTest, CitadelMissionFormationTest, CitadelFormationCorrectionTest and NativeAuthorityProtocolTest. Exercise actual FC reservation/custody paths and each of interview/drafting/acceptance. A synthetic alternate entry point is insufficient cross-type proof.
4. CanonicalConsumerCorrectionBatch4Test, NativeInspectionSnapshotConsistencyBatch5TerminalAuditTest and NativeInspectionSnapshotConsistencyPreparationBatch0Test. Preserve exact historical source pins.
5. TransactionalAuthorityConsumptionBatch12CoverageTest and every FrozenRuntimeCoverageTripwireRestoration suite; additive inventory cannot hide consuming candidates.
6. PHP lint for changed/new PHP, kernel boot/default wiring smoke, and `python -B tools/check_provider_onboarding_spec.py` (all 86). Full hosted CI is the subsequent source/integration gate, not inherited B0 proof.

This document specifies future tests; it reports no B1 test execution.
