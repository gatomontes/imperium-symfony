# PPC3 local handoff — countdown 5–7 remaining

Implement [PPC3 native tenure synchronization](../next-campaign-provider-native-tenure.md), countdown outcome R2. Approved PPC2 A–F already covers this bounded offline work. The owner selected continuation and countdown tracking. No additional blanket approval is needed for the selected scope.

Use an existing repository checkout in PowerShell. Choose unused names if the branch/path already exists; preserve earlier checkouts and the installed application.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-native-tenure ../imperium-provider-native-tenure origin/codex/provider-native-tenure-preparation
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-native-tenure
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/handoffs/provider-native-tenure-local-prompt.txt -Raw
```

Start local Codex in this worktree and paste the full prompt. Return `imperium-ppc3-public-review.zip` and its separate checksum. Include `docs/handoffs/provider-native-tenure-report.md`, writer/lock-order proof, compatibility matrix, changed-test map, runbook, committed source ZIP, public synthetic evidence ZIP, complete path/mode/byte/hash manifests, tested/final identities, exact base-to-tested/tested-to-final/complete diffs and bounded Git bundle with its exact prerequisite. No private runtime roots, secrets, vendor/cache or untracked installed material.

The report must show the [countdown](../provider-first-interview-countdown.md): **5–7 before review; potential 4–6 after accepted R2 closure**. Preparation, submission, documentation changes or partial code do not decrement it. Runtime integration requires receiving source review and fresh complete hosted CI.
