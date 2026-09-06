# Batch 2 — rehearsal package, before exact-commit audit

Batch 2 adds installed manifest comparison and expands disposable PowerShell proof
to native write denial, absent-file-not-denial, unchanged canary bytes, and an
uncertain consume marker that refuses without journal mutation. It records call
timings and requires exact JSON date strings. Actual probe plans additionally
cover system PowerShell, parents, positive Runtime reads and post-enrollment
journal handle access. Runtime is not required to be excluded from its own state.

Precommit disposable rehearsal passed all four lifecycle states, 15 fixture paths,
independent public receipt verification and unchanged complete fixture manifest.
The fixture is a real separate Git repository with fresh authority and signing
identity. No old evidence or key was reused. Same-user native ACL canary tests
are limited harness tests and explicitly NOT deployed Runtime/caller separation.

The selected target was materialized in a separate fresh preparation package:
commit a1fc4f27634319f2a22df2e6a1b370f70cdb98bf, tree
21780452f5815d4342f8f5c96923b2b276d9930a, 15 paths, 23 distinct objects,
448476 accepted inflated bytes with repeated tree overhead. Source repository
objects were read with lazy fetch/replacements disabled; no source repack/unpack,
checkout, hooks, hardlinks or mission execution. The snapshot is not the installed
runtime build. It remains unprotected staging until the owner freezes it.

Preaudit package consistency covered 6466 files in code/PHP/shell/target. Minimal
packaged PHP with explicit test-only relocated extension path ran the packaged
CLI help. Owner installer WhatIf validated package hashes without changing state.
Its dry-run SID inputs were existing inventory identities used only for account
resolution testing (current administrator-linked user and sandbox account), never
proposed deployment accounts, impersonated identities or measured isolation.

Owner setup/resume: docs/local-isolation-owner-runbook.md. It names fresh standard
PmaRuntime/PmaCaller accounts, all reviewed fixed paths, exact installer/probe/
ceremony commands, expected results, stop/recovery behavior and partial-installation
disposition. The independent Operator holds all real secret material. No accounts,
real trust, real mission or privileged setup was executed by the agent.

Code/test work is committed before the full audit. The final package will be rebuilt
from that exact commit into package-reviewed; package-preaudit is historical only.
Focused/full PHP, Windows PowerShell and packaged minimal-PHP results, tested SHA,
hashes and sanitized proof are recorded afterward in the evidence ledger/audit.
No subsequent executable change may retain those results without retesting.

Changed-test map: LocalIsolationPackageTest (2 new methods), new fixture and
local_isolation_powershell.ps1. Every old protected/amendment/canonical test remains
unchanged. AM01 unsigned-proposal rejection, AM02 inherited-evidence rejection and
fresh-amendment positive proof remain required in the focused/full audit.

Current boundary: DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED.
