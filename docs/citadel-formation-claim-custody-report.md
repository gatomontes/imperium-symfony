# Citadel formation claim custody — local FC0–FC3 report

Disposition: **FORMATION_CLAIM_CUSTODY_IMPLEMENTED_OFFLINE_LIVE_TRANSPORT_BLOCKED**. Independent review is next; this report does not claim acceptance or integration. Owner disposition remains **DEFER_ENROLLMENT**. `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation` and `execution_authority` are all false.

## Exact scope and identities

Work was confined to `E:/htdocs/imperium-citadel-formation-custody`, branch `codex/citadel-formation-claim-custody`, generated temporary proof roots and the external public review packet. Entry main was `758dcd6e5f84e32a65e4e5dda68dbb5b86d7c7e8`, tree `a3a0dbaaac4be942bcf57d473732d4a7be5a5934`, containing accepted CP0–CP3 merge `a3e113dfcb29c78ecfa60a8def45e1d3fc32f37a`. No installed application/private-state assessment was performed in FC0–FC3. The launch document was the only installed-checkout task input. No dependencies were installed or updated; existing offline vendor was copied from the commissioning worktree, and reflection verified that this worktree loads its own source.

Tested executable commit: `b33226db7720d7d6768274ac3fc96aa38b787b62`; tree: `04ac9f1465663736843ccc25c2abe258091dd938`. Later edits are Markdown-only, listed exactly in external `post-test.diff`; external `final-identity.json` records final commit/tree without a self-referential report hash. Local commits only: no push, PR, merge, deployment or commissioning.

## Software changes and authority map

The [FC0 contract](../contracts/citadel-formation-claim-custody.md) maps every producer, retained fact, consumer, lock boundary, currentness check and crash consequence. The narrow chain is:

`FormationInstitution / FormationPersonnel / FormationSignatures` → `FormationCognition::grant` and authentic `GovernanceProviderResourceDecisionService::formationSession` → exact prepared operation → `SessionExposure::reserve` → `FormationSessionLeaseService::derive` → retained v2 claim and durable formation start → `CustodiedFormationTransport` → `FormationClaimCustodyBroker` → scoped `CredentialBroker::issue/consume` → fixed `FormationWireAdapter::dispatch` → actual `ProviderResponseEnvelopeService` → `FormationCognition::call/recover` and original phase admission.

The broker reads the actual aggregate claim; it does not translate a caller's self-sealed object into legacy authority. Shared `FormationSessionAuthority` preserves the original source, session-control, refusal-history and understanding checks. The exact v2 operation binds request/wire bytes, provider/model/destination, signed public credential reference/operation/adapter, limits, authority source, pricing digest and expiry. Every boundary rechecks current holder/issuer, phase, authentic owner decision, exact request, consumed derivation/lease and aggregate exposure. The prepared inspection is performed once; its exact bytes are retained before custody. The existing v1 path/recovery remains available to its old consumers and confers no new custody authority.

Delivery, consumption and dispatch checkpoints commit before their possible effects. No lock spans credential or provider infrastructure, and the response-store lock is never nested under the aggregate lock. A second process or repeated credential callback cannot enter issue/dispatch twice. Refusal, completion, revocation, supersession and expiry before the final dispatch checkpoint block fresh use. A change after that checkpoint cannot cancel the effect already authorized there. Validated response ID, provenance, usage and body hash are retained before envelope publication, so a recoverable envelope never depends on attribution held only in memory. Recovery requires that metadata and the matching body; a dispatch fence alone is insufficient. Interrupted attempts retain their one-use fence and uncertain maximum; there is no retry/refund/reset.

`FormationPreparation` now serializes the optional exact signed transport addition and reports the closed custody software gap separately from the still-missing live adapter. Actual `config/services.yaml` loading and container lint confirm the unchanged `UnavailableFormationTransport` alias and the new refusing `UnavailableFormationWireAdapter` alias. No live adapter, runtime selection switch, environment activation flag or ordinary CLI override was introduced.

## Guarantee and limit table

| Property | Local result | Limit |
| --- | --- | --- |
| Authentic custody authority | Exact retained session/attempt, signature/currentness, phase, holder/issuer, consumed derivation and limits checked before issue | Trusted root and cooperating code/locks; not protection from administrator rollback or malicious writers |
| Exact operation | Canonical request and exact wire bytes bound before reservation and checked again before dispatch | Deployment-selected adapter must honor serialization, single destination/no redirects and usage authentication; no live adapter implemented |
| One-use custody | At most one issue and one dispatch per retained attempt, across processes/callback replay | Not exactly-once remote delivery; a crash can forfeit an unused attempt/capability |
| Currentness | Rechecked at delivery, consumption and final dispatch authorization | Checkpoint ordering, not guaranteed physical I/O before expiry or remote cancellation |
| Accounting | Ordered integer usage validated; malformed/absent/excessive/contradictory returns cannot reduce exposure | Synthetic usage is not remote billing evidence; recovery without caller settlement keeps full maximum |
| Response recovery | Validated retained attribution and exact trusted-root envelope/claim/body; original provider ID/provenance survives publication interruption; no fresh issue or invoke | Immutable record store and process-interruption proof do not establish independent timestamps or power-loss durability |
| Confidentiality | Public references only; sensitive callback context; no retained context or chained infrastructure error | Exact echo refusal is defense in depth, not general DLP or protection against malicious trusted adapter code |
| Default safety | Both transport aliases refuse; pure preparation has zero credentials/I/O/mutation | Registration is not operational approval; no live compatibility proved |

