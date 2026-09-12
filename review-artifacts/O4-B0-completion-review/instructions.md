# Continue O4-B0 locally

The review is HOLD because the selected batch is incomplete. Continue from the submitted implementation rather than starting again from main. O4 remains open; O5 has not started.

From your existing `imperium-onboarding-o4-b0` repository worktree, run:

```powershell
git cat-file -e '4711ea5def32148335069f396fc86ab6abc82df4^{commit}'
if ($LASTEXITCODE -ne 0) { throw "Submitted O4 commit is missing; use the original local checkout or import its verified implementation.bundle" }
git worktree add -b codex/provider-onboarding-o4-b0-completion ../imperium-onboarding-o4-b0-completion 4711ea5def32148335069f396fc86ab6abc82df4
if ($LASTEXITCODE -ne 0) { throw "Choose a fresh destination and branch name if these already exist; do not reset earlier work" }
Set-Location ../imperium-onboarding-o4-b0-completion
git status --short
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
```

Start a local Codex chat in this worktree. Paste the complete `completion-prompt.txt` from this review packet and attach `review-report.md`. The full O4 contract is already in the repository. No pull of a new implementation branch is required: the submitted implementation commit is local and has not been published by this review.

If using another checkout with the preparation baseline, import the original submitted `implementation.bundle` first using a fresh local ref; verify its head is the exact commit above. Do not apply the original entry-to-final patch on top of the already implemented partial commit.

Finish the same batch and return `provider-onboarding-o4-b0-completion-all-deliverables.zip` for source review and fresh full hosted CI. The review packet itself contains documentation and verification only, not a replacement implementation or evidence of O4 acceptance.
