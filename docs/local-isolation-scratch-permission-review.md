# Scratch workspace permission review — IS03 (P1)

Reviewed local packet: local-isolation-readiness-independent-review.md and its
derivative review-packet-manifest.json. Source head:
3e61c0c283aca4fbdd51179405cd7fcc17be3fcf.
Tested executable code: 6bb2628d440b45b881d2d330c61fe3b5b4aab521.
Package: package-readiness-6bb2628d.
Manifest SHA-256:
C0442CFCC4FD68AFF12161FC8C942AFC3E4BD4F01A7336B80A603E341CC9076D.

Independent review matched the packet hash, 67 embedded source hashes, the
6477-entry package-manifest digest and 33 embedded packaged-file hashes.
No actual Windows installation or full-suite rerun was performed by the reviewer.
The reported 2679 tests / 52722 assertions and focused 30 / 430 remain local
evidence at the tested commit.

LI01/LI02 corrections are substantially present: additional measured surfaces,
deterministic evidence validation, and readiness-independent Status recovery.
Deployment acceptance is withheld for the concrete operational conflict below.

## IS03: installed ACL denies an operation required by the ceremony

Install-LocalIsolationOwnerPackage.ps1::Set-PmaOperationalDirectory grants
Runtime ReadAndExecute plus CreateFiles on the state root, with ObjectInherit
Modify for files. It does not grant CreateDirectories there.

Ceremony::scratch() creates a random scratch-* subdirectory under that same root,
then nested canonical-service directories. prepare(), submit() and derive()
all depend on it. The readiness probe policy expects directory mask 4 access
to be denied. On a directory, that mask is CreateDirectories; it is distinct
from CreateFiles (mask 2).
Reference: https://learn.microsoft.com/en-us/dotnet/api/system.security.accesscontrol.filesystemrights

The intended restricted Runtime therefore cannot execute this workspace path.
This is a source-confirmed permission/operation conflict; it was not presented
as a reproduced installed-machine failure.

Existing readiness fixtures substitute inventory/observations. Native tests cover
selected file handles and startup refusals, not successful ceremony execution
with the installed state ACL. Their passing results do not refute this finding.

Correct the workspace layout and its permissions while preserving owner reference
protection. Prove preparation, signed submission and derivation through the real
ceremony with disposable credentials under equivalent Windows permissions.
Preserve failed/current packages and produce a new manifest for changed code.
