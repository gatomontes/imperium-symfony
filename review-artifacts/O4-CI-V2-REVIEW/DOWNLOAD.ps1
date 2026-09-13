$ErrorActionPreference = "Stop"
git fetch origin refs/heads/codex/provider-onboarding-o4-b0-ci-v2-review
if ($LASTEXITCODE -ne 0) { throw "Fetch failed" }
$tempZip = Join-Path $env:TEMP ("o4-v2-review-" + [guid]::NewGuid() + ".zip")
git archive --format=zip --output="$tempZip" FETCH_HEAD:review-artifacts provider-onboarding-o4-b0-ci-completion-v2-review-all-deliverables.zip
if ($LASTEXITCODE -ne 0) { throw "Archive failed" }
Expand-Archive -LiteralPath $tempZip -DestinationPath "$env:USERPROFILE\Downloads" -Force
Remove-Item -LiteralPath $tempZip
