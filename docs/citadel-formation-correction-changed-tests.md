# Citadel correction: changed-test map

Baseline: `e0386e75ce7619fbbeaac450af078d2d606df012`. Original tests, results,
archive and manifest remain in that preserved candidate and historical proof.

| Source | Change and reason | Evidence |
| --- | --- | --- |
| `CitadelFormationCorrectionTest::testCF01IndirectReopeningMustRemainRefused` | Added regression for authentic REFUSED -> DEFERRED -> OPEN | C0 fails: OPEN; corrected run retains REFUSED |
| `testCF01DirectAndStaleControlsPreserveAccountingAndLegitimateResume` | Added direct and stale control replay, valid resume, budget exhaustion and separately granted new session | Ledger unchanged across control/refusal; remaining allowance consumed exactly |
| `testCF01RetainedRefusalFencesOldReopenedProjectionWithRemainingBudget` | Added compatibility fixture for the status projection proven in C0, retaining authentic refusal history | Calls and controls fenced before provider activity despite unused allowance |
| `testCF01RefusalFencesSealedAdmissionAndRetainsUnknownExposure` | Added refusal between sealed unknown outcome and explicit admission recovery | No admission, retry, refund or new call; worst-case reservation retained |
| `testCF02RealPublishedChildMustReconcileAfterApprovalExpiry` | Added native child rename interruption and expired decision recovery | C0 errors with CMF094 after actual receipt publication; correction preserves bytes and identity |
| `testCF02LaterRevocationSuccessionAndTrustExpiryDoNotEraseCompletedEffect` | Added later same-timestamp revocation, holder succession, trust expiry and attempted new assessment | Historical fact reconciles; later authority checks still refuse; no acceptance invented |
| `testCF02ExpiryWithoutAnyEffectCannotCreateChild` | Added fresh expired approval case | No child directory, parent mutation or extra cognition |
| `testCF02BeforeEffectChangesCannotCreateOrReleaseUncertainChild` | Added expiry/revocation after interruption before native child rename | Both command and producer refuse new write; uncertain reservation cannot expire/release |
| `testCF02UnverifiableReceiptsStayFenced` | Added nine absent, identity, bytes, checksum, provenance, frame, reservation, time and native-witness adverse cases | Exact concrete refusal; parent/ledger unchanged; no second child |
| `testCF02ConcurrentCommandRecoveryPublishesExactlyOneParentTransition` | Added three separate PHP command/DI processes recovering one receipt | One generation and registry increment, one child/handoff, three occupied seats, unchanged cognition |
| `testCF02ConcurrentCompletionBetweenSnapshotAndRecognitionIsIdempotent` | Added deterministic interleaving after native unlock: another delivery completes before the stale reader resumes | Reproduced an undefined prepared-record error in the correction candidate; recognition now derives content from the verified receipt. Test converts unexpected warnings into failures. |
| Historical `CitadelMissionFormationTest::testPartialConstitutionKeepsIdentityFenceAndReplaysExistingChildReceipt` | Renamed to `...RefusesUnprovenChildReceipt`; retains storage-failure and manual-insertion setup, but now expects CMF130 and retained fence | A hand-inserted receipt lacks publication provenance. Its former positive assertion was precisely the insufficient evidence identified by review. The new real-publication positive is separate. Original method bytes are included in the packet's historical directory. |
| `CitadelPublicationInterruption` | Added test-only namespaced filesystem fault wrapper | Calls native rename, then throws before parent publication; separate before-rename mode proves the other boundary. Root-scoped and never loaded by production. |
| `citadel-recovery-worker.php` | Added signer-free synthetic-root command/DI worker | Uses refusing production transport; takes no credentials and issues no authority |

No other historical tests were removed or relaxed. The native child publication
and trusted historical authority checks are production code, not fixture-seeded
receipts. The compatibility projection and deliberate corruption cases are
explicit test fixtures, not evidence that such data was legitimately produced.

Development exposed one test expectation error: expired receiving personnel is
rejected at `CMF043` before reaching the later decision verifier. The assertion
now names that actual existing boundary; production authority checks were not
weakened. The development failure transcript is retained locally.

The unlock interleaving extends the same test-only filesystem wrapper. It runs
the competing production command after the actual native lock release, never
inside a fabricated atomic boundary. The wrapper remains absent from production.
