# PPC3 test map

The unchanged full eight-partition PHPUnit/source/exact-case coverage gate and all 11 Python guards are required. Final native exits, UTC intervals, platform versions, counts, skips, warnings and source identities are in the public evidence packet and the [implementation report](handoffs/provider-native-tenure-report.md). Earlier PPC2 CI is historical only.

| Executable test | Obligation |
| --- | --- |
| `NativeTenureSynchronizationTest::testRegistryAndAuthenticCurrentConsumerBothProcessOrders`, two datasets | Actual enrollment against actual owner-bound model candidate; explicit held/release/attempt barriers, independent nonblocking Formation-lock probe, both orderings, bounded completion, subsequent new-process refusal |
| `testLiveFrameRejectsWrongRootEscapeAndExceptionThenReleases` | Same-root live capability, serialization refusal, expired capability, exception unwinding and fresh-process positive reconstruction |
| `testRealIndependentAppointmentCurrentConsumerAndHistoricalReplay`, two Seats | Authentic native producers and institutional signatures; exact independent appointment; real current holder consumer; registry invalidation without erasing completed fact |
| `testGenericStorageRemainsAnOpenWriterPathButCompletedRetirementRefuses` | Proves the direct generic writer is still outside Formation; only post-retirement current refusal is proven |
| `testPinnedStateStoreNestedRequiredInstallerUsesActualOwner` | Pinned StateStore callback reaches required installation without inverted Formation acquisition; nested NativeJournal inspection |
| `testIndirectProductionEntryPointsInBoundedFreshProcess`, V0 and StateStore datasets | Actual MasterMason/V0/required/direct owner chain, physical Formation-lock probe, bounded processes and exact replay |
| `testInterruptedNativePackageNeverAuthenticatesSubset`, four checkpoints | Kill actual root installer at package intent, installation, placement and all-placements boundaries; release/reacquisition; every role refuses; pending exact replay refuses |
| `testUnsupportedFormsCannotUseUnchangedOldOccupancy`, three datasets | Unknown bootstrap/same-seat successor forms and old package without known completion evidence cannot fall back to intact occupancy |
| `FormationModelBoundProfileTest`, retained assertions | Both targets, authentic complete version-specific approval/qualification, old-ingress rejection, corruption, downgrade attempt, changed configuration/target, expiry, delegation revocation and fresh-process reconstruction |
| `NativeAuthorityProtocolTest`, retained suite | ADOPT/SUPERSEDE/RETIRE/REVISE/revoke effects; actual competing processes; native consumer currentness, enrollment fencing, interruption and exact historical recovery |
| `NativeAuthorityCorrectionTest`, retained suite | Accepted-at instant, authentic recovery, separate revoked/expired effects and legacy admission fences |
| `RequiredV0PersonnelInstallationTest`, `ConstableSeatBindingServiceTest`, `GuildhallSeatBindingServiceTest` | Historical V0/operationalization/upgrade behavior and actual alternate producer publication and refusals |
| Complete original Formation/onboarding/persistence tests | O0–O5/PPC0–PPC2 compatibility, FRESH ownership, source pins, service wiring and original independent appointments |

`Support/native-tenure-worker.php` uses only generated disposable roots and public fixture inputs. Wait loops have deadlines; sleeping alone is not the ordering oracle. The parent uses the actual lock probe and child checkpoints. Workers return native success/refusal exits; killed workers are asserted nonzero. `Support/model-bound-profile-worker.php` and `ModelBoundFormationFixture::assembly` now pass the real journal-issued owner into the new current candidate seam.

The public-original collector retains exact synthetic native installation/occupancy/package originals, complete signed Formation history, independent appointment, public native enrollment and the positive/current-refusal distinction for both targets. Private fixture signing keys stay in memory and are not archived. The model authorization/source-line declarations remain unverified.

A diagnostic full-gate attempt at `f68b601d1bfb1db480faa990860e5ee77d9b9b39` was intentionally interrupted after source review identified an unknown-bootstrap fallback. Its incomplete evidence is retained separately and is not a successful complete gate. The corrective candidate is `6ecf1e4417453a35f9781b21fbc16632965febec`; final validation uses eight isolated worktrees. No selection, test, source guard, dependency or CI rule was weakened.

Two further diagnostic gate attempts were interrupted for concrete source-review corrections: `3a295120` still used the legacy actor lookup at model-bound evidence ingress; `6a585386` still skipped unrecognized same-Seat non-ACTIVE successor forms. Intermediate candidate `5a8d10cd` corrected both. All interrupted logs and process-termination records are retained, with no successful native exits inferred for killed workers.

A fourth diagnostic attempt at `5a8d10cd` was stopped when review expanded wrong-root proof to V0 with an incorrectly injected StateStore. Intermediate candidate `c1c0e72b` added refusal of cross-root native nesting before a second owner acquisition. `testV0CannotConsumeAForeignInjectedBootstrapOwner` proves bounded refusal with neither foreign bootstrap nor installation publication. The four stopped attempts are not final gate evidence.

The fifth diagnostic attempt (`c1c0e72b`) produced a real partition-4 failure: 397 tests, 5,380 assertions, one failure, one warning, native exit 1. The immutable generic storage edit violated its terminal successor pin. Candidate `6ecf1e44` restores both generic primitives and removes the unused wrapper, retains a test exposing the open path, and is the final complete-gate candidate. R2 remains partial even if its final compatibility gate passes.
