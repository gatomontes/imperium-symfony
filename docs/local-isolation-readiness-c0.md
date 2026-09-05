# C0 — measurement and readiness contract

Baseline: 574bfc69aebf12b5197f4f808b54896bf02ba142, tree
a2d5535dc9ef6e82d02f1225a8066c5de31df245. Main and fetched origin/main:
837f2731e705d9aa9737d947b481b57ff0b494f8. Exact six-file planning selection
merged on codex/local-isolation-measurement-readiness from the implementation.
No later local implementation descendants or tracked/untracked changes existed.
Ignored assembler and packet remain in var/local-isolation-evidence, unchanged.
No applicable ancestor or repository AGENTS.md or existing installation was found.

Reproduction: `pwsh -NoProfile -File tests/Imperium/Runtime/Support/local_isolation_c0.ps1`.
It executes a relocated copy of the original generator against disposable files,
and the exact original readiness conditional against nonexistent evidence.
No production fix belongs to C0. The original package and manifest remain historical.

## Frozen access policy

All finite installed files/directories, state canaries/metadata, exchange and both
plan roots and their contents require positive owner inventory, attributes,
owner/group/SDDL and no reparse points. Account evidence binds exact distinct
non-builtin Runtime/Caller SIDs, complete token group sets and no administrator
membership (including a filtered token). Missing paths and unknown native errors
refuse. Handle opens never read/write/delete operational content.

| Surface | Runtime | Caller |
|---|---|---|
| Immutable code/PHP/shell/target and plan roots/files | Read/list succeeds; write/append/delete/delete-child/DAC/owner denied | Same |
| Owner evidence/reference files in exchange | Read succeeds; all mutation denied | Read and all mutation denied |
| Exchange directory | List/add-file succeeds; append/add-directory/delete/delete-child/DAC/owner denied | All denied |
| Runtime-created finite exchange outputs | Read/write/append/delete succeeds; owner rights recorded, not excluded | All denied |
| State directory | List/add-file succeeds; delete-child denied; other mutation policy explicit | All denied |
| Installation metadata and metadata canary | Read succeeds; mutation denied | All denied |
| State canary / enrolled journal | Required Runtime state access succeeds; legitimate Runtime ownership excluded from exclusion claim | All denied |
| Ancestors of protected children | Delete-child/DAC/owner denied for both roles; direct delete of protected roots denied | Same |

Owner reference files must resist replacement through their parent: exchange may
grant Runtime creation but never delete-child. Preserve original attempt markers.
Plan directories are owner-controlled and readable by both identities. Readiness
is never an editable boolean that substitutes for actual measurements.

## Frozen phases and evidence

Pre-enrollment measures canaries and all existing surfaces, with Runtime startup
success through the checker followed by exact PMA_TRUST_ABSENT. Post-enrollment
adds the journal and public trust and requires exact trust/fingerprint binding.
Before each mutating mission action, current finite exchange contents require
fresh coverage; new outputs invalidate previous current-inventory evidence.
Status requires no readiness and performs no evidence/authority write.

Plans contain canonical paths/rights/expected results derived from installed
inventory and this reviewed policy. Detached hashes bind final plan bytes; plans
do not hash themselves. Readiness references exact plan, measurement, inventory,
startup and public-trust bytes; binds installation metadata, package digest,
protected configuration, setup session, phase, collection time and both identities.
Validation rederives coverage instead of trusting a producer's plan or summary.
Exact row sets reject missing/extra/duplicate/mask/expectation/result changes.
Native exit/status/output bindings distinguish identity refusal from generic error.
Freshness has a bounded owner measurement window; no timestamps are preapproved.

## Affected source and changed-test map

Review/change: New-LocalIsolationProbePlans.ps1, Test-LocalIsolationAccess.ps1,
Install-LocalIsolationOwnerPackage.ps1, Invoke-LocalMission.ps1, LocalMission.ps1,
Assert-ProtectedMissionInstallation.ps1, InstalledRuntime.php; add shared readiness
policy/validator and collection helpers. Review package builder and installed
manifest verifier, CLI/owner/journal, LocalIsolation.php and ProtectedMission.ps1.
Add deterministic negative fixtures and native PowerShell route tests. Retain
LocalIsolationPackageTest, ProtectedMissionAuthorityTest, MissionAmendment tests,
all prior fixture routes, AM01/AM02 and uncertain-attempt tests. Any changed prior
test expectation must be identified explicitly in C2.

Validator consistency is conditional on trusted collection/custody, OS and
uncompromised Runtime. It cannot authenticate arbitrary producer-controlled logs
or prove human forwarding and independent signing custody. Those owner facts
remain separate. No deployed isolation is claimed by C0 fixtures.
