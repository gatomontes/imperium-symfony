# O2-B1 preparation and O2-B0 closeout report

Disposition: O2-B1 contract and implementation handoff prepared locally; stop for preparation/source review before runtime implementation. O2-B0 is accepted within its offline scope. Five implementation batches remain across O2-O5; O2 remains open for B1.

## Entry and provenance

Actual fetched main/worktree entry: cafa93f9665f0e5734f26491a092b28995e1cf14; tree e2070c7a2184c7481882f27297c976d248b2c2be. Verified corrected published commit 4001f08d343d289cecb913633961bd90b7797fcf is an ancestor. Worktree: E:/htdocs/imperium-onboarding-o2-b1-preparation, branch codex/provider-onboarding-o2-b1-preparation. Existing worktrees preserved. No applicable AGENTS.md was found in this worktree or its ancestors; no subagents used.

The supplied correction review and integration JSON are retained byte-for-byte as new provenance. Its CRC, complete manifest, unique safe paths, inner ZIP checksum/equality and CI-log hash were verified. Source exports match entry Git blobs. PR #788's recorded successful hosted run 34522234611/job 103022324183 used PHP 8.4.25 / PHPUnit 13.3.0: 3,463 tests / 55,572 assertions / four skips. This is inherited reviewed CI, not a new PHP run. Reviewer authorship qualifications remain explicit. Prior author/HOLD reports and signed/approved source bytes are unchanged.

## Prepared deliverables

- [Implementation contract](../../contracts/provider-onboarding-ledger-custody.md): explicit v1-to-v2 migration, compatible B0 interfaces, internal no-lock current verification, exact F1 keys and replay, S/P step/slot consumption, finite readiness, bounded claims, shared budget/source fences, custody states and evidence-only recovery.
- [Surface and test matrix](../provider-onboarding-ledger-custody-surfaces.md): exact existing/new runtime paths and required adversarial, shared FC contention, process-loss, B0/O1/source-pin/inventory regression gates.
- [Campaign](../next-campaign-provider-onboarding-o2-ledger-custody.md) and [local launch](provider-onboarding-o2-ledger-custody-ready.md): bounded implementation prompt after preparation review.
- Current status, README, steps/flow and roadmap now record reviewed/integrated O2-B0, five remaining batches and preparation-only next action. Complete earlier status/flow text remains marked historical.

## Actual implementation observations

FormationJournal::changeAtHead already provides mutable state and predecessor head under the owning lock; no journal redesign is proposed. Public Resolver::current/original acquire inspect, so calling them inside reservation would nest the lock. The contract extracts internal verification and preserves public B0 static-only behavior. AuthorityStore currently requires v1's exact seven keys; migration and dual-version validation are explicit, with no automatic enrollment or read-time migration.

FormationCognition reserves through per-session SessionExposure; FormationClaimCustodyBroker rechecks that accounting during custody. Neither establishes a D2 global budget merely by sharing a journal. The contract therefore requires shared exposure checks at both genuine FC and onboarding mutation paths with verified canonical source associations, conservative refusal for unknown linkage, no second claim ledger and no modification of original FC schemas. Bootstrap zero-fee access uses a separate nonnegative meter validator; existing positive FC maxima are preserved.

The actual B0 Admission, Act, Resolver, AuthorityStore and original/correction/process tests were inspected, together with FormationJournal, FormationCognition, SessionExposure and formation custody/process tests. The preparation preserves R1-R4 requirements and distinguishes immutable original admission from progressing completion. Required interruption proof is process-level; no power-loss or remote-cancellation guarantee is invented.

## Validation and identity

All 86 existing Python specification checks pass. They check existing abstract policy/specification consistency, not this new ledger implementation. Document links, new provenance equality, changed-path scope, unchanged protected tracked blobs, whitespace diff and Git/package identities are checked separately; raw logs and scripts are in the delivery evidence directory. No PHP tests, dependency installation, kernel boot, provider research or hosted CI were run in this documentation-only pass. No B1 runtime test is claimed. The final committed tree is also the static-check tested tree; source-identities.json records full entry/tested/final identities and canonical changed-file hashes. There is no post-test source change; post-test.patch is empty.

## Retained decisions and limits

DeepSeek/API-key, FRESH, D2-A, P1-P9, medium target capacity, least-cost eligible initial base, empty actual safe-retry allowlist and persistent operator control remain fixed. No owner decisions reopened. CY/FC/O1 acceptance remains unchanged. DEFER_ENROLLMENT and unresolved B1 remote guarantees persist; deployment_approved, enrollment_authorized, live_ready, activation and execution_authority remain false.

No runtime code, tests, dependency locks, service wiring, historical inventory, original policies/workloads or old reports changed. No credentials, provider requests, installed state, real enrollment, appointment, founding, assignment, activation, later batch, push or merge. Missing genuine dynamic/custody sources are explicit future refusals, not placeholders promoted into authority.

## Review handoff

Review the contract's migration/interface choices, exact command/consumption identities, budget association and FC participation, custody currentness/recovery, effect boundaries and adversarial gates. Preparation acceptance precedes O2-B1 implementation. Delivery includes every changed document, report/instructions, public evidence and relevant original review/CI identity, patch, Git bundle, complete payload manifest and checksummed inner review ZIP. Historical convenience archives are not recursively duplicated. The outer ZIP intentionally has no checksum sidecar.
