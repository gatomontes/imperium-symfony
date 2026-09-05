# Protected Scratch Workspace and Ceremony Permission Correction

SCRATCH_WORKSPACE_CEREMONY_CORRECTION_SELECTED.
Review: docs/local-isolation-scratch-permission-review.md.
Runner: docs/handoffs/local-isolation-scratch-correction-ready.md.
Parent: docs/next-campaign-local-isolation-useful-mission.md.
Preceding correction: docs/next-campaign-local-isolation-measurement-readiness.md.

## Objective and bounds

Fix IS03: the deployed Runtime cannot create the scratch directories required by
the approval ceremony. Preserve LI01/LI02 and prove the positive operation with
Windows permissions enforced. Complete this finite correction and return to
parent Batches 3–5; do not redesign approval, authority, providers or governance.

Local source branch: codex/local-isolation-measurement-readiness.
Known final head: 3e61c0c283aca4fbdd51179405cd7fcc17be3fcf.
Tested code: 6bb2628d440b45b881d2d330c61fe3b5b4aab521.
Planning base: 837f2731e705d9aa9737d947b481b57ff0b494f8.
The implementation is not assumed present on remote main.

## Steps

| Stage | Work | Exit evidence |
| --- | --- | --- |
| S0 | Inventory all workspace operations and reproduce the denied directory creation under disposable Windows ACLs. | Exact caller/Runtime rights and production call path; characterize the existing failure without installing the old package. |
| S1 | Implement the smallest protected workspace correction; update installer, startup checker, inventory/probes and readiness phase handling together. | Successful real ceremony plus caller/reference denial and cleanup/failure regressions under enforced permissions. |
| S2 | Commit executable/tests before audit; run focused/full/PowerShell checks, rebuild and prepare a fresh review packet and owner runbook. | New package digest, exact tested SHA/tree, positive and negative proof, preserved history and concrete owner actions. |
| Parent 3–5 | Owner setup/actual measurement, authentic approval and one selected mission, then receipt verification and useful report. | Original bounded operational outcome; no restart of earlier campaigns. |

S0–S2 are authorized sequentially locally, with separate commits and no repeated
permission for routine preparation. If a native account/elevation step is genuinely
unavailable, finish every code, fixture and reviewable command first; identify the
exact missing test and required owner action. Do not claim native success from
synthetic observations.

## Workspace design

Prefer an administrator-provisioned dedicated scratch root with a fixed,
Runtime-owned purpose and a location separate from owner reference records.
Runtime must be able to create nested work directories/files, use canonical
services, and clean only its own work. Caller must be excluded. Never grant
general Modify/delete-child on a parent containing installation metadata,
public trust, readiness attestations or owner evidence.

Resolve the root from trusted installation configuration/code, not caller input,
environment override, system temp or an arbitrary request path. Reject path
substitution, reparse traversal and reuse of another operation's workspace.
A trusted test relocation may map fixed installation roots into a disposable
fixture; reuse the production layout/ACL builder and ceremony, rather than
maintaining a separately permissive test-only implementation.

Inventory ALL affected operations: mkdir, recursive canonical-service persistence,
read/write, enumeration, unlink/rmdir, cleanup after success/refusal/exception and
restart with an abandoned workspace. Extend the same audit to any worker staging
paths affected by the installer change. Do not broaden unrelated worker behavior.

Integrate the new directory into startup checks, Get-PmaInventory, surface classes,
expected probe rights, package inventory and phase evidence. Model legitimate
Runtime ownership/rights explicitly. Preserve owner-reference replacement
denials and existing state/exchange policy.

Temporary contents require explicit lifecycle treatment: while active, do not
misclassify them as unrelated files or demand a new human measurement for each
internal canonical write. After cleanup, readiness should validate the expected
stable layout. An abandoned workspace must be identified and safely refused or
handled by a narrowly defined recovery path; never silently delete unknown state.
Do not remove finite surface coverage or accept an unbounded prefix exemption.

