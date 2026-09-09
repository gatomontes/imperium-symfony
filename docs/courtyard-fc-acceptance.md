# Current Courtyard and formation-custody acceptance

Status: **CY_FC_ACCEPTED_LOCAL_OFFLINE_SOFTWARE_SCOPE**.
This addendum records the supplied independent reviews; it is not a new independent
assessment by the publishing executor. It supersedes pending-review wording in
the earlier submission reports for current status only. The original reports,
contracts, packet manifests, signed evidence and submitted source remain historical
records and are not rewritten.

## Accepted identities and review provenance

| Campaign | Accepted final commit | Accepted tree | Independent review |
| --- | --- | --- | --- |
| Courtyard CY0–CY3 | `8f5e2052bf99b747f51320dfce2567d31d6fd0da` | `55b100f65ac30ed2d1ea92261de14bea8f09c831` | [CY review and revised O0](reviews/courtyard-independent-review-and-revised-o0.md) |
| Formation custody FC0–FC3 | `daafb83f45978f1d203c9cba693bfaf96a1cd4ab` | `3826740810f42c90e6d8b6e00686947003ab62c4` | [FC independent review](reviews/citadel-formation-custody-independent-review.md) |

Both supplied review files are retained byte-for-byte as UTF-8 source evidence:

| Review | Bytes | SHA-256 |
| --- | --- | --- |
| CY | 22469 | `ff7e7798ff30b62d55f51f3d6a6ad426c3793e9320254c63e486048a3ba83447` |
| FC | 13875 | `a917055410343490f400db124e3c5e0c7d40aba3d1dc5b39be63f21013cc0e0a` |

The reviews were supplied at the corresponding filenames under
`E:/htdocs/imperium/`. Only those explicit review documents were read there;
installed configuration, journals, credentials and runtime state were not inputs.
The accepted Git commit/tree identities were checked locally. FC's tree is also
the integrated PR #778 baseline. CY entry was preparation merge
`b1a8378668ecd9f4e4c1502411a2713111915535`, tree
`eae698493f2f679774c7e91975e05bbaf4cae039`.

The CY reviewer executed packet/source/Git comparisons, Python checks and public
cryptographic/integrity verification. The FC reviewer likewise verified its
packet and public evidence. Both inspected supplied PHP results rather than
rerunning PHP in their review environment. The [CY submission report](courtyard-identity-report.md)
and [FC submission report](citadel-formation-claim-custody-report.md) retain their
original test attribution and then-pending review status. No PHP rerun is required
solely to reconcile this documentation.

## Publication history and completed integration

