# Provider onboarding O2-B1 local implementation

The dormant ledger/custody boundary is implemented locally. Source review, fresh full hosted CI and integration are pending; O2 is not accepted closed. No push, merge, commissioning, installed-state mutation, provider request or real credential access was performed.

Entry was clean main commit `0399b581e8e61b5306813479df6a8af363c35c0a`, tree `bc010341a934706e2c75b8d8b6e203882473bedf`, in the preserved isolated worktree `E:/htdocs/imperium-onboarding-o2-b1`, branch `codex/provider-onboarding-o2-b1`. The reviewed preparation ancestor is `770dfddb07ee221a58c24d8ebf927470d1db61f2`. Original earlier worktrees remain intact.

## Implemented behavior

- Explicit idempotent v1-to-v2 migration preserves B0 originals and the sole FormationJournal. Strict readers reject unknown state; no read-time migration or second journal exists.
- The internal no-lock current verifier supplies static facts and closed obligations. Public B0 retains conservative refusal. B1 resolves supported original completions within the owning reservation transaction.
- F1 command recognition, logical sequence registration, predecessor control and global step/slot/S/P consumption share one atomic transition. Historical recognition precedes currentness and never redispatches.
- Shared accounting enters the actual FC reservation and custody paths. Signed original budget association, source identity, currentness, typed settled/maximum exposure and concurrency are checked under the same journal lock. Unmapped v2 sources refuse. Existing pre-v2 limits and validators are preserved.
- Custody commits monotone uncertainty checkpoints before external issue, consume and dispatch. Fixed ports validate exact operation and original response. Completion and settlement publish atomically. Restart/resume only recognize evidence, including after a RESERVED process loss; they never issue or dispatch.
- Finite group readiness and outcome interpretation keep the actual retry allowlist empty. Default adapter, holder and base-projection producers refuse when absent; B1 manufactures no O3/O4 authority.

The [input shapes](../provider-onboarding-ledger-custody-input.md) document the internal schemas and narrow constructor/source-association refinement. Claim reservation is implemented in CommandLedger; no separate ClaimCoordinator is needed.

## Evidence and limitations

Final PHP tests ran on commit `547a95ddca457faeb0834fd197803e20ba6f8c0b`, tree `d42404988a80e308c97039121e52bbb17ba7748f`, with PHP 8.4.14 / PHPUnit 13.3.0 and locked Composer 2.8.12 dependencies. All 1,796 tracked PHP checkout byte streams match their canonical Git blobs exactly; none required normalization. Exact copies and per-file SHA-256 comparisons accompany the package. The final commit contains only documentation after this tested source; its identity and tree are in the package identities.json, with an exact post-tested-documentation.patch.

| Final selection | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| New B1 and all four unchanged B0 suites | 205 | 611 | PASS, exit 0 |
| FC custody/process, mission/correction and native protocol | 140 | 760 | PASS, exit 0 |
| O1 selection/response/base and three source-pin suites | 380 | 1519 | PASS, exit 0 |
| Transactional coverage and all frozen inventory tripwires | 16 | 3990 | PASS, exit 0 |

Final selections total **741 tests / 6,880 assertions**, no skips. In addition, all 27 changed/new PHP files linted, the default kernel boot passed and all 86 Python specification checks passed (each exit 0). Exact commands, logs and exit-code sidecars are retained in evidence/. Network functions were disabled for final PHPUnit and process workers.

The protected-file audit found 3,277 unchanged original files. Existing tests, fixtures, approved workload/policy sources, FormationJournal, SessionExposure, O1 runtime, service configuration and dependency locks remain unchanged. Four original runtime files are intentionally changed: AuthorityStore, Resolver, FormationCognition and FormationClaimCustodyBroker. The inventory retains its complete original byte prefix and adds exactly one actual CommandLedger candidate. Attributed preparation review and integration metadata copies are byte-exact; changed Markdown links resolve.

Development and superseded runs are excluded from those totals. Development shared-fixture runs initially failed resource bounds and were corrected in the new fixture; the original FC fixtures were not weakened. One development association invocation omitted the test directory and printed PHPUnit usage, so it is not a test result. The corrected focused invocation passed 8 tests / 20 assertions. The first committed candidate `4e2e63f2318419e7c831009bff34012e594cf9c6`, tree `bafde770a372cd8e20508ee1613315df9d210946`, passed its selected checks but was superseded by the original-budget currentness refinement and actual cross-type contention test; pre-association logs and exact 1,795-file source capture preserve that attribution. No incomplete or interrupted test is included as a pass.

Inherited preparation CI run 34525838331 / job 103034390826 passed 3,463 tests / 55,570 assertions / four skips on PHP 8.4.25 / PHPUnit 13.3.0. Its retained log SHA-256 is `3f0328d19cac9ed99edc8605e2da8a3b07224d41021f727744135dacab6356f7`. This is preparation provenance, not a fresh full-CI result for B1.

## Focused evidence boundaries

- Ledger tests exercise explicit migration and historical recognition, real signed/policy consumption, completed signed-prerequisite revocation, frozen request/head/sequence conflicts, missing dynamic evidence, default refusals and a new policy failing to escape an original unknown fence.
- Shared tests invoke real FC reservation/custody in both directions, settled usage, original-policy revocation, unmapped session refusal, strict zero-fee separation and simultaneous real FC/onboarding workers competing for one available call.
- Custody/process tests exercise one-use completion, callback replay, fake consume return, changed prepared wire, half-open lease expiry, policy/expiry changes across stages, 11 abrupt-exit windows, duplicate workers and signed revocation barriers. Counts prove no restart reissue or redispatch; validated retained evidence alone permits settlement.
- Unchanged B0 and FC regressions retain their original acceptance requirements and adversarial cases. The new group interpreter covers finite ordering, missing results and the actual empty retry refusal. Positive later founding/assessment/assignment execution is deliberately not claimed: the required compatible original producers are absent and production defaults refuse. No hypothetical safe-retry allowlist is enabled.


Positive synthetic access custody and actual FC competition use the real signed B0/FC producers in temporary roots. Process barriers and abrupt exits prove local process-loss behavior, not remote cancellation or power-loss durability. Group-interpreter refusal checks do not claim a completed positive founding/assessment journey: compatible later producers remain absent. Full hosted CI remains the integration gate.

DeepSeek/API key, FRESH, D2-A, P1â€“P9, medium target capacity, least-cost eligible initial base, persistent operator control, DEFER_ENROLLMENT and unresolved remote guarantees are preserved. All five authority flags remain false. No O3 adapter/founding, O4 assignments, O5 CLI, operational wiring, enrollment, appointment or activation was added.

Review the changed source, tests, original preparation review, protected hashes and evidence manifest. Run fresh full CI on the proposed integration state before deciding acceptance. Stop within O2-B1; this report authorizes no later batch or live work.
