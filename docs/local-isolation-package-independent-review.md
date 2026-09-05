# Local isolation package — independent review

Reviewed inputs: `local-isolation-independent-review.md` and
`review-packet-manifest.json`, compiled from local branch
`codex/local-isolation-useful-mission` at
`574bfc69aebf12b5197f4f808b54896bf02ba142`. Tested executable code:
`47bcc44a8fc0540bef855d27ba71abaebb452c84`. Original package-manifest
SHA-256: `8DAB48F98D147A6ABA9E89260D25082BA5B91AD2C72638F1F10FFB9A449CF6AE`.

The packet digest, all 55 embedded source hashes, the original 6,469-entry
manifest digest, and the 30 embedded packaged-file hashes matched. Packaged
binaries and unembedded dependencies were not independently inspected. Reported
2,677 tests / 52,713 assertions, focused 28 / 419, and disposable Windows
rehearsal remain attributed to the local audit and were not rerun here.

Verdict: suitable for owner-controlled installation and measurement after the
two bounded findings below are corrected. Real mission execution remains
conditional on passing actual-account evidence and authentic authorization.

## LI01 — incomplete measured surface

`New-LocalIsolationProbePlans.ps1` covers immutable assets, ancestors,
state/canaries/metadata and optional journal, but omits explicit coverage of
`ProtectedMissionExchange`, `ProtectedMissionProbePlans`, and
`ProtectedMissionPostEnrollmentPlans` with their contents. These surfaces hold
mission inputs, readiness records, evidence and measurement plans. Their intended
role-specific access, ACL/owner, and direct/parent replacement rights must be
measured. Missing, unknown or unexecuted rows cannot count as passing isolation.

## LI02 — readiness accepts unvalidated references

`Invoke-LocalMission.ps1` checks a readiness status and nonempty measurement-hash
fields. It does not recompute the referenced files, validate the complete probe
set/results, or bind every readiness field to the installed package/session.
The runbook already requires human review, but that review is not repeatable.

Add a deterministic validator that derives expected coverage from reviewed policy
and installed inventory; checks exact plans/results, SIDs, tokens, package,
installation, trust and startup outcomes; and rejects omitted, duplicate, altered,
stale or mismatched evidence before Prepare/Accept/Step. Preserve Status as a
read-only recovery path. The validator proves consistency of trusted collected
observations, not authenticity of arbitrary copied logs or the human transport
arrangement. Those remain explicit premises.

Preserve the original package and tests. Any executable change requires a fresh
package and manifest.
