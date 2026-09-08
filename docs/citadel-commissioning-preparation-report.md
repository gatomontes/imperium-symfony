# CP0–CP3 commissioning preparation report

Disposition: **COMMISSIONING_PACKAGE_PREPARED_FOR_OWNER_REVIEW**. Local preparation is complete; independent review is next. `deployment_approved`, `enrollment_authorized`, `live_ready`, `activation` and `execution_authority` are all **false**. Recommendation: defer enrollment until the owner explicitly accepts the legacy fence and resolves deployment, custodian, signer and genuine evidence prerequisites.

## Concrete outputs

| Step | Result |
| --- | --- |
| CP0 | [Installation assessment](citadel-commissioning-installation-assessment.md) and complete [213-path source comparison](citadel-commissioning-source-impact.tsv); external timestamped Git observation and sanitized assessment |
| CP1 | [Owner decisions/signing interface](citadel-commissioning-owner-decisions.md), [public example](citadel-commissioning-proposal.example.json), [schema](citadel-commissioning-proposal.schema.json) and [38-source workflow matrix](citadel-commissioning-compatibility.md) |
| CP2 | [Bounded Python checker](../tools/citadel_commissioning.py), [package contract](citadel-commissioning-package-contract.md), and [phased owner runbook](citadel-commissioning-owner-runbook.md) using actual command shapes |
| CP3 | [Refusal/preservation tests](../tools/tests/test_citadel_commissioning.py), [command/DI rehearsal](../tools/prove-citadel-commissioning.php), existing native regressions/proof, exact command logs and complete review packet |

No production runtime, contracts, dependencies or existing tests changed. The checker reads five fixed public filenames at most, checks package syntax/identity/byte hashes and freshness, and never opens the assessed runtime root or invokes Git/PHP. It intentionally does not certify complete Garrison/Recruiter record semantics: actual PHP inspectors/protocol enforce those contracts in rehearsal and later separately authorized use. Source metadata and hashes do not prove running code, current incumbency, historical execution or owner approval. Complete synthetic input still returns unapproved preparation with false flags. Real missing values remain explicit nulls with consequences, not invented approvals.

## Source and execution attribution

Entry: `170902f4c609c25776bc53a70950866a542c3d92`, tree `7f5715bea7b4bde2057077f0a0b17a977bca2f8d`; clean tracked entry, branch `codex/citadel-native-authority-commissioning`. Accepted runtime merge `f35a0b33ddab9e8815470a697d8593984e050023` is an ancestor. Proposed deployment target stays that merge and accepted tree `e4140d95cbb83b3dbf9125849953cec0c7eda989`, separate from preparation code.

Executable preparation checkpoint: `276f7cfe34dbcda7714eba3dd40161a72a153f0a`, tree `e585d1ea95a0040a76b68922f3b155464e9af2b9`. All final associated gates below ran on this committed source with clean tracked status. Two generated untracked Python bytecode directories were disclosed in gate metadata; they are not source payloads. No executable edits followed these gates. Final closeout changes Markdown only; final commit/tree and exact post-test diff are external to avoid a self-referential source identity.

| Newly executed gate | Result |
| --- | --- |
| Focused PHP: NativeAuthorityProtocol/Correction, authority interfaces, inventory, StateStore and relevant coverage tripwires | **95 tests / 4,425 assertions**, native exit 0; no failures/errors/skips or warnings in output |
| New Python checker suite | **20 tests**, exit 0; schema/source/fingerprint/freshness/compatibility refusals, private path rejection before open, hardlink refusal, root/byte preservation, fresh-output rule and approval separation |
| Existing Python collector compatibility | **16 tests**, exit 0; unchanged source and assertions |
| Existing synthetic PHP proof | Exit 0; original/private synthetic bytes preserved, exact completed recovery without new frame |
| New commissioning command/DI rehearsal | Exit 0; separate enrollment, snapshot/resolve, prepare/sign/assemble/apply, A0 request building, separate admission/inventory, actual consumers, revocation and expired completed recovery |
| Public cryptographic verification, each of the two proofs | Exit 0; **six chained frames, four Ed25519 acts, one admission** per proof; synthetic public bytes only |
| Real-input proposal checker | Expected native exit **2**; useful review package with missing owner/evidence inputs and DEFER_ENROLLMENT |
| Complete synthetic proposal checker | Expected native exit **2**; complete public inputs, still synthetic and unapproved, all five flags false |