## Fresh validation and retained failures

PHP 8.4.14 / PHPUnit 13.3.0. Final focused gate ran first, followed by the complete offline PHP suite on the same committed executable tree. No failures, errors or skips.

| Gate | Result | Native exit | UTC start → end |
| --- | --- | --- | --- |
| focused | 78 tests / 4266 assertions; no warnings | 0 | 2026-09-09T01:39:57.726497+00:00 → 2026-09-09T01:42:10.321757+00:00 |
| full-suite | 2929 tests / 54268 assertions; four qualified warnings | 0 | 2026-09-09T01:42:10.492273+00:00 → 2026-09-09T01:59:27.999441+00:00 |
| final-lint | PASS | 0 | 2026-09-09T02:00:06.331341+00:00 → 2026-09-09T02:00:11.763033+00:00 |
| synthetic-proof | PASS | 0 | 2026-09-09T01:41:07.595584+00:00 → 2026-09-09T01:41:09.536191+00:00 |
| verify-public-proof | PASS | 0 | 2026-09-09T01:42:26.266912+00:00 → 2026-09-09T01:42:27.075014+00:00 |

The four fresh full-suite warnings exactly match the accepted native correction transcript after worktree-root normalization: `.git/HEAD` reads in DeploymentCustodyCrashDemonstration:290, OperationalConstructionCrashDemonstration:399, TerminalRetirementCrashDemonstration:183 and UnknownProviderOutcomeCrashDemonstration:245. These linked-worktree evidence helpers remain qualified; no warning was silently removed. New custody cases pass without warnings.

Exact command arrays, UTC times, native exit codes, source status before/after, PHP binary/dependency identities and raw stdout/stderr/JUnit are in the packet. Gate environment was allowlisted OS essentials plus `APP_ENV=test`, empty `APP_SECRET`, offline Composer flag and the existing Python cryptography path. Parent PHP network entry functions were disabled. Test workers also explicitly disable those entry functions and accept only generated fixture roots. No dependency update, installed configuration or real credential was used.

Development results are separate from committed gates. The first draft had a test-helper method collision with PHPUnit's final `attempt()` (native exit 255); it was renamed. The initial receiving fixture's signed input ceiling was too small for a full handoff retaining v2 byte-bound claims; production correctly refused it. The fixture now discloses and signs a larger synthetic receiving ceiling before grant, and all three phases pass. Raw failures and subsequent passing gates remain in the packet. Symfony's generated `config/reference.php` PHPDoc diff was retained, inspected and restored to its committed blob; it is not an implementation change. Historical full-suite results in accepted reports remain historical, distinct from this new run.

Final source review identified a response-recovery gap in the first local candidate `1b5f45d3c1a3f1a04fc62c517b6655fe3e2ce22a`: its dispatch fence could recognize an independently self-sealed body without validated returned attribution. A diagnostic loaded that exact prior `FormationCognition` source with current supporting classes in a generated fixture and reproduced the failure (one test, native exit 1). This was not a full old-tree run. The corrected producer durably retains validated response metadata before sealing, and recovery requires it. Twelve targeted post-correction cases passed before the new executable commit. The initial 177-test focused run and deliberately interrupted initial full run (native process exit 4294967295, incomplete/no JUnit completion) remain under `initial-*`; they are superseded, not passing evidence for the final candidate. Only the identified offline test process was stopped; no installed process/service was changed.

The public synthetic proof producer is `tools/prove-citadel-formation-custody.php`. Independent Python verification checks 32 unique generated owner/institutional signatures, two public frames, claim/lease/operation/request/wire/envelope and retained response attribution, and one issue/consume/dispatch; a substituted proof is refused. This verifies supplied public bytes, not independent historical custody, genuine competence, installed readiness or remote guarantees.

The first complete full run on `31bdeb35c14c5c15b8b2bc126e4cec4948b4b9e7` reported two frozen-inventory failures: `FrozenRuntimeCoverageTripwireRestorationBatch3TerminalAuditTest::testExplicitlyInventoriedCandidateAdditionPasses` and `TransactionalAuthorityConsumptionBatch12CoverageTest::testMechanicalRuntimeCoverageMatchesTheFrozenBatch12Snapshot`. Both identified the new `FormationClaimCustodyBroker` missing from the explicit successor inventory. One row was added to `docs/citadel-mission-formation-runtime-inventory-v1.tsv`, identifying the authorized FC0–FC3 consumer and its tests. No production code, test assertion, original snapshot or canonical-consumer count changed. Sixteen affected inventory tests passed. `pre-inventory-*` retains that complete failed run and its earlier 179-case behavioral focused pass; the final committed focused gate covers the new custody cases and repaired coverage tests, followed by a fresh complete suite including all existing regressions. The PHP, test and contract files are byte-identical across this inventory-only commit.