Cleanup may remove only the exact operation workspace and must not follow links
or modify authority journals, other workspaces or owner evidence. Record cleanup
failure honestly, preserve incident evidence and keep Status recovery available.
Do not invent retry authority or erase uncertain-attempt markers.

## Required proof

Use disposable Windows directories and credentials. At least one test must invoke
the actual prepare → export/render → sign with a disposable key → submit → derive
→ verify route under an effective token and ACLs equivalent to the intended
Runtime/caller arrangement. Exercise the native directory creation, nested file
operations and cleanup. Record actual token/groups, ACLs and native outcomes.

Map existing suitable identities or a faithfully restricted token in the disposable
harness; do not create real deployment accounts or request passwords in agent
custody. Administrative/ownership rights that defeat the tested restrictions
invalidate an equivalence claim. If the environment cannot enforce equivalent
permissions, deliver the exact owner-run disposable proof and label it NOT RUN.

The positive proof must use the production workspace path resolver, ACL policy,
startup checks and ceremony. Do not substitute a successful mocked mkdir, synthetic
inventory, forged observations, bypassed checker or direct journal edit. Tests may
relocate paths and provision disposable trust, clearly recording these differences.

Required negatives and interactions:
- old ACL reproduces directory creation refusal;
- Caller cannot create/read/write/delete scratch work or change its ACL/owner;
- Runtime can operate within scratch but cannot replace installation/trust/readiness
  or collected owner evidence through direct or parent rights;
- wrong/missing/reparse workspace refuses before authority publication;
- exception/cleanup and abandoned-workspace handling preserve journal and other work;
- readiness succeeds with clean corrected layout, rejects unsafe workspace policy,
  and Status remains available with invalid readiness;
- existing LI01/LI02, AM01/AM02 and byte/receipt-generation regressions remain intact.

Run focused tests after relevant changes. Run full php vendor/bin/phpunit tests
on the committed final S2 executable/test head. Exercise PowerShell on Windows
and retain native proof attribution. Repeat full runs only for actual failures or
subsequent code/test changes. Maintain a changed-test map; no weakening of caller
exclusion or replacement protection to obtain a passing positive test.

## Package, history and owner handoff

Preserve both previous packages and all branches/tests/evidence:
package-47bcc44a, original manifest
8DAB48F98D147A6ABA9E89260D25082BA5B91AD2C72638F1F10FFB9A449CF6AE;
package-readiness-6bb2628d, manifest
C0442CFCC4FD68AFF12161FC8C942AFC3E4BD4F01A7336B80A603E341CC9076D.
Neither is the corrected installation package.

Build into a fresh directory with a new manifest. Update installed-build references,
workspace disclosures, current steps/flow, owner setup/resume instructions, audit,
evidence ledger, independent-review Markdown and derivative JSON manifest.
Return exact local paths for uploading the latter two files.

Keep inspected target a1fc4f27634319f2a22df2e6a1b370f70cdb98bf and its existing
15-file mission scope distinct from the newly installed code. Do not reuse
old trust, approvals or capabilities. No real secrets or raw operational state
in committed evidence.

Completion:
SCRATCH_WORKSPACE_CORRECTION_LOCAL_READY_PENDING_INDEPENDENT_REVIEW.
Do not claim a real deployed-isolation result. If native proof remains unavailable,
report SCRATCH_WORKSPACE_CORRECTION_NATIVE_PROOF_PENDING instead and provide the
completed owner-run test package.

After independent review, continue the already selected owner setup and mission
when authentic prerequisites exist. No further campaign is required merely to
perform the prepared owner actions. If an installation already exists, inspect
read-only and prepare an exact owner disposition; never overwrite or migrate it.

This runner permits initial read-only Git fetch and local planning integration.
No implementation push/PR/main merge, branch deletion, account creation, real
installation, agent private-key/password handling, journal reset, provider action,
target mutation, second mission, Iron Gate/Lazaretto or live Batch 7.
This GitHub push/merge publishes planning only.

Hoc est pretium solitudinis.