Exact executable/argument arrays, environment names, UTC start/end times, native exits, tracked before/after status, raw stdout/stderr and JUnit are retained in `E:\htdocs\citadel-commissioning-review-20260908`. The PowerShell command host sometimes reports exit 1 for a child exit 2; the child native exit recorded by the harness and expected preparation disposition are authoritative. No refused preparation is relabelled as positive authority.

The focused JUnit includes all 19 unchanged NA-IR01/02 correction cases. Same-Persona retained legacy custody refuses without new effect or changed original/legacy custody/disposition; a distinct Persona coexists. Advancing clocks retain one acceptance instant across fresh checks and exact historical recovery. Existing process tests cover competing revision/revocation, retirement while admission waits, StateStore/enrollment exclusion, before-commit/pending-durable/after-commit interruptions and unknown pending fences. These are native offline Windows process tests, not a new power-loss or trusted-timestamp claim.

The new proof records exact public input arguments, command exits/UTC times, compiled DI outputs and public journal frames. Synthetic private state is seeded in generated temporary roots only; ephemeral signing keys remain in memory and are cleared by the fixture. Neither proof reads the installation or uses a genuine key/provider. Existing vendor dependencies were copied from the accepted source worktree, without Composer install/update or autoload regeneration. Parent PHP outbound functions are disabled and URL fopen is off; existing subprocess fixtures also use restricted environments/flags. This is a restricted harness, not an OS network sandbox. No credential-bearing environment was inherited; names are disclosed without values.

Development: an initial 20-test checker invocation passed on uncommitted/untracked new source, and the development rehearsal passed on that same preparation baseline. Its early harness recorded tracked-only status, so empty tracked status there must not be read as a clean committed source claim. Final gates supersede development validation. Early discovery commands had missing filename guesses and one malformed search path; they made no source changes. A bytecode cleanup command was rejected by automatic tool policy before execution; validation proceeded without cleanup, with caches disclosed. No PHP/Python test failure occurred. No generated `config/reference.php` change occurred in these targeted gates; container boot/lint and full-suite execution were not added.

## Preserved evidence and boundaries

[P0–P3/NA-IR01/02 acceptance](citadel-native-authority-protocol-acceptance.md) remains closed within local/offline scope. Historical supplied full PHP is **2,867 / 53,961**, with four historical warnings; this campaign does not claim a new full-suite or GitHub CI run. The accepted correction ZIP remains identified by SHA-256 `6f9fb59b30f48ea7a6f738b9b6ba53b526460f5d4d4a6abe3817938659577783`. Earlier formation acceptance, CF01/CF02/IR01 and Delegate Steps 1–69 remain unchanged.

The retained Guildhall follow-up archive SHA-256 `3ca224dd162f9fb7987942dbfb9f7e2fc70b62f4e31b6e9314ebd68d930bd977` and collection SHA-256 `cf21ad4edf1391601bd1f7f95b5e9f5ce0a6d4eb0a983587f432f293d6eaca87` were checked before reusing the exact public Garrison original. No installed runtime recollection or repeated Guildhall acceptance investigation occurred. Historical raw evidence stays outside Git, retaining original collection time and byte hashes. Genuine current Recruiter evidence and runtime custody remain missing.

The external review packet includes tested/final source ZIPs and file/mode/blob/byte manifests, bounded Git bundle with prerequisites, complete final and post-test diffs, source identity/status/preservation records, public proofs and inputs, raw gate logs, retained-public provenance, SHA manifest, verification script and external ZIP SHA-256. Read the packet index for exact final identities. No raw private installation or credential material is packaged.

Next: independent package review, then explicit owner decisions, then separately authorized deployment/enrollment and genuine acts only when their prerequisites are established. No publication, PR, merge, source deployment, key handling, enrollment, process stop, service change, provider call or mission execution was performed on the installation. Formation-specific competence/trust, other Seats, suitability/delegation, candidates/appointments and B1 remain open. Native inventory is not execution authority. Citadel receives; Castellan interviews; understanding closes interview authority; drafting and mission approval are separate; receiving assessment grants no execution authority. Unknown outcomes retain exposure without retry/refund; default formation transport refuses.
