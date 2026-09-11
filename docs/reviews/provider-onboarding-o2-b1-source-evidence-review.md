# Provider Onboarding O2-B1 — attributed source/evidence review

**Verdict: HOLD. Do not merge O2-B1 or close O2 yet.** Three implementation defects require correction. The evidence package is internally consistent; that does not establish the missing runtime invariants. Draft [PR #790](https://github.com/gatomontes/imperium-symfony/pull/790) contains the exact submitted implementation and four added reviewer regression cases. No runtime fix, merge, activation or live operation was performed by this review.

CY/FC remain accepted within their original offline scope. O1 and O2-B0 acceptance/integration, plus accepted O2-B1 preparation, are preserved. O3–O5 and live commissioning remain deferred. The next action is one direct O2-B1 corrective implementation and validation run using the accompanying prompt, with no additional preparation batch.

## Concrete blockers

**R1 — recovered response is not bound to the exact prepared operation (high).** In `CustodyCoordinator::reconcile`, a response retained after the metadata checkpoint enters checkpoint 4. That checkpoint compares metadata, claim reference and response hash, but never compares the envelope's top-level `operation_digest` with the reserved operation or its metadata. `finish` does not close that gap. The reviewer case uses the real signed B0 fixture, interrupts after envelope retention, changes only the retained envelope's operation digest, then asks for evidence-only reconciliation. The required behavior is refusal with no new frame/settlement and maximum exposure retained. The implementation instead accepts the contradictory envelope and completes the claim. This is a missing generic attribution check at the response-custody boundary; a source-specific payload validator should not be assumed to supply it. It is not evidence of unauthorized access to the trusted aggregate or any real provider call.

Correction: validate exact closed envelope/metadata shapes and their mutual agreement with the immutable claim and prepared-operation identity before retention/recovery publication and settlement. Preserve original source validation and conservative exposure. Also align the checkpoint's declared operation identity: the current checkpoint hashes the `{record,prepared}` wrapper while dispatch metadata hashes the prepared operation itself. Distinct identities need explicit names/bindings rather than an unexplained shared field name.

**R2 — the v2 reader does not enforce its declared closed schemas and identities (high).** `LedgerState::validate` checks H hashes for budget-binding records without closing their body fields. It also accepts a correctly resealed migration receipt naming a foreign instance. These are two concrete failures of the promised v2 decoder, reproduced as adversarial input to the reader without inserting fake runtime authority. Other retained bodies and tuple links also receive partial validation: sequence-registration and source-fence bodies, nested claim/lease/operation fields, and several map-key/record relationships need a systematic check against the already accepted schema. `AuthorityStore::state` delegates to this validator; B0 compatibility and FC shared accounting depend on it. A valid hash proves neither the permitted schema nor membership in the enrolled instance.

Correction: enforce exact shapes, types, bounds, enrolled identity and original cross-links across the populated v2 maps, before reads and before writes publish. Keep valid historical migration checks distinct from comparison with legitimately evolved present maps. Preserve v1 behavior and the single journal lock. Do not weaken the contract to match the permissive decoder.

**R3 — resume returns the wrong advancing predecessor (medium).** `Recovery::resume` passes its own recognition command to `CommandLedger::presentation`, which sets `sequence_head` to that command's ref. The stored sequence head correctly remains the prior advancing command. The public resume result therefore disagrees with the ledger and can send a caller into `STALE_PREDECESSOR` when the caller uses the returned value. This affects fresh and duplicate resume presentations. It does not itself grant a new effect.

Correction: return the actual retained advancing sequence head under the owning lock, keeping the resume command's own immutable result ref separate. Test pending/completed originals and duplicate recognition after later progression. No new F1 fields or schema change is needed.

Four reviewer cases are in `source/tests/Imperium/Runtime/ProviderOnboardingLedgerReviewRegressionTest.php`. Preserve these requirements in the correction.

## Source and evidence identities

| State | Commit | Tree |
| --- | --- | --- |
| Integrated entry/main | `0399b581e8e61b5306813479df6a8af363c35c0a` | `bc010341a934706e2c75b8d8b6e203882473bedf` |
| Author final local PHP tests | `547a95ddca457faeb0834fd197803e20ba6f8c0b` | `d42404988a80e308c97039121e52bbb17ba7748f` |
| Author final submission | `c7b1576490996f83f97445f1c7f0eddd53d4260f` | `c987e25871e5003efd5aed6e358a534f219bbff0` |
| Exact submitted tree published | `74416e9266de8191127490d632059bd658b9b9b1` | `c987e25871e5003efd5aed6e358a534f219bbff0` |
| Local reviewer tests added | `2470a9cffbfbd144940bbabce804429e5a04b37d` | `f56c019688777745ae0b5a2e5d29f18dbcee432d` |
| Published reviewer candidate | `441fc9bb7003b62cb77bf61d82cc78485d55bf38` | `f56c019688777745ae0b5a2e5d29f18dbcee432d` |

Publication creates different commit identities with identical verified trees; it does not misrepresent the author's local commits as remote ancestors. Only one author file changed after the recorded local tests: `docs/handoffs/provider-onboarding-o2-b1-report.md`. Its exact post-test patch is verified. The reviewer then added only the four-case test file; original runtime and author test bytes remain unchanged.

## Checks rerun here versus evidence inspected

**Rerun locally by this reviewer:** safe ZIP paths/no symlinks/duplicate checks and CRC; inner SHA-256; exact inner/outer membership and byte equality; all 121 payload sizes/hashes; incremental Git bundle verification; entry/tested/final trees and ancestry; 38 canonical changed files and exact patches; all 1,796 tested PHP checkout byte streams versus Git blobs; all 3,277 protected original blob identities/hashes; original runtime-inventory prefix plus exactly one additive row; prior preparation report, integration identities, CI log and prompt equality; 203 changed-document links; `git diff --check`; all 86 Python specification checks.

**Inspected author local execution evidence:** PHP 8.4.14 / PHPUnit 13.3.0 and Composer 2.8.12. Final selected logs report 741 tests / 6,880 assertions, no skips, all exit 0: B1+B0 205/611; FC/native 140/760; O1/source pins 380/1,519; inventory 16/3,990. The package also reports 27 PHP lint results and a passing default kernel smoke. Final log hashes and exit sidecars match the identities. Exact tested-source capture supports their attribution. Development/superseded runs and inherited preparation CI are not added to those totals.

**PHP was not rerun in this local reviewer workspace:** PHP and Composer executables are unavailable here. Fresh GitHub CI execution is recorded separately below. The Python checks are static protocol examples, not runtime/custody or process-loss proof.

**Fresh full hosted executions triggered for this review:**

| Candidate | Run / job | Result |
| --- | --- | --- |
| Exact author tree, no reviewer additions | [34540121387](https://github.com/gatomontes/imperium-symfony/actions/runs/34540121387) / 103080668467 | PASS: 3,526 tests / 55,885 assertions / 4 skips; 11:24.552 |
| Same runtime plus four reviewer cases | [34540347005](https://github.com/gatomontes/imperium-symfony/actions/runs/34540347005) / 103081363869 | FAIL: 3,530 tests / 55,890 assertions / **4 failures** / 4 skips; 12:13.707; exit 1 |

Both used PHP 8.4.25 / PHPUnit 13.3.0. Exactly the four added cases failed; their assertion messages confirm R1, R3 and the two R2 examples. Existing author tests were not weakened or replaced. The first green run does not establish the newly tested invariants.

Author CI tested temporary PR merge `6ab4e594b5ace1d6c627d309b60359e83d474b04`; reviewer CI tested `4d7fc59b7aca4d9db5d0f1ebc06a3d83970bf22e`. Both merge objects were fetched and their trees/parents verified against the exact published head and unchanged main. These temporary test merges are not integration into main. Full job-log SHA-256 values: author `c1343fcc1d768e0588cdfcd23848e3bc8507cdf0dce77a3c3866b652f678ff4f`; reviewer `dc30be6c646bbb0572a84d01b4482e11bc4eb535caa6688da2d2bbc17006d284`. No source changed after the reviewer CI candidate. Reports, evidence and packaging were completed afterward.

## Authority, provider and consent assessment

The implementation uses the existing FormationJournal and its owning `changeAtHead` lock. Registration, advancing command, step/slot/S-or-P consumption, claim maximum and uncertainty fence are assembled in one publication. The actual FC reservation and claim-custody consumers now call shared exposure validation; this is not merely a parallel test-only accounting entry point. Pre-v2 FC behavior, original journal, SessionExposure, services configuration, dependencies, O1 runtime and existing tests are byte-preserved. This is favorable inspected structure, not blanket acceptance of the incomplete v2 reader.

The public B0 resolver retains dynamic refusal while an internal no-lock verifier emits facts and obligations. B1 handles supported mechanical evidence/mapping completion and fixed custody ports. Default ports refuse absent compatible producers. Infrastructure access remains distinct from Augur assessment authority; later founding/holder/assignment producers are absent. Synthetic signed access/FC fixtures do not establish a complete founding or W1–W3 journey. There is no new live authentication adapter or OAuth route in this batch.

The local process tests cover checkpoint interruption, one-use delivery and actual FC/onboarding contention. They do not prove remote cancellation, no billing after timeout, or power-loss durability. R1 concerns the original-evidence recovery boundary itself. The actual safe-retry allowlist stays empty; three retries remains a maximum of four predeclared attempts per workload, not enabled automatic retries.

DeepSeek/API key, FRESH, D2-A, fixed P1–P9 limits, medium target-role selection, least-cost eligible initial Augur base, persistent operator-controlled assignments, DEFER_ENROLLMENT and unresolved remote guarantees are unchanged. All five operational flags remain false. No provider or credential access, installed-state mutation, enrollment, appointment, assignment, activation or execution occurred here. Prior offline acceptance is not reopened by this hold.

## Provenance and next executable action

This is an **attributed source/evidence review**, not a claim of disjoint authorship: this reviewer previously contributed preparation clarifications and now authored the regression requirements. The author submission and original evidence remain preserved separately.

Use `instructions.md` to create an isolated correction worktree from published candidate `441fc9bb7003b62cb77bf61d82cc78485d55bf38`, then run `local-codex-prompt.txt`. Correct R1–R3, retain the four reviewer cases, run the relevant gates on exact committed PHP, and return one complete correction ZIP. Keep PR #790 draft and O2 open until source review and fresh full CI pass. O3 is not the next action yet.