## Changed-test map

| Tests / proof | Production boundary exercised |
| --- | --- |
| `FormationClaimCustodyTest` legitimate command/DI and separate drafting/receiving | Genuine grant, reservation, consumed lease, custody, actual envelope, settlement/admission, non-execution and retained completion |
| 23 substituted claim/operation cases | Resealed/forged digest, session, attempt, source, holder, issuer, lease/authority flags, phase/body/wire, provider/model/destination, credential reference/operation/adapter, per-call/aggregate/expiry and v1 refusal before credential entry |
| Seven currentness cases plus late issue/consume hooks | Expiry, refusal/defer, Castellan/Locksmith replacement, understanding and revocation; late refusal and changed wire stop subsequent effects without reopening replay |
| Pure inspect, actual YAML aliases and public preparation | Zero runtime mutation, credential issue/consume or dispatch; exact signed transport fields with no authority promotion |
| Bad results, secret-bearing exception, callback replay and budget exhaustion | Missing/excessive/non-integer/reordered/calls/provenance/scope/context failures, no secret-bearing exception chain, full unsettled exposure, no second dispatch/refund |
| Cross-root claim, self-sealed pre-custody envelope and unvalidated body after a real dispatch fence | Authentic evidence from another generated aggregate and a response hash/fence alone do not create custody/admission; validated returned attribution is required |
| `FormationClaimCustodyProcessTest` | Two workers contend at real custody; one succeeds, one refuses; one issue/consume/dispatch total |
| Eight process exit cases | Before delivery; after delivery/before issue; after issue/before consumption; after dispatch fence/before effect; after effect; after validated metadata/before envelope; after real envelope publication/before completion receipt; after completion receipt/before caller settlement. Counts: 0/0/0, 0/0/0, 1/0/0, 1/1/0, then 1/1/1 for the last four. Each post-delivery replay refuses; original call never retries; retained envelope cases preserve provider ID/provenance and recover with full unsettled maximum |
| Existing formation/native/legacy broker tests | CF01 terminal refusal, CF02 completed child publication, IR01 interview closure, NA-IR01/02 and legacy custody compatibility. No original assertions removed |
| Existing frozen coverage tests and one new inventory row | New broker is explicitly inventoried; unsnapshotted consumers, unapproved store/perimeter helpers and vocabulary changes still fail closed. No mechanical scanner or historical snapshot was weakened |

## Blocker delta and exact future prerequisites

Closed locally, pending review: formation-specific retained-claim custody; durable one-use infrastructure entry; public credential/operation/wire binding; default-dormant adapter seam; actual offline response/usage integration; adverse/concurrent/interrupted proof. These are software closures only.

Still unresolved: actual installed source/custodian and formation public trust (accountable authority custodian, confirmed public key/fingerprint, validity and public signature exchange); genuine lineage/currentness for Garrison, Guildhall, Laboratorium, four Senate committees, Lord Speaker and Conscription (nine witnesses); formation-specific delegated competence and genuine personnel judgments; complete qualified Castellan and Locksmith appointments; actual provider/model/destination/reference/operation/adapter selection; enforceable B1 remote cost/time/cancellation and pricing/usage evidence; public preflight; separate owner commissioning authorization. Native enrollment remains deferred and would not supply these formation prerequisites. The accepted Guildhall planning evidence is not upgraded to formation competence.

B1 is unchanged. An output-token cap or local HTTP timeout does not establish a remote billing ceiling or remote cancellation. A future owner must either supply supportable guarantees for a concrete reviewed adapter or explicitly amend the accepted policy in a separate decision. This campaign made neither decision.

Implemented public preparation commands, to use only in the separately scoped future evidence workflow, are `php bin/console imperium:citadel:public-institutions`, `php bin/console imperium:citadel:prepare inspect <public-file>`, `php bin/console imperium:citadel:prepare decision <public-request-file>`, and `php bin/console imperium:citadel:prepare assemble <public-signature-file>`. Inspection returns 2 while readiness remains blocked. They do not select an adapter, enroll trust, sign, deploy or authorize execution. No implemented live activation command can be supplied because no live adapter or commissioning authority exists here.

Citadel receives; Castellan interviews; understanding closes interview authority. Separate approval permits drafting; separate mission approval precedes legitimate child-Curia constitution/handoff. Receiving assessment grants no execution authority. CF01/CF02/IR01 and NA-IR01/02 remain closed within their accepted scopes. Independent review of this exact packet is the next step.

## Review package

External directory: `E:/htdocs/citadel-formation-custody-review-20260909`. It contains complete tested/final source ZIPs with path/mode/Git-blob/byte-length/SHA-256 manifests; a bounded Git bundle requiring entry commit `758dcd6e5f84e32a65e4e5dda68dbb5b86d7c7e8`; exact final/post-test diffs; public proof and verification script; all development/final gate logs and identities; a complete payload SHA manifest; and packet verification instructions. The outer ZIP SHA-256 is provided separately. The packet excludes installed/private evidence, real credentials, private keys and vendor contents.
