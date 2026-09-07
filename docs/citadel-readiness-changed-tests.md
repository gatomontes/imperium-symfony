# Citadel readiness changed boundaries and proof

No existing test assertion was removed or changed. CF01/CF02 tests and accepted
historical packets are preserved. `CitadelFormationFixture` adds only
`signPrepared`: it signs exact validated packet bytes using that fixture's
ephemeral in-memory synthetic key. No production signer or identity is added.

## New focused cases

`tests/Imperium/Runtime/CitadelReadinessPreparationTest.php` contains ten cases:

| Test | Observed boundary |
| --- | --- |
| `testPreparedBytesAreConsumedByActualGrantAndDefaultTransportStillRefuses` | Actual preparation command/DI creates exact packet; public signature assembly rejects a wrong signature; native grant consumes prepared signed bytes. Journal/files and fake-call count remain unchanged during preparation. Explicit default transport refuses; synthetic interview creates no dossier. |
| `testExactPreparedSignatureRejectsChangedAuthorityOrDestination` (five data rows) | Altered destination, model, trust fingerprint, Citadel actor scope or object digest produces CMF022 before a grant/call; no session appears. |
| `testPublicInspectionNeverTreatsSyntheticEvidenceAsGenuineAuthority` | Byte-consistent synthetic public trust remains unverified and blocked; ProtectedMission competence fails formation trust check. Supplied transport flags cannot create readiness. |
| `testPublicExportReadsNativeWitnessesWithoutWritesAndEmptyRootStaysEmpty` | Nine native synthetic witnesses resolve, file hashes remain identical, and missing installation root produces exit 2 without even creating a lock/store. |
| `testFormationClaimCannotAcquireLegacyCredentialCapability` | Real synthetic aggregate claim is rejected by the existing Delegate credential broker; spies prove neither capability issue nor consume executes. |
| `testPreparationRefusesExpiryMissingPricingAndActivationInjection` | Expired request, empty tariff, injected root and activation effect refuse; zero fake provider calls. |

The preparation DI container registers only Clock, FormationPreparation and its
command. It has no journal, credential or provider dependency. The separate public
institution command reads the fixed-root native resolver and never reads the
formation journal. The production container lint and transport alias inspection
are retained in proof. Symfony's generated reference-only changes are saved as a
diff then restored; they are not application changes.

## Preserved adverse boundaries

`CitadelMissionFormationTest` retains wrong institution/missing examination,
unknown pricing, revocation during I/O, receiving GAP, no proposal without
understanding and separate signature, terminal refusal, deferral/expiry,
succession/changed intent, aggregate budget contention, unknown reservation with
no retry, sealed recovery without refund/call, context reassessment, numbered
objections/revisions, and uncertain child identity fencing.

`CitadelFormationCorrectionTest` retains terminal refusal including stale controls
and admission, legitimate never-refused resume with accounting, real child
publication followed by expiry/revocation/succession, absence before effect,
unverifiable publication evidence and concurrent exactly-once parent recognition.
These regression checks do not reopen the accepted correction or repeat an owner
ceremony. Their public proof verifier continues to check the original signatures
and retained receipt identity.

## Command/DI rehearsals

`tools/prove-citadel-readiness.php` adds inspection → exact packet preparation →
external synthetic signature → public assembly → actual formation consumers.
Four distinct effects are signed: interview, drafting, mission/constitution,
receiving assessment. The three synthetic calls carry actual request objects;
readiness/preparation itself does no cognition. The result ends at
`STEP_1_SCHEMA_AND_FOREIGN_REFERENCES_VALIDATED_NO_EXECUTION`.

The unchanged formation and correction scripts are also run in fresh generated
roots on the executable candidate. Their ephemeral keys remain in memory and their
exports contain public evidence only. Python Ed25519 verification independently
checks prepared bytes/signatures plus the preserved correction proof. It verifies
cryptographic integrity, not real custody or semantic competence.

There is no fake-HTTP claim of real adapter support: no provider was selected and
the live bounded/custody contract is unresolved. Default transport refuses in
production DI. A future B1 resolution and concrete adapter would require its own
HTTP, credential, exact-wire and provider-limit tests before commissioning.

Exact tested identities, counts, commands and warnings are in the
[terminal report](citadel-readiness-report.md) and `proof/verification.json`.
