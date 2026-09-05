# Bounded continuation after independent package review

Batches 0–2 remain complete locally. Independent review accepted their package
for owner-controlled setup subject to LI01/LI02: complete exchange/probe-plan
measurements and deterministic validation of readiness evidence.

Complete `docs/next-campaign-local-isolation-measurement-readiness.md` C0–C2
using `docs/handoffs/local-isolation-measurement-readiness-ready.md`, produce a
fresh package, then resume this campaign's Batches 3–5. Preserve the old package
and do not claim deployed isolation before actual measurements pass.

# Local Deployment Isolation and Useful Mission — campaign

Status: LOCAL_ISOLATION_AND_USEFUL_MISSION_SELECTED.
Accepted implementation base: a1fc4f27634319f2a22df2e6a1b370f70cdb98bf (PR #759).
Reviewed correction head: 2a918d38fab1a307d348adc813ef47b1b973fac3.
PR CI: https://github.com/gatomontes/imperium-symfony/actions/runs/33965668160 (passed).
Runner: docs/handoffs/local-isolation-useful-mission-ready.md.

## Outcome

Produce measured Windows Runtime/caller separation and one useful, exact-snapshot
read-only inspection with observable lifecycle and a verified receipt. Use the
verified source evidence to write a bounded report of discrepancies between
documented mission steps and implemented CLI behavior. Report zero discrepancies
if warranted; do not invent findings to satisfy a quota.

The current offline inspector returns verified source bytes and metadata, not
semantic judgments. Keep the mechanical inspection result and the analyst's
derived interpretation separately labeled. The receipt attests the inspection;
it does not certify the truth or completeness of the analyst's conclusions.
No model/provider integration or automatic semantic-analysis engine is selected.

## Authority and progression

Complete Batches 0–2 autonomously on a new local branch. Read-only local environment
inspection, implementation of narrowly necessary harness/runbook fixes, disposable
tests and local commits are authorized. Do not stop merely because real accounts,
trust or keys are absent; finish the reviewable setup package and rehearsal first.

Batch 3 is the concrete deployment-owner action, using exact paths, identities,
code/dependency hashes and commands prepared in Batch 2. The owner operates the
administrator/Runtime/signing terminals. Loco must not request or handle the real
private key or account passwords, impersonate the owner, or generate an enrolled
test key. The owner must review the actual installation changes before applying
them. The authentic signed dossier is required for Batch 4; this planning request
is not a substitute for the runtime signature. Once the required owner actions
and valid authorization are present, continue Batches 4–5 without asking again
for the already selected bounded mission.

This authorizes preparation toward real setup; it does not preapprove unknown
account creation, ACL changes, installation replacement, migration or arbitrary
mission contents. Any missing human step must be executable and specific, not
another conceptual gate. Preserve existing installations and journals; no reset,
replacement enrollment or silent migration.

## Steps and deliverables

| Batch | Work | Required result |
| --- | --- | --- |
| 0 | Verify Git/environment, inspect current implementation and identify actual deployment inputs and supported target storage. | Exact baseline, inventory, account-role matrix, proposed paths, previous-test disposition and finite checklist of missing inputs. |
| 1 | Prepare executable PowerShell orchestration, access probes, status capture and evidence verification using existing CLI. Correct only concrete blockers in this route. | Commands fail on real errors, distinguish missing files from denied access, and support resume by persisted IDs without replaying consumed authority. |
| 2 | Rehearse the entire sequence with disposable identities/state and a real Git fixture; prepare the exact owner setup package and mission draft. Commit code/test work, then audit that commit. | Focused/full tests, PowerShell result, package hashes, target-object inventory, planned changes and rollback/partial-installation disposition. No invented successful deployed-isolation result. |
| 3 | Owner reviews and applies fresh installation/account setup, establishes protected PHP/environment, measures actual-account separation, and enrolls independently held public trust. | Actual token identities, positive Runtime access, caller denials, immutable code/dependency checks and public fingerprint confirmation. Private key stays with owner. |
| 4 | Export/render the fresh dossier, obtain authentic owner signature, submit/derive/verify and execute one exact authorized inspection. | AUTHORIZED → ADMITTED → INSPECTING → COMPLETED evidence using actual implementation transition names verified in Batch 0; generation-bound receipt and unchanged target. Do not force vocabulary if implementation differs. |
| 5 | Independently reconstruct public receipt bindings and target bytes; produce the discrepancy report and terminal local audit. | Finite verified evidence, useful report, exact tested/installed/target identities, residual limits and truthful local disposition. |

Each batch has a separate local commit where it changes tracked deliverables.
No requirement to merge between local batches. Owner-local operational records
are retained outside Git; sanitized evidence can be committed after inspection.
Stop optional testing once the selected risks and required gates are covered.

## Deployment proof scope

Distinguish deployment administrator, trusted Runtime owner, untrusted caller and
independently held-key Operator. The Runtime legitimately writes authority state
and reads its issuer secret. It is the caller that must be denied state read/write;
do not demand that the authority owner be denied its own journal.

Record actual account tokens and relevant group memberships. Verify:
- Runtime is the designated non-administrator identity; caller is distinct and is
  not supplied arbitrary shell/PHP execution under the Runtime identity.
- Caller cannot read/write authority state or replace public trust, installation
  metadata, code, autoloader, dependencies, PHP binary/extensions/configuration,
  launcher or relevant parent directories.
- Runtime can perform only the documented state operations and cannot alter
  installed code/installation metadata. Include owner/group/ACL/reparse checks.
- Correct Runtime startup succeeds and caller/wrong-identity startup refuses.
- Hashes of installed code and dependencies match the reviewed installation manifest.

Inspect effective access, including parent replacement rights; ACL text alone is
insufficient. Before enrollment, use harmless canaries with the same actual ACLs.
After enrollment, a journal-read probe may attempt opening a handle but must not
read or print bytes. Never write/delete real state/code to demonstrate a denial.
Unexpected successful access is a failed isolation case; preserve evidence and
stop real execution. Missing files, skipped probes, same-user test doubles and
installer -WhatIf output do not prove deployed isolation.

The current route is human-mediated stdio; do not invent an unattended broker.
Prove that forwarding data cannot give the caller owner commands or arbitrary
code execution. Administrators and a compromised trusted Runtime/signing process
remain outside the bounded exclusion claim. Record any deployment assumptions
that are not measured, including PHP/environment security.

## Exact mission

Use a new mission identity. Proposed target is the accepted snapshot
a1fc4f27634319f2a22df2e6a1b370f70cdb98bf in a separately prepared local inspection
copy. Resolve and record the tree and all allowlisted blob IDs locally; never
copy an unverified tree/digest from prose. This is the historical merged snapshot,
not a claim to inspect the future implementation or planning HEAD.

Candidate allowlist:
- docs/delegate-mission-flow.md
- docs/protected-mission-operator-runbook.md
- docs/next-campaign-mission-amendment-correction.md
- docs/handoffs/mission-amendment-correction-local-audit-complete.md
- bin/protected-mission.php
- src/ProtectedMission/Cli.php
- src/ProtectedMission/InstalledRuntime.php
- src/ProtectedMission/AuthorityOwner.php
- src/ProtectedMission/Ceremony.php
- src/ProtectedMission/Generation.php
- src/ProtectedMission/OfflineGitInspector.php
- src/ProtectedMission/InspectionProcess.php
- tools/ProtectedMission.ps1
- tools/Install-ProtectedMission.ps1
- tools/Assert-ProtectedMissionInstallation.ps1

Proposed ceilings: 15 paths, 15 mechanical file findings, 4,000,000 accepted
inflated Git-object bytes, 900 seconds; at most 15 analytical discrepancies.
Measure commit/tree traversal overhead before sealing. Validate all limits
against the real schema. If insufficient, reduce scope or present a revised
explicit budget before signing; never silently widen it.

The target must satisfy the existing local loose-SHA-1 reader. Prepare a separate
copy with exact original Git object IDs and no hardlinks/shared writable objects.
If loose materialization is needed, do it before the mission using local source
objects, record the derivation, and verify every identity. Do not repack, modify,
or unpack the user's source repository during mission execution. Freeze the copy
read-only for the executing identities and hash its complete file manifest before
and after. No fetch/network/hook execution during the mission. Packed-object
support is not a hidden addition to this campaign.

Keep three distinct identities: the installed runtime build, inspected target
commit/tree, and later evidence/report commit. Any installed code change requires
new validation before use. Choose fresh expiry within the implemented limit only
when ready for the ceremony; do not bake a stale timestamp into this plan.

Prepare all required numbered disclosures. Export exact canonical bytes, render
all lines, verify the public fingerprint and obtain the owner's affirmative
signature. Publish no pending challenge or usable capability. Use persisted
challenge/authorization IDs for recovery; never blindly retry consumed actions,
revive old missions or borrow earlier generations' evidence.

The selected mission permits only allowlisted Git-object reads and separate local
lifecycle/evidence/status/receipt writes. No target mutation, providers, network,
credential access, remediation, other repositories or second mission. A refusal
or unknown result is recorded truthfully; a retry/new mission needs fresh valid
authority. No Iron Gate, Lazaretto, trading, email or live Batch 7.

## Useful report and verification

For each discrepancy include the exact documented claim, source path/blob and
location, executable evidence path/blob and location, expected versus observed
behavior, confidence, and bounded implication. Distinguish stale historical text
from a real current-flow inconsistency. A keyword absence is not proof that an
operation is unreachable. Mark gaps outside the allowlist as unverified.

Produce the analysis locally from the already verified allowlisted bytes as a
separate analyst report, without invoking a provider or adding unauthorized
cognition ingestion. Do not label a human/agent reading as runtime-produced
semantic findings. An automated semantic report would require a separate
explicit design decision and is not needed to finish this campaign.

The audit reconstructs target commit/tree/blob/content hashes and exact generation
bindings instead of trusting producer booleans. Check lifecycle history, actual
receipt, budgets, no stale-authority use and target manifest equality. Preserve
reported failure/absence/unknown distinctly from success.

## Tests, historical evidence and closure

Preserve all previous branches and useful test methods, including the quarantined
candidates. Do not restart earlier campaigns or reuse their keys, authority,
dossiers, receipts or runtime state. The 2675 / 52696 audit remains historical
evidence at 2a2b746feb9c66c961033ffafcf961f3bb49c4b5, and the PR #759 CI remains its
own remote event. New results must identify their actual tested SHA/environment.

Run focused protected-mission/amendment tests after relevant fixes. Run the full
PHPUnit suite on the committed Batch 2 code and again only if subsequent code or
tests change. Validate PowerShell orchestration on Windows. Record skip reasons;
an unavailable Windows/account test cannot be counted as passed. Keep a changed-
test map and retain AM01/AM02 rejection and fresh-amendment positive proofs.

Required artifacts: batch/evidence ledger, owner setup and resume runbook, exact
mission draft and public manifest, isolation matrix, sanitized lifecycle/receipt
verification, analytical discrepancy report, terminal audit and previous-test
disposition. Never commit raw private evidence, journal/issuer secrets, private
keys, passwords or usable capabilities. Public artifacts must be inspected for
sensitive content, not merely assigned a "sanitized" filename.

If Batches 0–2 finish but human action remains:
LOCAL_ISOLATION_PACKAGE_READY_AWAITING_OWNER_ACTION.
Return the exact reviewed command sequence, accounts/paths that remain to be
supplied, package digest and expected public outputs. Explain why the actual
owner action is required. Do not claim the whole campaign is complete.

If actual isolation and the mission/receipt verification all pass:
LOCAL_ISOLATION_AND_USEFUL_MISSION_COMPLETE_PENDING_INDEPENDENT_REVIEW.
Record ISOLATION_PROVED_FOR_RECORDED_WINDOWS_DEPLOYMENT only for the measured
installation. Otherwise retain DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED
or report ISOLATION_FAILED; never erase historical qualifications globally.

Windows-only operation, loose SHA-1 support, bounded journal capacity, unavailable
migration and unmeasured hardware power-loss behavior remain explicit. Local
completion is not independent acceptance or production-wide security proof.

No local implementation push/PR/main merge or branch deletion in this runner.
The present GitHub authorization publishes and merges these planning documents.
After the local handoff, the user can publish its candidate for independent review.

Fortuna eruditis favet.
