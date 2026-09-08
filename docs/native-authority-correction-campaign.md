# Citadel native authority protocol — independent review and local correction handoff

**Verdict: CHANGES REQUIRED.** The P0–P3 package has verified source identity and meaningful positive offline evidence, but two defects block acceptance of its supported admission/recovery behavior. Correct these within P0–P3. Do not restart the formation, readiness, native-lineage or authority-interface campaigns.

This review authorizes no installation change, real policy approval, enrollment, key operation, live provider call or mission execution. No publication, PR or merge was performed during this review.

## Verified package and attribution

| Item | Independently verified identity |
| --- | --- |
| Uploaded archive | `citadel-native-authority-review-20260908.zip` |
| Archive SHA-256 | `17ea97c06531c8892d3357b33622b9cd11f8d2fea4425e7bb664f1f6a352e2a9` |
| Campaign entry | `031091dda39798429d2af79bca106ad2bfe983e8` |
| Entry tree | `986f4c12f7c7aa757ef17af58051730cf460ecb7` |
| Tested executable commit | `fa4fa7dbebee26890eab3ebf827fe8a157583cb2` |
| Tested tree | `54c8327b98d5ddebecfdb1496f51415a217e2c08` |
| Final review commit | `be09c33af8f28dccfe27df3bbeaf387f9afbf97d` |
| Final tree | `7c2aa9d0b2fcfe7859089845774d0dc368ab0b85` |

The external checksum matches the uploaded ZIP. The packet's 79 manifested payloads, complete path set and hashes verify. The separately uploaded `REPORT(1).md` is byte-identical to the packet report. The Git bundle verifies against the available prerequisite history. Both source archives match their Git trees: 3,103 tested blobs and 3,104 final blobs, including paths, modes, sizes and hashes.

The exact tested-to-final Git diff matches the supplied post-test diff. It changes only five Markdown documents: readiness matrix, readiness runbook, delegate mission flow, native authority protocol report, and next lifecycle route. There are no executable changes between the tested and final commits. Supplied validation records disclose the generated `config/reference.php` PHPDoc changes and restoration; these are not a retained runtime configuration change.

**Rerun here:** the public Python proof verifier passed against the supplied synthetic proof, verifying six frame hashes/chaining and four Ed25519 acts, with one native admission. This authenticates those synthetic public bytes under their supplied enrolled fixture key. It does not rerun PHP production behavior or authenticate an installation or historical producer.

**Inspected supplied results, not rerun here:** focused PHP **75 tests / 4,304 assertions**; full PHP **2,848 tests / 53,852 assertions**, no errors, failures or skips, with four documented historical warnings. JUnit agrees with the summary. Also inspected the supplied synthetic PHP proof, container-lint and 16-test Python compatibility results. PHP is unavailable in this reviewer environment. The supplied initial Python verifier import failure and successful rerun with the existing dependency path are disclosed and retained.

## Blocker NA-IR01 — admission overlooks retained legacy custody

Source: `src/Imperium/Runtime/Citadel/NativeAuthority/NativeProtocol.php`, `admit()` at line 213 and `inventory()` at lines 235 and 240. `NativeAdmission::build()` creates new native custody/disposition identities. The legacy canonical admission service persists custody in `var/imperium/offices/garrison/custody`.

Fresh native admission checks candidate uniqueness only against `$s['admissions']`. It does not inspect legacy custody. Native inventory, however, combines those native admissions with retained legacy custody and rejects duplicate `persona_id` values with `NAT040_CUSTODY_INVALID`.

Consequently, an installation containing a valid previously admitted legacy Persona can enroll the new protocol, adopt its original occupancy, receive a valid native revision, and admit that same Persona again. The new admission can succeed and commit a second custody record; the supported inventory route then refuses. The enrollment/adoption paths impose no empty-legacy-custody prerequisite. This requires neither a forged signature nor an administrator edit after enrollment.

This is a source-traced defect, **not a PHP reproduction run in this review**. The existing positive test starts without legacy custody, so it cannot establish correctness across this transition.

Required correction:

