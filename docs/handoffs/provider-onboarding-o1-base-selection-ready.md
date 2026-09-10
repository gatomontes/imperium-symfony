# Local launch — O1-B2 base selection

The selected next implementation is O1-B2 only. Read the
[campaign](../next-campaign-provider-onboarding-o1-base-selection.md),
[contract](../../contracts/provider-onboarding-base-selection.md),
[roadmap](../provider-onboarding-implementation-roadmap.md) and every required source
they name. O1-B1 is already reviewed and integrated; preserve its source/evidence.

The preparation is committed locally on
`codex/provider-onboarding-o1-b2-preparation`, in
`E:/htdocs/imperium-onboarding-o1-b2-preparation`. From an existing source clone,
choose that exact local preparation ref, or a verified main that contains it after
a separate integration. Do not assume fetching main publishes the preparation.

```powershell
git fetch origin main
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed' }
git merge-base --is-ancestor cc9a1882e88de55412d1428e2a01e5ac85bb92c5 origin/main
if ($LASTEXITCODE -ne 0) { throw 'Reviewed B1 merge missing' }
$b2PreparationRef = 'codex/provider-onboarding-o1-b2-preparation'
git cat-file -e "${b2PreparationRef}:docs/next-campaign-provider-onboarding-o1-base-selection.md"
if ($LASTEXITCODE -ne 0) { throw 'Import the exact preparation bundle before launching' }
git worktree add -b codex/provider-onboarding-o1-base-selection E:\htdocs\imperium-onboarding-o1-b2 $b2PreparationRef
if ($LASTEXITCODE -ne 0) { throw 'Preserve existing branch/worktree and inspect; do not reset' }
Set-Location E:\htdocs\imperium-onboarding-o1-b2
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
```

In another clone, first verify/import preparation.bundle from the supplied packet;
its prerequisite is the reviewed B1 merge above. Verify the resulting commit/tree
against preparation-identities.json. Never reset an installed checkout to launch.

Implement immutable supplied-context projections, exact integer/rational cost,
separate evidence freshness/effective intervals, complete eligibility/exclusions,
fixed workload baseline/maxima and deterministic tie-break under the B2 contract.
Keep actual admission and qualification separate. Preserve B0/B1, original policy
and workload evidence, dependency lock and pinned config; use class-level Exclude.
No current provider facts, credential input, live call, persistence or assignment.

Run the specified adversarial and regression tests using the actual locked PHP
toolchain. Record exact execution evidence and any incomplete tests. Update the
roadmap, commit locally, and deliver all public results in one
`provider-onboarding-o1-b2-all-deliverables.zip`, with README, source hashes,
patch/bundle, checksummed inner review packet and individual report/instructions.
Stop for source/integration review. This launch does not authorize publication,
later batches or live commissioning.
