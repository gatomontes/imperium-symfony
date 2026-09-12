# O4-B0 completion — offline implementation delivered

Atomic assignment, persistent exact model settings, explicitly authorized whole-pair replacement, and all seven offline application/recovery proof rows are implemented. This is the continuation of the existing completion run, not another migration-only checkpoint. Integration still requires source review and fresh complete hosted O4 CI.

## Delivered behavior

CommandLedger publishes both role tuples, generations, original assessment view, receipt, completion and one-use authority/slot consumption in one FormationJournal commit. Selection uses real selected W1/W2/W3 originals, current semantic/Profile evidence and the unchanged typed capacity rule. Refusals publish nothing. Mode A uses the conditional policy slot; mode B requires a distinct signed APPLY act.

PersistentSettings returns exact retained identities and revalidates current use without changing settings. The explicit bounded transport consumer binds the full tuple/generation while preserving personnel, session, lease and dispatch authority. Signed operator replacement requires the current complete predecessor and another permitted eligible pair, consumes a fresh nonce, advances both generations atomically, and preserves initial and replacement replay. It never reopens the initial slot or substitutes a catalogue choice.

v4 migration and full history validation retain original signed/raw bytes and prior consumption. Private original verification is shared under the owning lock, without a nested public resolver call. R1 limits and R2 bounded pure parsing/canonicalization semantics remain unchanged. Read design.md in the package for closed schemas, source graphs, bounds, and exact public interfaces.

## Verification on PHP 8.4.14

| Final focused check | Result | Elapsed |
|---|---|---|
| owners-final | 2 tests / 89 assertions | 580.210 s |
| signed-boundary-final | 1 tests / 110 assertions | 666.268 s |
| selection-final | 5 tests / 23 assertions | 1029.436 s |
| compatibility-final | 563 tests / 2433 assertions | 1226.708 s |
| migration-legacy-final | 82 tests / 391 assertions | 386.694 s |
| inventories-final | 13 tests / 2288 assertions | 22.119 s |
| settings-rotation | PASS | 286.637 s |
| lint-final | PASS | 3.973 s |
| kernel-smoke | PASS | 0.720 s |
| dependencies | PASS | 1.861 s |

Counts are per selection and overlap; they are not added as unique-test totals. The supplemental settings-rotation PHP harness is preserved with its command, source and public log. It uses real synthetic producers and asserts unchanged journal state and zero additional HTTP. Static specification validation also passed all 86 checks. Changed PHP lint, locked Composer dependencies, non-debug Symfony test-kernel boot and source whitespace checks passed.

The exact unchanged full local command `php vendor/bin/phpunit tests` timed out at the unchanged 1800-second ceiling (exit 124; 1800.607 seconds). This is not a full pass. The command, complete public output, exit and before/after project PHP manifests are in `evidence/full-final.*`. No hosted O4 CI was run, and accepted historical O3 CI is not substituted for it.

Additional broad runs:

- `regressions`: exit 124, 1800.747 seconds, timeout=true. Not counted as an aggregate pass; focused selections above carry the completed proof.
- `o4-final`: exit 124, 1800.603 seconds, timeout=true. Not counted as an aggregate pass; focused selections above carry the completed proof.

The interrupted development runs are retained separately. They exposed test-harness request-head formatting and revocation-nonce mistakes; the harness was corrected without weakening production checks. The earlier successful process-development run proves only its original smaller case. Final component runs above carry the expanded contention/recovery proof. Earlier incomplete reports and their prior evidence remain historical and unchanged.

The broad `regressions` run began before three new O4 harness files were stabilized (AssignmentProcessTest, AssignmentRefusalTest and Support/AssignmentFixture). Its before/after manifest marks that difference; those files were outside its selected test list and production PHP was unchanged. It is not a final aggregate pass. The final focused selections and full-final run use the stable tested project PHP bytes. Supplemental execution harnesses are retained separately in evidence with manifest hashes; they are not counted as tracked project PHP or PHPUnit cases.

## Proof matrix and limits

The completion matrix maps seven rows to exact evidence labels: producer-backed application/restart; selection boundaries; authority/currentness refusals; replay/contention; publication interruption/recovery; persistence/change; migration/preservation/regressions. Contract views and receipts retain their exact bodies. Positive histories are produced through actual O1/O2/O3/O4 owners with temporary synthetic authority and MockHttpClient, never seeded positive claims or caller success booleans.

Process interruption is injected immediately before the fsynced temporary file rename or immediately after successful rename and before response. This does not claim arbitrary machine power-loss durability. The fixed production evidence ports default to refusal when genuine deployment evidence is absent. No service wiring is activated by this delivery. The local full timeout, if reported above, remains a release-gate limitation; it does not become a full-suite pass through focused tests.

## Source and handoff

Preparation baseline: f544b696a0ebdf8c25a71f3a369e5a93738074e9.
Submitted continuation entry: 4711ea5def32148335069f396fc86ab6abc82df4.
Clean resumed entry: 4098f5a2cf1ebfdba952b2c6ac37e2f9a7eb1e5f.
Tested PHP source: cbeb218fbdd02254816517b44fd6e87398be0616, tree 375dd0abdf98e4ae20b01149e56ed403547e53af.
Final documentation commit/tree are external in identities.json to avoid a self-reference. Final project PHP bytes must equal the tested snapshot.

Work remains on `codex/provider-onboarding-o4-b0-completion` in `E:/htdocs/imperium-onboarding-o4-b0-completion`. The original submitted worktree was not reset. The package includes canonical changed files, exact patches from all three declared baselines with reconstructed-tree verification, an incremental bundle requiring 4711ea5d, complete project PHP snapshots/hashes, protected-original comparisons, logs, an inner review ZIP/hash and verified manifests. It includes no historical ZIPs, vendor tree, runtime state, real credentials or outer-ZIP checksum.

No push, merge, real provider request, installed-state operation, live enrollment/appointment/activation, OAuth, existing-installation cutover, subagent or O5 work occurred. All five operational flags remain false and the actual retry allowlist remains empty. Stop for source review and fresh complete hosted O4 CI before integration.
