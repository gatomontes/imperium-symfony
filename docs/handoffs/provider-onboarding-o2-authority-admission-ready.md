# Local launch — O2-B0 authority admission

This is the next implementation handoff, not evidence that O2-B0 exists. The
preparation is local on `codex/provider-onboarding-o2-b0-preparation`; publication
and integration are separate. Preserve existing branches/worktrees.

From an existing clone containing this local preparation branch:

```powershell
git worktree add -b codex/provider-onboarding-o2-authority-admission E:\htdocs\imperium-onboarding-o2-b0 codex/provider-onboarding-o2-b0-preparation
if ($LASTEXITCODE -ne 0) { throw 'Preserve existing branch/worktree; inspect instead of resetting' }
Set-Location E:\htdocs\imperium-onboarding-o2-b0
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
```

For a separate clone, verify/import the delivered preparation bundle using the
exact commit in preparation-identities.json, then use that commit as the worktree
start point. If this preparation is subsequently reviewed/integrated, use a verified
main containing it and record the actual entry. Do not start from the O1 merge alone
and omit this preparation. This pass has neither pushed nor merged the preparation.

Paste into local Codex in that worktree:

```text
Implement O2-B0 only. Read applicable AGENTS.md, docs/next-campaign-provider-onboarding-o2-authority-admission.md, contracts/provider-onboarding-authority-admission.md, docs/provider-onboarding-implementation-roadmap.md and every required source they name. Record actual entry commit/tree.

Implement fixed-root original policy/act/reference admission and current authority resolution for the selected FRESH route. Preserve the D2/F1/F2/v1.3.1 registry, original approved policy/workload identities and separate trust competence. Use real signing/verifying and protected producer-to-consumer paths in synthetic temporary-root tests; never fabricate genuine installed authority or convert O1 projections into it. Keep admission uniqueness separate from O2-B1 execution consumption. Preserve historical records and pinned services through class-level Exclude.

Complete all required adversarial, concurrency/recovery and regression checks. No provider call, credential, installed trust enrollment, activation, persistence of assignments, operational wiring or later batch. Use no subagents. Commit locally and return one provider-onboarding-o2-b0-all-deliverables.zip with all public sources, tests, report/instructions, commands/logs, identities/hashes, patch/bundle and checksummed inner review ZIP; no outer checksum. Provide individual report/instructions and stop for source/integration review.
```