- Apply one consistent custody identity/conflict policy across native and retained legacy custody under the shared transaction lock, before creating any new admission effect.
- Preserve the original legacy records and their bytes. An existing completed legacy effect must not silently become a newly created native effect. Refusing a conflicting fresh admission is sufficient; do not invent an automatic migration or legacy recovery protocol.
- Add a positive fixture with retained legacy custody and a different new Persona: inventory must continue to return the supported records.
- Add an actual-consumer regression for the already-held Persona, including the same delivered package and a second delivery identity. Assert no additional admission/frame and unchanged original custody. Inventory must remain usable after refusal.
- Build the retained custody using the existing valid legacy admission path in a fresh synthetic root, then enroll and bind adoption/revision to that same exact original occupancy. Adjust fixture inputs where they currently assume the original occupancy's missing powers; do not substitute an unrelated record or write into a real installation.

## Blocker NA-IR02 — authority validation and retained acceptance use different times

Source: `NativeProtocol::apply()`, signature validation at line 57, result timestamp at line 79, retained act timestamp at line 82, and exact replay at line 54. `NativeTrust::verify()` requires the supplied validation time to precede decision/trust expiry, including historical verification. Admission has the same pattern: currentness/interval checks precede its fresh `accepted_at` read at line 217; replay checks that retained time against the authority witnesses at line 193.

`apply()` reads the clock independently for validation, the returned act and the saved act. A valid decision expiring at `T+1` can pass verification at `T`, then be committed with `accepted_at = T+1`. Exact replay verifies the same original signature at that retained time and refuses with `NAT013_EXACT_NATIVE_DECISION_REQUIRED`. The two retained timestamps can also disagree. The simplest deterministic counterexample is a valid `REVOKE_DECISION` for an unused target, with an injected clock returning `T` for validation and `T+1` for the subsequent acceptance reads.

Likewise, admission can pass the roster/revision interval checks before expiry and retain an acceptance time at or beyond expiry, causing its completed-effect recovery to fail with `NAT042_RETAINED_AUTHORITY_INVALID`. The common file lock prevents competing writers; it does not stop elapsed time.

This is a source-traced defect, **not a PHP reproduction run in this review**. Existing tests move a fixed fixture clock after completion and prove that case. They do not advance it between validation and acceptance.

Required correction:

- Define a consistent acceptance instant within the locked transaction and use it for the authority checks and every retained acceptance timestamp. Document that instant honestly. If the chosen contract instead requires revalidation at a later boundary, enforce that consistently before committing an effect.
- Keep completed-effect recovery based on the original authenticated acceptance evidence. Do not disable historical interval checks, extend signed expiry, grant replacement authority, or rerun an effect to make recovery succeed.
- Add deterministic advancing-clock tests for both signed act application and actual admission at decision, trust, roster and revision expiry boundaries as applicable. Verify refusal before any effect when authority is already expired at the defined acceptance instant; otherwise verify identical returned/retained acceptance evidence and exact replay without a new frame after expiry/revocation.
- Retain the existing competing-process, pending-unknown and after-commit interruption tests.

## Accepted evidence and remaining limits

The package makes substantive progress beyond the earlier refusal-only interfaces: separately enrolled synthetic Ed25519 trust; exact domain, instance, issuer, effect, object digest, nonce and interval binding; immutable native journal frames; explicit expected predecessors; decision/request replay checks; owner-attested adoption that does not claim historical producer authentication; two narrowly defined Garrison powers; and explicit legacy reader/writer fencing.

The new complete legacy admission controls correct the earlier A0 test qualification: a separate valid control admits, and each missing power or substituted request independently refuses. The old incomplete test remains historical evidence with its qualification.

The reported supported boundary is explicit. Enrollment fences substantial legacy work. Native inventory uses a new response version; legacy Guildhall transport, Garrison succession and broader lifecycle migration remain unsupported. The journal assumes protected local custody and cooperating current binaries; it does not promise administrator rollback resistance, directory-fsync durability or power-loss proof. Pending frames retain an unknown-outcome fence. These disclosed limits are not additional invented blockers.

Formation adapters and their trust remain unchanged. Native institutional authority is not formation suitability, appointment lineage or execution competence. Existing CF01 terminal refusal, CF02 authenticated completed child-publication recovery, IR01 interview closure, refusing transport and retained unknown-provider exposure remain accepted prior boundaries; no new PHP rerun of those claims is asserted here.

