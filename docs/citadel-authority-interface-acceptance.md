# Citadel authority interfaces — independent review

Date: 2026-09-08. Repository: `gatomontes/imperium-symfony`. Campaign: Recruiter currentness and Garrison authority interfaces, A0–A3.

## Verdict

**ACCEPTED within the implemented preparation/refusal scope, with one nonblocking test-evidence qualification.** No blocking authority bypass or source-integrity defect was found in the supplied candidate. No corrective campaign is warranted for this accepted scope.

The implementation does not complete public authenticated Recruiter currentness or a legitimate Garrison authority revision. It implements the campaign's expressly permitted fallback: usable projection/inspection and unsigned preparation interfaces, explicit missing issuer/currentness prerequisites, and a permanently refusing revision/resolution path. It must not be described as live commissioning or a successful institutional act.

## Exact accepted source

| Item | Verified identity |
| --- | --- |
| Campaign entry | `408597e3d4828e8cdb1f623d0eb0ec8215972830` |
| Entry tree | `7db7d78dc6201667e094e4e0f02a5f0ef3f3c01d` |
| First executable checkpoint | `2e51af29339bdf429d50e39b2a510f5bba4ab197` |
| First checkpoint tree | `2401ecce92141ad9c3c93240881313e9e23c5b3c` |
| Final tested commit | `49021008edd947a3e32171abc328bdc1cf97460a` |
| Final tested tree | `68d40a0fa3da307cd35d0c57a8cbc28a6a41e97a` |
| Accepted final commit | `7cee7f8c1b67154ae871ab1b2a9648d116ed48a1` |
| Accepted final tree | `986f4c12f7c7aa757ef17af58051730cf460ecb7` |
| Local branch | `codex/citadel-recruiter-garrison-authority-interfaces` |

Archive SHA-256 matches the separately supplied checksum:

`f5762bbd168ada2e65e9dfbf3a9f8cc8c617a305da93d94060cc1726f3c0d255`

All 82 manifest payloads verify, with the exact archive payload path set. The bounded bundle imports against retained prerequisites `158aee518f840090a4910ae6a38bfac66ecbda6c` and `c7b3274dfcbdaac31b2367736a779cd50ec3ba86`. The three campaign commits and entry ancestry are present.

Independently checked every source archive entry against its manifest and Git tree: 3,082 blobs at the first checkpoint, 3,082 at the final tested checkpoint, and 3,083 at final closeout. Path sets, modes, Git blob identities, byte counts, per-file SHA-256 and archive digests match. The supplied post-test diff exactly matches Git and changes only five Markdown files.

The transition from the first checkpoint to the final tested commit adds one runtime-inventory row and its explanation. Production PHP and tests are unchanged between those checkpoints. All existing Bootstrap, Conscription, Garrison and formation source, existing PHP tests, configuration, dependency files and public Python collector remain unchanged through the final commit. The new command is discoverable through existing Symfony autowiring; unchanged configuration does not mean the new command is absent.

## Source findings and accepted behavior

`src/Command/CitadelAuthorityCommand.php` exposes six explicit modes. Complete preparation/structural inspection/refusing assessment returns exit 2; malformed or unsupported input returns exit 1 with a fixed public error and false readiness/activation/execution flags. No mode returns authority approval.

`Citadel/Authority/RecruiterEvidence.php` reads bounded bootstrap state under the existing `StateStore` cooperating-writer lock. It validates the supported historical T03/T04 shape, identities, generations, consumed commission and qualification digest, then emits a distinct allowlisted projection. Whole private state and arbitrary extra fields are excluded. `source_original_digest` remains null; authenticated provenance/currentness remain unavailable. A fresh comparison establishes equality at a local observation boundary, not legitimate incumbency or trust.

The historical MasterMason producer is retired, and the traced source does not establish a public native revocation/supersession authority. The implementation correctly declines to invent one. The lock may create its directory/file and does not constrain writers that ignore the lock; this footprint and limitation are disclosed. Actual private-state export remains a later owner-authorized operation.

`Citadel/Authority/GarrisonAuthorityRequest.php` binds an unsigned request to the supplied occupancy digest, instance, actor/generation, prior-revision reference or unknown null, two exact power scopes, time terms and replay nonce. Existing observed powers remain true/false/null. Preparation writes no effective revision, consumes no authority and does not rewrite an occupancy. Incoming keys/signatures cannot establish their own issuer competence. `verifyRevision()` always returns refusal; `resolveForAdmission()` never returns an effective actor and throws `CAI036_AUTHENTIC_NATIVE_REVISION_RESOLVER_UNAVAILABLE`.

`Citadel/Authority/AuthorityInput.php` bounds explicit input size/depth, rejects duplicate JSON keys, wrappers/UNC paths and final-file symlinks, and supports fixed public errors. Its filesystem checks assume custodian-controlled parents; they are not the Python collector's Windows final-handle boundary. The public collector itself is byte-identical to the accepted version.

No positive native witness is added to FormationInstitution, FormationPersonnel or retained FormationPublicationEvidence. CF01 terminal refusal, CF02 authentic completed-publication recognition after expiry without renewed authority/repeated effect, and IR01 understanding closure remain preserved. This review establishes source preservation and inspects supplied regression results; it does not claim a fresh PHP execution here.

## Tests and evidence attribution

