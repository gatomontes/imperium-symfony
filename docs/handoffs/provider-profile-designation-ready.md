# PPC2 conditional local handoff

The [designation amendment](../provider-profile-designation-amendment.md) is **proposed, not approved**. This handoff is prepared for the owner's decision. Review/checkout is available now; implementation starts only after explicit approval of clauses A–F. A merged documentation proposal is not that approval.

After approval, use an existing checkout in PowerShell. These commands create an isolated worktree from the published proposal branch, preserving the installed application and earlier worktrees. Choose unused branch/path names if these exist; do not reset or delete them.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
git worktree add -b codex/provider-profile-designation ../imperium-provider-profile-designation origin/codex/profile-designation-amendment-proposal
if ($LASTEXITCODE -ne 0) { throw "Worktree creation failed" }
Set-Location ../imperium-provider-profile-designation
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
Get-Content docs/provider-profile-designation-amendment.md -Raw
Get-Content docs/handoffs/provider-profile-designation-local-prompt.txt -Raw
```

Paste the full prompt into local Codex with the explicit amendment approval. Record the actual starting commit/tree and approval reference in the report. Do not manufacture an approval record merely to pass the prompt prerequisite.

Return `imperium-ppc2-public-review.zip`, with the implementation report at `docs/handoffs/provider-profile-designation-report.md`, an exhaustive supported-writer/fence inventory, an authority/originals matrix, a changed-test map and a future public-input runbook. Include all source and public synthetic evidence; complete path/mode/byte/hash manifests; actual tested and final commits/trees; exact base-to-tested, tested-to-final and base-to-final diffs; a bounded Git bundle with explicit prerequisite; SHA-256 verification instructions. Preserve diagnostic failures and actual Windows/Linux limitations. Exclude private installed state and real secrets.

Run the unchanged complete eight-partition PHPUnit/source/exact-case coverage gate and all 11 guards on committed executable code. The receiving review must verify packet bytes and source, then obtain fresh complete hosted CI before runtime integration. Stop at local commits for that review.

Flow: proposal decision → offline versioned contract/producer implementation and synchronization proof → public packet → source review and complete CI → accepted assignment evidence, or explicit partial refusal with remaining source gap. Provider-dependent evidence and combined FRESH institutional establishment remain separately assessed; there is no automatic live step.