The exact accepted CY branch `codex/courtyard-identity-mission-formation` is
published at `8f5e2052bf99b747f51320dfce2567d31d6fd0da` and submitted as
[PR #780](https://github.com/gatomontes/imperium-symfony/pull/780) against `main`.
At publication the PR base was the exact preparation entry above, with no
intervening base change. Branch, head, tree, clean status and origin were checked
before the non-forced push; the remote branch and PR head matched afterward.

The owner subsequently authorized integration. PR #780 was merged as
`79282773b0c1ca93aa17303d46e784b5415cad7e`. The resulting main tree was verified as
`55b100f65ac30ed2d1ea92261de14bea8f09c831`, exactly the accepted CY tree. The
expected-head guard used accepted head `8f5e2052bf99b747f51320dfce2567d31d6fd0da`.
This documentation closeout starts from that verified integration commit; it does
not change the accepted executable source or amend either historical submission.

## Remote CI verified on the accepted CY source

[GitHub Actions run 34392793041](https://github.com/gatomontes/imperium-symfony/actions/runs/34392793041),
job `102605006758`, completed successfully on 2026-09-09. The job ran from
19:03:48Z to 19:11:53Z; the full-suite step ran from 19:04:04Z to 19:11:50Z.
PHP 8.4.25 / PHPUnit 13.3.0 reported **2,956 tests, 54,433 assertions, four skips**,
with no reported test failures, errors or PHP warnings.

The run identifies accepted head `8f5e2052bf99b747f51320dfce2567d31d6fd0da`.
Checkout used GitHub's temporary PR merge
`4cf53bdadd30a7d6230da9d1c55a75b77b2109da`, whose parents are the preparation
base and accepted CY head. Its verified tree is exactly
`55b100f65ac30ed2d1ea92261de14bea8f09c831`. This synthetic CI merge does not mean
the PR was integrated into main. The remote head/tree and all 58 changed paths
were compared with the accepted local source. After that CI run, PR #780 was open and mergeable with check status SUCCESS.
The subsequent authorized merge is recorded above; an independent chat review
should not be confused with a GitHub review-approval event.

The four CI skip ordinals correlate with these unchanged Windows-only guards in
the supplied local JUnit order, and each guard was inspected:

- LocalIsolationProcessTest::testWindowsModulePollutionAtBothRealLaunchBoundaries;
- LocalIsolationReadinessTest::testNativeHandlesAndExactStartupRefusal;
- LocalIsolationReadinessTest::testCompleteReadinessAndAdversarialEvidenceThroughPowerShell;
- LocalIsolationScratchTest::testNativeWorkspacePolicyAndCleanupFailure.

The original Windows gate has zero skips and 54,448 assertions. These remain
separate platform results. GitHub also reported an infrastructure annotation:
`actions/checkout@v4` targets deprecated Node 20 and ran under Node 24. No workflow
or dependency update was made to remove that annotation.

The retrieved run log is retained locally at
`E:/htdocs/imperium-courtyard-identity/var/courtyard-publication-20260909/run.log`,
SHA-256 `6f59bc5d01c7daa15a4751aebe0ac79d9b91d0fc006e7d05d8cc3ec63c5d95bb`.
Adjacent run/PR metadata, merge identity and skip-correlation records retain the
verification evidence. The publication report records Markdown-only and local-link checks for its
then-local addendum. This new closeout separately verifies its changed links,
Markdown-only scope, preserved historical bodies and exact review-copy bytes. The remote CI result belongs to the accepted CY head, not this
separate documentation commit. No local PHP rerun was performed for these status
and pointer changes.

## Continuing limits and revised O0

Citadel remains the enclosing jurisdiction. Courtyard receives/forms missions;
Courtthane is the exact `courtyard.courtthane` LEGATE. Castellan oversight stays
deferred. Seneschal retains its child-Curia mandate. Understanding, exact drafting
approval, mission approval, constitution/appointments, receiving assessment and
execution remain separate gates. No old Castellan evidence becomes fresh Courtthane
or oversight authority.

Acceptance is local/offline software acceptance using synthetic infrastructure,
trusted storage and cooperating locks. It does not prove genuine institutional
competence or appointments, installed trust/custody, rollback resistance,
power-loss durability, provider billing/cancellation or live readiness.
`DEFER_ENROLLMENT` remains selected; B1's remote guarantees remain unresolved.
`deployment_approved`, `enrollment_authorized`, `live_ready`, `activation` and
`execution_authority` remain false.

The owner has now selected **O0 only** for the next local onboarding chat.
See [the current campaign](next-campaign-provider-onboarding-o0.md) and
[local handoff](handoffs/provider-onboarding-o0-ready.md). This lifts the previous
deferral for contract/prerequisite preparation only. O1–O5 implementation and all
live commissioning remain deferred. No provider has been selected.

The [reviewed O0 outline](reviews/courtyard-independent-review-and-revised-o0.md#revised-o0--contracts-and-authority-map-after-courtyard)
remains an unchanged historical review document; the current campaign supersedes
its deferral status only as stated above. Provider/authentication choice, the
founding-authority graph, reproducible base-model policy and persistent-consent
rules must be resolved before implementation. A future run records its actual
entry commit/tree and accounts for any intervening changes.
