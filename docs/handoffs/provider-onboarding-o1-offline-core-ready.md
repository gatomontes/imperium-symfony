> Current onboarding status: [validated O1 offline selector and owner-authorized integration](../provider-onboarding-o1-current-status.md).
> Earlier PHP-validation-pending and local-only instructions below are historical for this batch.
> Author-review provenance and all live deferrals remain explicit.

# Local validation and review — O1 offline core

Read the campaign and report, identities.json, contracts/provider-onboarding-assignment-selection.md and the retained O0 medium-capacity decision/review. This batch has no passing PHP execution evidence yet. Do not reactivate earlier campaigns.

The incremental bundle requires commit `3a5dfc1d4f341a12e5c3269c8f3c55f79807a342`. If absent, first import the prior medium-capacity bundle using the original provider-submission prerequisite documented in that packet. Use an isolated repository/worktree, not the installed checkout. Verify the fetched commit/tree against identities.json before running anything. Example PowerShell, with your actual downloaded bundle path:

```powershell
$reviewBundle = 'C:\Downloads\o1-offline-core.bundle'
# Run in an isolated review clone already containing the prerequisite.
git bundle verify $reviewBundle
if ($LASTEXITCODE -ne 0) { throw 'Bundle prerequisite/integrity check failed' }
git fetch $reviewBundle refs/heads/codex/provider-onboarding-o1-offline-core:refs/heads/review/o1-offline-core
git worktree add E:\htdocs\imperium-o1-offline-review review/o1-offline-core
Set-Location E:\htdocs\imperium-o1-offline-review
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
# Compare both outputs with identities.json before continuing.
php --version
# Only if vendor is absent: install the existing lock in this isolated worktree.
composer install --no-interaction --no-scripts
if ($LASTEXITCODE -ne 0) { throw 'Locked dependency installation failed' }
New-Item -ItemType Directory -Force var/local-isolation-evidence/o1-offline-core | Out-Null
Get-ChildItem src/Imperium/Runtime/Onboarding/Selection/*.php, tests/Imperium/Runtime/ProviderOnboardingOfflineSelectionTest.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $($_.FullName)" }
}
php vendor/bin/phpunit --bootstrap vendor/autoload.php --colors=never --log-junit var/local-isolation-evidence/o1-offline-core/phpunit.xml tests/Imperium/Runtime/ProviderOnboardingOfflineSelectionTest.php
if ($LASTEXITCODE -ne 0) { throw 'Focused tests failed' }
python tools/check_provider_onboarding_spec.py .
if ($LASTEXITCODE -ne 0) { throw 'Specification checks failed' }
git diff --check
git status --short
```

Do not run provider commands, compile an operational container against installed configuration, load private state, or push/merge. No existing service dependencies are used by this pure core, so the focused boundary test and static check of the one service-discovery exclusion are the relevant regression coverage; a broad suite is not a substitute. If future integration changes dependencies, select relevant regression tests then.

Review strict decoder boundaries, invalid-ranked coverage before zero/one filtering, permission intersection, lower-middle/equal-tier behavior, exact UTF-8 ordering, immutable inputs, mixed Profile refusal, evidence membership, role order, coupled pair refusal, and absence of authority/effects. The raw JSON duplicate-member and full-response authenticity boundary remains explicitly unimplemented. Inspect original O0 authority/policy evidence independently; do not relabel the author's self-review.

Return exact checked commit/tree, PHP/PHPUnit versions, lint/test output and status, changed-source hashes, and any post-check changes. Include all public files in one all-deliverables ZIP with README and inner review packet/checksum; no outer hash. Supply individual report and handoff files too. Exclude credentials and private evidence. Stop at review; preserve CY/FC acceptance and every live deferral.