**Performed here:** outer checksum and complete manifest verification; bounded Git import/ancestry and three complete source/archive comparisons; exact post-test diff and unchanged-boundary checks; source/consumer/test/harness tracing; JUnit and command metadata inspection; full warning-block comparison against the previously supplied native-lineage output; execution of the inspected standard-library `verify-synthetic-proof.py` over the supplied public synthetic output.

That helper reproduced projection/request and qualification digests, reference bindings, all six mode identities/exits and false authority flags. This is a rerun of a supplied verification script over supplied bytes, not a new execution of the PHP commands. It cannot independently prove that no private state or real key was used during the reported Windows run.

**Supplied results inspected, not rerun here:**

| Gate | Supplied result |
| --- | --- |
| Final focused PHP | 37 tests / 4,109 assertions; no errors/failures/skips; exit 0 |
| Final full PHP | 2,810 tests / 53,495 assertions; no errors/failures/skips; exit 0; four historical warnings |
| New interface tests | 21 cases / 149 assertions in reported development results; included in final gates |
| Python compatibility | 16 unchanged tests; native exit 0 |
| Synthetic command/DI proof | All six modes return expected exit 2; proof process exits 0 |
| Symfony container lint | Native exit 0 |

Final full run: 2026-09-08 16:12:19.7969466Z–16:26:06.9443003Z, 827.147 seconds, at `49021008`. The warning block matches the accepted native-lineage run after only worktree-root/newline normalization. Its historical copy also matches the earlier supplied output bytes.

The initial full run's two inventory failures are preserved. The additive inventory classification describes an always-false `authority_consumed` output; discovery predicates, frozen snapshots and assertions were not weakened. The final full rerun closes that gate. Generated `config/reference.php` PHPDoc changes are disclosed and restored; final Git configuration matches entry.

The adverse suite covers contradictory/missing succession, tampering, stale projection, borrowed provenance, incoming untrusted signatures, overbroad requests, expiry and refusal/no-effect behavior. Sibling PHP process tests support cooperating-writer exclusion, committed-generation observation and lock recovery after writer interruption. There is no mutable authority transition, so positive revision/revocation/consumption races are not proved or accepted. The documented process restrictions are not an OS network sandbox.

PHP is unavailable in this reviewer environment. No PHP suite, Symfony lint, live Windows collection or new Python unit-suite run is claimed here.

## Nonblocking qualification: admission-test isolation

In `tests/Imperium/Runtime/CitadelAuthorityInterfacesTest.php::testActualAdmissionCannotConsumeRequestWhileInventoryRetainsOriginalPower`, the delivery constructed at lines 123–124 omits `senate_confirmation_record_id` and `originating_guildhall_commission_id`. The unchanged `SubordinatePersonaCanonicalAdmissionService::admit()` also requires both to be strings in its initial GA87 guard.

Therefore that test would still refuse at GA87 even if the two admission/custody power predicates were removed. Its observed no-write result does not isolate the missing-powers guard. Interpret it as refusal of the supplied incomplete delivery/old-occupancy fixture, not independent proof that the two powers alone determine the outcome. The same incomplete delivery also limits the admission-side request-as-occupancy check. The separate inventory success/refusal assertions and original-byte preservation checks remain useful.

This is not a demonstrated production bypass. The consumer is unchanged, the new revision verifier has no positive branch or persistence, and its admission resolver always throws. Acceptance is consequently limited to preparation/permanent refusal. Before claiming isolated admission-boundary coverage or admitting any positive revision, use a complete otherwise-valid delivery, establish a synthetic valid control, then vary the powers and occupant representation independently. No broad remediation campaign is required for the current no-effect scope.

## Remaining blockers and next owner action

Authentic native Recruiter provenance/currentness; a competent Garrison revision issuer and external trust path; current revision/revocation closure and atomic consumption/effect protocol remain unimplemented prerequisites. Other formation Seat witnesses, genuine formation suitability/delegation, custody/trust, candidates/appointments and B1 remain unresolved. B1 still needs exact payload/destination/model/limits/authority and honest timeout/cost guarantees. Unknown outcomes retain exposure without retry/refund; default transport refuses.

The implemented owner runbook contains real preparation/inspection syntax and deliberately invalid null templates. Its private exporter/compare modes require separate authorization and installation. No signing, enrollment, positive mutation or deployment command exists to run by inference. Do not read installed private state to compensate for absent authority, and do not repeat the completed Guildhall acceptance lookup.

**Next executable owner action is publication of the accepted local source for GitHub integration**, carrying this review's scope and qualification into the PR. It is not commissioning. If publishing now, verify the exact local source before pushing:

```powershell
Set-Location E:\htdocs\imperium-citadel-authority
$acceptedHead = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $acceptedHead.Trim() -ne '7cee7f8c1b67154ae871ab1b2a9648d116ed48a1') { throw 'Source differs from the independently reviewed commit.' }
$acceptedTree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0 -or $acceptedTree.Trim() -ne '986f4c12f7c7aa757ef17af58051730cf460ecb7') { throw 'Tree differs from the independently reviewed tree.' }
$trackedChanges = git status --porcelain --untracked-files=no
if ($LASTEXITCODE -ne 0 -or $trackedChanges) { throw 'Preserve and review tracked changes before publishing.' }
git push -u origin HEAD:refs/heads/codex/citadel-recruiter-garrison-authority-interfaces
```

Return the push result for remote identity/CI verification. No GitHub push, PR, merge, installation update or activation was performed by this review.

*Hoc est pretium solitudinis.*