Preserve the formation baseline exactly: integration `645d53bdbb80d537ef0a7f226b8ad48f192ea1ef`, tree `ae5d701c1ef69cc6610306cfe40f0f518cb7e5cc`, original local review `21bb307d4a573980383aa0fbcd59cb1148efff0a`, historical **2,729 tests / 53,069 assertions** with four documented linked-worktree warnings. Preserve the later accepted campaign evidence as well.

Actual institutional policy approval, real issuer identities/keys/enrollment, factual adoption decisions, other formation Seats, formation-specific suitability/delegation, appointments, custody/trust and the remaining commissioning prerequisites are still unresolved. No live readiness or activation is established by this result.

## Next executable owner action — hand this document to Loco

Continue the existing local P0–P3 worktree. No pull from main is required to correct this reviewed local commit. Do not reset or discard newer local work.

```powershell
Set-Location 'E:\htdocs\imperium-citadel-native-authority'
$reviewHead = 'be09c33af8f28dccfe27df3bbeaf387f9afbf97d'
$reviewTree = '7c2aa9d0b2fcfe7859089845774d0dc368ab0b85'
$actualBranch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or $actualBranch -ne 'codex/citadel-native-authority-protocol') { throw 'Unexpected branch; preserve and reconcile it.' }
$actualHead = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $actualHead -ne $reviewHead) { throw 'HEAD differs from the reviewed commit; preserve and review the difference.' }
$actualTree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0 -or $actualTree -ne $reviewTree) { throw 'Unexpected source tree.' }
$trackedChanges = git status --porcelain --untracked-files=no
if ($LASTEXITCODE -ne 0 -or $trackedChanges) { throw 'Tracked changes exist; preserve and reconcile them.' }
git status --short
```

### Prompt for Loco

Continue Imperium in `E:\htdocs\imperium-citadel-native-authority`, branch `codex/citadel-native-authority-protocol`, from reviewed commit `be09c33af8f28dccfe27df3bbeaf387f9afbf97d`. Read this independent review, repository instructions, `contracts/native-institutional-authority-v1.md`, the native protocol campaign/source map/runbook, and current readiness/flow documents.

Complete only NA-IR01 and NA-IR02 within P0–P3. First establish meaningful synthetic failing regressions against the reviewed production paths, then correct the implementation and contract where necessary. Preserve exact legacy custody, narrow authority, shared locking, independent enrollment, signature/currentness/revocation checks, one-time consumption, pending unknown-outcome refusal and authenticated completed-effect recovery. Keep the earlier admission-control qualification and its new valid controls honest.

Update the native protocol report/runbook and current readiness/flow entries with the correction result and unchanged unresolved prerequisites. The flow remains: Citadel receives; Castellan interviews; “I understand” closes interview authority; separate explicit approval permits drafting; separate mission approval precedes legitimate child-Curia constitution and handoff; receiving assessment grants no execution authority.

Use generated fixtures and ephemeral in-memory test keys only. Do not read an installed private bootstrap state or journal, approve real policy, enroll real trust, change an installation, access provider credentials, call a provider or execute a mission. Do not broaden the campaign to unsupported lifecycle migration.

Run the focused regressions, existing protocol/admission and concurrency/interruption tests, container lint, public proof verifier, Python compatibility gate and the final full offline suite using the retained restricted harness. Commit the executable correction before final gates and identify its exact commit/tree and clean pre-test status. Preserve native exit codes, commands, environment names, UTC times, JUnit, warnings and any failed attempts. Reconcile generated reference changes explicitly. Any post-test executable change requires fresh affected gates and an honestly identified tested tree.

Produce a replacement review packet with the original evidence retained: exact tested/final source archives and manifests, bounded Git history, correction diff, post-test diff, public synthetic proof, test outputs and external SHA-256. Report the two regressions' before/after behavior separately from supplied historical results. Stop at local commits for independent review. Do not push, open a PR, merge or activate as part of this correction run.

*Hoc est pretium solitudinis. Imperium via solitaria est.*
