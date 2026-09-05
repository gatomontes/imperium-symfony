# Protected mission Operator ceremony

Implementation commands below use PHP 8.4+, Sodium, and PowerShell 7. The same CLI implementation
has been exercised in independent processes using newly generated disposable identities. No real
Operator enrollment, signing or mission execution was performed. Same-user proof is not deployment
isolation. **DEPLOYMENT_ISOLATION_UNPROVED_OPERATOR_SETUP_REQUIRED**.

## Human deployment prerequisite

The deployment owner must install a reviewed immutable code copy and PHP under an account separate
from the untrusted caller. On Windows the authoritative state path is fixed at
`C:/ProgramData/Imperium/ProtectedMission`; it cannot be changed by arguments or environment.
Only Runtime and deployment administrators may read/write this directory, because the journal
contains the issuer secret. Callers must not write the installed code, PHP executable, autoloader,
libraries or parent directories. Callers may submit data to a Runtime operator through the stdio
command below; they must never receive a generic shell or PHP execution under the Runtime account.
There is no unattended service launcher or credential impersonation in this implementation.

The reviewed Windows installer uses fixed code path
`C:/ProgramData/Imperium/ProtectedMissionCode` and records distinct existing Runtime/caller SIDs.
It creates no accounts and enrolls no trust. It refuses an existing installation and sets explicit
administrator/SYSTEM-owned ACLs: Runtime may modify state, callers cannot read state, and neither
Runtime nor callers may modify installed code or the installation record. Use a separately secured
PHP executable, extensions, ini files and Runtime environment; these are trusted deployment inputs.

```powershell
# Review the plan first; replace SIDs with the two existing dedicated accounts.
.\tools\Install-ProtectedMission.ps1 -Source $PWD.Path -RuntimeSid 'RUNTIME_ACCOUNT_SID' -CallerSid 'CALLER_ACCOUNT_SID' -WhatIf
# Later, explicitly authorized human administrator only: rerun without -WhatIf.
```

Startup invokes the installed ACL checker using Windows' system PowerShell. It refuses a wrong
Runtime identity, an administrator Runtime token, unsafe state/code ACLs, unexpected code path,
or reparse points. Non-Windows production installation is currently unsupported. Only installer
dry-run and checker refusal were tested locally; successful real-account enforcement is UNPROVED.

Before production use, run the checker under the actual Runtime identity, and test denied access
under the actual caller identity. Do not print or read journal contents while probing access:

```powershell
# Under Runtime, after the human installation:
& C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile -File C:\ProgramData\Imperium\ProtectedMissionCode\tools\Assert-ProtectedMissionInstallation.ps1 -CodePath C:\ProgramData\Imperium\ProtectedMissionCode
# Under the caller, after public enrollment creates the journal:
function Test-PmaDeniedOpen([string]$Path, [IO.FileAccess]$Access) {
    try { $probe = [IO.File]::Open($Path, [IO.FileMode]::Open, $Access, [IO.FileShare]::ReadWrite) }
    catch [UnauthorizedAccessException] { return 'DENIED_AS_REQUIRED' }
    if ($probe) { $probe.Dispose(); throw 'UNSAFE_ACCESS_SUCCEEDED; stop deployment' }
}
Test-PmaDeniedOpen 'C:\ProgramData\Imperium\ProtectedMission\authority.journal' Read
Test-PmaDeniedOpen 'C:\ProgramData\Imperium\ProtectedMission\installation.json' Write
Test-PmaDeniedOpen 'C:\ProgramData\Imperium\ProtectedMissionCode\bin\protected-mission.php' Write
```

File absence or another error is not proof of denied access. Record real identities and probe
results before claiming isolation. Bootstrap is an explicit human deployment-owner action in the
Runtime terminal; an application caller cannot enroll through `request`. No real account/ACL setup
is authorized by this local campaign. These unexecuted installation steps remain a human gate.

## Executable commands

Run the owner-side commands under the separately installed Runtime account. Paths below refer to
that reviewed installation. Public inputs contain no private key. Check every ExitCode before
continuing. `$challenge` and `$authorization` come from actual command output.

```powershell
. .\tools\ProtectedMission.ps1
$cli = (Resolve-Path .\bin\protected-mission.php).Path
$trust = Get-Content -LiteralPath .\public-trust.json -Raw
# Independently confirm the SHA-256 of decoded public_key with the deployment owner.
$fingerprint = 'REPLACE_WITH_CONFIRMED_PUBLIC_KEY_SHA256'
$enrolled = Invoke-PmaProcess -Script $cli -Arguments @('enroll', $fingerprint) -InputText $trust
if ($enrolled.ExitCode -ne 0) { throw $enrolled.Error }
$prepared = Invoke-PmaProcess -Script $cli -Arguments @('prepare') -InputText (Get-Content .\plan-and-disclosures.json -Raw)
if ($prepared.ExitCode -ne 0) { throw $prepared.Error }
$challenge = ($prepared.Output | ConvertFrom-Json).challenge_id
$export = Invoke-PmaProcess -Script $cli -Arguments @('export', $challenge)
Write-PmaCanonicalPayload -Result $export -Path (Join-Path $PWD 'payload.json')
$render = Invoke-PmaProcess -Script $cli -Arguments @('render', $challenge)
if ($render.ExitCode -ne 0) { throw $render.Error }
$render.Output
```

