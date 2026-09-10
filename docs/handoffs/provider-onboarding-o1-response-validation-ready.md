# New local Codex run — O1-B1 response validation

Continue Imperium from main containing docs/next-campaign-provider-onboarding-o1-response-validation.md. Read applicable AGENTS.md, that campaign, docs/provider-onboarding-implementation-roadmap.md and all sources the campaign names.

Execute O1-B1 only: bounded duplicate-key-safe raw JSON decoding and complete W1/W2/W3 response/context validation under contracts/provider-onboarding-response-validation.md. Keep structural consistency separate from genuine evidence/authority admission. The completed selector and historical acceptance remain intact. No later roadmap batch or live activity is selected by this run.

Start from a fresh isolated worktree and record actual entry commit/tree. Example PowerShell, run from an existing source clone; this fetch does not change its installed worktree:

```powershell
git fetch origin main
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed' }
git cat-file -e origin/main:docs/next-campaign-provider-onboarding-o1-response-validation.md
if ($LASTEXITCODE -ne 0) { throw 'Campaign preparation is not on fetched main' }
git merge-base --is-ancestor 086eb363ef43e58a29a50e7dfdecba34306c44c3 origin/main
if ($LASTEXITCODE -ne 0) { throw 'Expected O1 integration missing' }
git worktree add -b codex/provider-onboarding-o1-response-validation E:\htdocs\imperium-onboarding-o1-b1 origin/main
if ($LASTEXITCODE -ne 0) { throw 'Preserve any existing branch/worktree and inspect; do not reset it' }
Set-Location E:\htdocs\imperium-onboarding-o1-b1
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
```

Implement the exact closed response shapes and adversarial tests specified in the campaign. Use existing dependencies and class-level Symfony Exclude attributes. Keep config/services.yaml and accepted source-pin ledgers unchanged. Raw JSON must preserve object/list distinctions, reject duplicates including escaped names, and enforce the declared UTF-8/byte/depth bounds. Original bytes remain attributable. A parsed response must not be passed directly into the selector as trusted fitness.

Verify the actual locked PHP toolchain (PHP >=8.4.1 for the current PHPUnit lock). Install the lock without scripts/plugins if needed in the isolated worktree. Run lint, the new raw-response tests, the existing selector tests, the three named source-pin regressions and the 86 specification checks. Record unavailable tools or failures honestly and correct concrete defects within this scope. Do not fabricate a pass from inspecting prior logs.

Update the selected roadmap row and report with exact source identities and real validation. Commit locally. Deliver all public results in ONE provider-onboarding-o1-b1-all-deliverables.zip with README and a checksummed inner review packet; no outer hash. Also give individual report/instruction files. Do not include private state or credentials. Stop for source/integration review; no automatic provider call, appointment, application, activation or later campaign.
