# S0 — native IS03 and operation inventory

Source 3e61c0c283aca4fbdd51179405cd7fcc17be3fcf, tree
219da17703d6bb64c5b6582c3aec343f426f278c. Main is the requested
a06a2dec6fc1d62aeef7260e3a9c3508c801fffb; its six documentary changes since
837f2731e705d9aa9737d947b481b57ff0b494f8 were merged locally as 6bdfce95.
Origin is https://github.com/gatomontes/imperium-symfony.git. No implementation
descendant was found in refs; no tracked/untracked work was dirty at entry.
The separate detached reproof worktree and ignored assemblers remain untouched.
No repository/ancestor AGENTS.md was found. No fixed installation exists.
The misspelled review path in the request resolves to the tracked
docs/local-isolation-scratch-permission-review.md.

Native command: `pwsh -NoProfile -File tests/Imperium/Runtime/Support/local_isolation_s0.ps1`.
Evidence: var/local-isolation-evidence/scratch-s0/native-final-2.json.
Actual Windows directory ACL: Runtime RX/CreateFiles on root, ObjectInherit
InheritOnly Modify on files. File creation succeeded; native CreateDirectory
threw UnauthorizedAccessException. The fixture is owned by the current user;
this is the IS03 operation reproduction, **not** equivalent ceremony proof.
Earlier Set-Acl attempts failed with SeSecurityPrivilege missing and are not
successful evidence. icacls applied the recorded native DACL successfully.
The .NET group list omits deny-only administrator groups visible in whoami /all
on this host; owner proof must retain the native group attributes, not infer
equivalence from that summary. No privileged token or accounts were acquired.

| Route | Required native workspace operations |
|---|---|
| prepare / Ceremony | Exclusive random operation directory; recursive mkdir; ProceedingStore persist/read, appendTurn lock file, flock, glob enumeration, temporary file creation/write/rename; dossier persistence/read; nonpersisting preview review; enumerate/unlink/rmdir |
| export/render/sign | Export reads journal payload; render and canonical payload/response files live in exchange or disposable signing environment; no canonical scratch needed |
| submit | Exclusive workspace; recursive dossier seed write; authenticated review reads dossier, enumerates existing reviews, recursively creates review directory and writes review under LOCK_EX; cleanup |
| derive | Exclusive workspace; seed dossier/review; read/validate both; enumerate authorizations; recursive missions directory creation; persist authorization under LOCK_EX; cleanup before journal publication |
| inspection worker | Random input/output/error files, child input read, output/error writes, result read/size, process wait/termination, unlink; move these into the same protected lifecycle without changing inspection semantics |
| refusal/exception | Exact operation cleanup only; journal transaction publishes nothing on exception; preserve leftovers and refuse new work if cleanup fails |
| restart/Status | Unknown or abandoned workspace refuses new mutation; no implicit deletion or retries. Status remains journal-only and preserves uncertain attempt markers |

Canonical services read completely: ProceedingStore, PlanningDossierAssemblyService,
ImperatorPlanningDossierReviewService, MissionAuthorizationDerivationService.
Affected sources include Ceremony, AuthorityOwner, InspectionProcess, InstalledRuntime,
both installers, startup checker, readiness policy/inventory/probes, launcher,
package/review builders and their PHP/PowerShell fixtures. Existing readiness,
AM01/AM02, generation/receipt tests retain their assertions.

Both historical manifest digests match the selection exactly. Ignored original
assembler SHA256 remains 5A5799D5E9A06E36A23806CACC1C7F145901F7C1FB7D223004C4DE2F4B1CE64D.
Baseline branch refs are retained in scratch-s0/refs.txt. Earlier packages,
attempts, raw local evidence and audit records are preserved.

S1 uses a fixed sibling scratch root, administrator-owned root with Runtime
create-file/create-directory/list rights and inherited Modify on descendants.
No new rights on state/exchange/reference parents. Stable readiness measures an
empty scratch root. Internal work is serialized by the existing journal lock;
concurrent measurement explicitly reports busy/abandoned scratch, rather than
blessing transient content or requiring a new measurement per internal write.