Public trust JSON requires `identity` (8–100 letters/digits/underscore/hyphen),
`competence: APPROVE_CANONICAL_MISSION_PLAN`, `public_key` (base64 Ed25519 public key),
and integer Unix `not_before` / `expires_at`. Replacement enrollment refuses. Rotation is a signed
`imperium.protected-control/v1` request under existing trust; revocation/supersession use the same
common lock. Lost-key recovery requires offline owner retirement of the entire authority store and
a separately reviewed installation with new trust; never silently replace a key in an active store.

The plan input shape is exercised by `ProtectedMissionFixture::input()`: `mission` and all eleven
canonical `disclosures` sections. The mission binds ID, exact repository/commit/tree, exact paths,
positive bounded file/byte/finding/time budgets, expiry within 24 hours, permission
`READ_EXACT_GIT_OBJECTS`, prohibitions `NETWORK`, `TARGET_MUTATION`, `PROVIDERS`, `CREDENTIALS`,
and the three fixed protected-git-inspector transitions. No model/provider commission is included.
Preparation accesses supplied material only. Preparing an amendment supersedes that mission's
pending challenge and invalidates existing authorization for the affected mission.

The implemented inspector supports local loose SHA-1 objects only. Packed repositories, worktree
indirection and symlink blobs refuse. Budget bytes count accepted inflated commit/tree/blob content;
compressed/header parsing has a bounded 1024/128-byte overhead per object. No Git subprocess,
configuration, hook or lazy fetch runs. A separate worker deadline is subject to OS scheduling and
termination latency. Local-volume placement is a deployment requirement. Raw file content remains
mechanical receipt evidence and is not admitted to internal cognition or an external provider flow.

The authority journal refuses writes beyond 64 MiB and individual states beyond 16 MB. It is not
an unbounded production database. Capacity exhaustion is a stop condition requiring owner-reviewed
maintenance; there is no automatic compaction or silent reset. Partial crash tails are recoverable;
complete corruption refuses. Hardware power-loss durability remains unmeasured.

Move the public payload to the independently held-key environment. Inspect the complete numbered
dossier and full rendering; compare the exported SHA-256. Use the helper and signer from a reviewed
copy there. The key must never pass through the implementation agent, chat, repository or logs.

```powershell
. .\tools\ProtectedMission.ps1
$held = Read-Host 'Separately held Ed25519 secret, base64' -AsSecureString
try { Sign-PmaApproval -Payload (Join-Path $PWD 'payload.json') -Response (Join-Path $PWD 'response.json') -HeldKey $held }
finally { $held.Dispose() }
```

The signer accepts only exact canonical UTF-8 bytes without BOM/newline, verifies the public
fingerprint, and refuses an existing response path. It receives the secret through stdin and outputs
only a detached signature. SecureString reduces accidental display; PHP and .NET still necessarily
hold secret bytes in memory. This does not resist compromise of the signer's account/process.

Return only the signed response to the Runtime operator:

```powershell
$submitted = Invoke-PmaProcess -Script $cli -Arguments @('submit') -InputText (Get-Content .\response.json -Raw)
if ($submitted.ExitCode -ne 0) { throw $submitted.Error }
$derived = Invoke-PmaProcess -Script $cli -Arguments @('derive', $challenge)
if ($derived.ExitCode -ne 0) { throw $derived.Error }
$authorization = ($derived.Output | ConvertFrom-Json).authorization_id
$verified = Invoke-PmaProcess -Script $cli -Arguments @('verify', $authorization)
if ($verified.ExitCode -ne 0) { throw $verified.Error }
$verified.Output
(Invoke-PmaProcess -Script $cli -Arguments @('challenge-status', $challenge)).Output
(Invoke-PmaProcess -Script $cli -Arguments @('status', $authorization)).Output
```

`help` is available without installation; commands return 0 for success and 2 for refusal/input
errors. Rejected submissions create no approval or authorization. Pending previews contain planned
review IDs but have no authority. Authenticated review records store the signature, challenge,
fingerprint and submitted time. The preallocated `reviewed_at` is part of the signed proposal;
`submitted_at` records Runtime acceptance time. Derivation uses the canonical service's actual ID.
Approval and verification do not execute a mission. This campaign does not authorize using these
instructions with real trust or running any real target.
