# Local isolation — exact owner setup and mission resume

This package is preparation, not a deployed-isolation result. Batches 3–5 require
owner actions below. Do not put a password/private key in this task, repository,
agent terminal or evidence. The agent has not created accounts or installed code.

Use the final package digest in docs/local-isolation-evidence-ledger.json; the
preaudit package is historical and must not be installed. The final package is
E:/htdocs/imperium/var/local-isolation-evidence/package-reviewed. Its manifest
hash binds every included code, dependency, PHP, PowerShell and target file.
No network/package installation is required. This route requires PowerShell
7.5+ (packaged 7.6.5) for exact JSON timestamp strings, PHP 8.4.14 plus Sodium.

## 1. Owner administrator: review and create the two proposed accounts

Review this runbook, the ledger, mission draft and the four installation/probe
scripts in the package before applying. The proposed account names are
PmaRuntime and PmaCaller. Both are new, dedicated standard accounts, with no
administrator membership. Existing application/sandbox/database accounts are
not reused. If either name or C:/ProgramData/Imperium already exists, stop and
review that discrepancy; these commands must not appropriate or replace it.

In your own elevated PowerShell 7 terminal, outside agent control:

```powershell
$ErrorActionPreference='Stop'
$package='E:\htdocs\imperium\var\local-isolation-evidence\package-reviewed'
$ledger=Get-Content 'E:\htdocs\imperium\docs\local-isolation-evidence-ledger.json' -Raw|ConvertFrom-Json
$digest=$ledger.package.manifest_sha256 # Compare with the task's final digest.
foreach($name in @('PmaRuntime','PmaCaller')) {
    if(Get-LocalUser -Name $name -ErrorAction SilentlyContinue){throw "Account exists: $name"}
    $password=Read-Host "New password for $name (owner custody only)" -AsSecureString
    try { New-LocalUser -Name $name -Password $password -Description 'Dedicated protected mission account' }
    finally { $password.Dispose() }
    $users=Get-LocalGroup -SID 'S-1-5-32-545'
    Add-LocalGroupMember -Group $users -Member $name
}
$runtime=(Get-LocalUser PmaRuntime).SID.Value
$caller=(Get-LocalUser PmaCaller).SID.Value
Get-LocalGroupMember (Get-LocalGroup -SID 'S-1-5-32-544')
& "$package\ProtectedMissionCode\tools\Install-LocalIsolationOwnerPackage.ps1" -Package $package -ManifestSha256 $digest -RuntimeSid $runtime -CallerSid $caller -WhatIf
```

Review the exact existing-account SIDs, package digest and proposed writes. Then
apply the identical command without `-WhatIf`. Owner review is required by the
campaign's Batch 3 boundary: account creation, privileged ACL changes and genuine
key custody cannot be supplied by a same-user agent rehearsal.

Planned writes are ONLY a fresh C:/ProgramData/Imperium parent and the siblings
ProtectedMissionCode, ProtectedMission, ProtectedMissionPHP, ProtectedMissionShell,
ProtectedMissionTarget and ProtectedMissionExchange. Administrator/SYSTEM own
immutable content and parent ACLs. Runtime modifies state/exchange only. Caller
reads public code but cannot read state/exchange or replace assets. PHP uses an
explicit minimal ini with absolute extension directory; no old ini/secrets copied.
Two harmless public-content canaries are created before enrollment.

No rollback deletes are automated. If account creation stops partway, keep the
created account disabled until owner review. If installation stops partway, keep
the directory and all evidence, enroll nothing, grant no caller route and do not
rerun the installer over it. Retiring a partial setup needs a separate reviewed
owner disposition. Never reset/migrate a journal or replace trust to recover.

## 2. Owner administrator: freeze and verify the installation

```powershell
$base='C:\ProgramData\Imperium'
$code="$base\ProtectedMissionCode"
$exchange="$base\ProtectedMissionExchange"
& "$code\tools\Test-LocalIsolationInstalledPackage.ps1" -Manifest "$exchange\package-manifest.json" -ManifestSha256 $digest > "$exchange\installed-hashes-admin.json"
if($LASTEXITCODE -ne 0){throw 'Native process failure'}
& "$code\tools\New-LocalIsolationProbePlans.ps1" -RuntimeSid $runtime -CallerSid $caller -OutputDirectory "$base\ProtectedMissionProbePlans"
# Inspect owner-acls.json: owner, group, ACL, attributes. Parent replacement is
# measured by the plans; SDDL text alone is not proof.
```

The verifier must say INSTALLED_BYTES_MATCH_REVIEWED_PACKAGE and reject any extra,
missing, changed or reparse file. Probe plans include every installed immutable
file/directory, Windows system PowerShell, ancestor replacement rights, metadata
and canaries. C:/Windows OS/runtime-loader security remains a trusted platform
assumption; this is not a whole-OS integrity audit. Ensure these are local fixed
NTFS volumes, no substituted drive/network mapping. No target hardlinks are made:
the preparer generated 23 fresh compressed object files, with original IDs checked.

## 3. Owner-operated actual Runtime and caller terminals: measure access

Log on interactively as each new account yourself. Do not transmit its password
to the agent or give the caller a Runtime shell. Open the protected PowerShell
binary with `-NoProfile`; do not enable a remoting service, generic broker, saved
credential launcher or caller-selected command. In EACH account's terminal:

```powershell
$ErrorActionPreference='Stop'
$base='C:\ProgramData\Imperium'
$out=Join-Path $env:USERPROFILE 'PmaIsolationResults'
New-Item -ItemType Directory -Path $out -ErrorAction Stop | Out-Null
whoami.exe /all > "$out\token.txt"
# Runtime uses Runtime-plan.json and runtime-access.json.
# Caller uses Caller-plan.json and caller-access.json.
& "$base\ProtectedMissionCode\tools\Test-LocalIsolationAccess.ps1" -Plan "$base\ProtectedMissionProbePlans\Runtime-plan.json" -Output "$out\runtime-access.json"
```

For the Caller substitute the two names as indicated. Both must report
RECORDED_ACCESS_EXPECTATIONS_MET, actual expected SID, no administrator token or
administrator group membership, no missing/unknown/skipped probe. Runtime must
positively open state canary read/write and all immutable content read-only.
Caller must be denied state read/write and all replacement rights. Handles only
are opened; no real bytes are printed or mutated. Any unexpected success is
ISOLATION_FAILED; stop before enrollment. Same-user tests satisfy none of this.

Runtime startup, BEFORE enrollment, in the actual Runtime terminal:

```powershell
$env:PHPRC="$base\ProtectedMissionPHP\php.ini"
$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
Set-Location "$base\ProtectedMissionCode"
& C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile -NonInteractive -File .\tools\Assert-ProtectedMissionInstallation.ps1 -CodePath "$base\ProtectedMissionCode" > "$out\startup-checker.txt"
if($LASTEXITCODE -ne 0){throw 'Runtime checker refused'}
& "$base\ProtectedMissionPHP\php.exe" --ini > "$out\php-ini.txt"
& "$base\ProtectedMissionPHP\php.exe" -m > "$out\php-modules.txt"
& "$base\ProtectedMissionPHP\php.exe" .\bin\protected-mission.php trust 2> "$out\preenrollment-refusal.txt"
if($LASTEXITCODE -ne 2 -or (Get-Content "$out\preenrollment-refusal.txt" -Raw).Trim() -cne 'PMA_TRUST_ABSENT'){throw 'Unexpected preenrollment result'}
```

Checker must emit PMA_INSTALLATION_ACL_AND_IDENTITY_VERIFIED. PHP trust absence
then proves startup passed through that checker, not that trust is enrolled.
Under Caller run the checker and the same PHP trust command; record nonzero
refusal. Never count generic absence as identity refusal: checker must explicitly
refuse, and caller access probe must deny installation metadata access. Administrator
must collect the two accounts' public result files and token outputs into exchange,
compare plan hashes to the generated plans and examine every row. Keep raw outputs
outside Git. Do not enroll until this review and installed hashes pass.

## 4. Independent Operator and Runtime owner: public trust, no secret transfer

Operator supplies only public-trust.json with identity (8–100 letters/digits/_/-),
competence APPROVE_CANONICAL_MISSION_PLAN, base64 Ed25519 public_key, integer Unix
not_before and expires_at. Its private key is generated/held in the Operator's
separate signing environment, never here. Independently confirm SHA-256 of the
decoded 32-byte public key through the owner. Record that public fingerprint.

Administrator places the reviewed public file in exchange. Runtime:

```powershell
. .\tools\ProtectedMission.ps1
$cli="$base\ProtectedMissionCode\bin\protected-mission.php"
$php="$base\ProtectedMissionPHP\php.exe"
$trust=Get-Content "$base\ProtectedMissionExchange\public-trust.json" -Raw
$fingerprint=Read-Host 'Independently confirmed PUBLIC fingerprint (64 lowercase hex)'
$enroll=Invoke-PmaProcess -Php $php -Script $cli -Arguments @('enroll',$fingerprint) -InputText $trust
if($enroll.ExitCode -ne 0){throw $enroll.Error}
$enroll.Output
$r=Invoke-PmaProcess -Php $php -Script $cli -Arguments @('trust')
if($r.ExitCode -ne 0){throw $r.Error}
$r.Output # public trust only; confirm fingerprint/identity/validity again
$bad=Invoke-PmaProcess -Php $php -Script $cli -Arguments @('request') -InputText '{"operation":"shell","arguments":[]}'
if($bad.ExitCode -ne 2 -or $bad.Error.Trim() -cne 'PMA_OPERATION_REFUSED'){throw 'Forwarding boundary failure'}
```

The Runtime owner accepts only reviewed data through the fixed `request` CLI.
Never interpolate data into a command, run caller PowerShell, or forward owner
enroll/prepare/control operations on the caller's demand. No unattended broker
exists. Record this human-mediated transport arrangement and refusal output.

Administrator generates a fresh second pair of probe plans with `-IncludeJournal`
into ProtectedMissionPostEnrollmentPlans. Both actual accounts run them as above
into fresh result filenames. Caller journal probes only open a handle; no journal
bytes are read. Collect and review again. Re-run installed hashes before ceremony.
Owner writes exchange/owner-readiness.json, with status
OWNER_REVIEWED_ACTUAL_DEPLOYMENT_READY, runtime_measurement_sha256 and
caller_measurement_sha256 (actual final output hashes), package digest, SIDs,
confirmed public fingerprint, startup results and human-transport statement.
This is an owner attestation, not a replacement for reviewing the measured files.
Provide those public results to this task; the agent must verify them before
continuing real Batches 4–5. Never provide journal, private key or passwords.

## 5. Runtime preparation, independent exact signing, one execution

After actual setup evidence is reviewed, use the installed PowerShell with the
protected environment above. All ceremony files stay in owner-only exchange.

```powershell
$exchange="$base\ProtectedMissionExchange"
$expiry=[DateTimeOffset]::UtcNow.AddHours(1).ToUnixTimeSeconds()
& $php .\tools\local-isolation.php draft "$exchange\target-inventory.json" "$base\ProtectedMissionTarget" $expiry > "$exchange\plan.json"
if($LASTEXITCODE -ne 0){throw 'Fresh plan refused'}
# Fresh identity/expiry, same exact 15 paths and 15/4000000/15/60 budgets.
& $php .\tools\local-isolation.php manifest "$base\ProtectedMissionTarget" > "$exchange\target-before.json"
if($LASTEXITCODE -ne 0){throw 'Target manifest failed'}
& .\tools\Invoke-LocalMission.ps1 -Action Prepare
```

Review all numbered lines and the full rendering; compare exported payload.json
SHA-256 and public fingerprint. Transfer only that exact payload and reviewed
signer/helper to the independently held-key environment. Operator runs the
Sign-PmaApproval SecureString sequence in protected-mission-operator-runbook.md.
Return only response.json to Runtime. No test key, previous payload or prior
generation is accepted. Never publish a pending payload or usable capability.

```powershell
& .\tools\Invoke-LocalMission.ps1 -Action Accept
& .\tools\Invoke-LocalMission.ps1 -Action Step # ADMITTED
& .\tools\Invoke-LocalMission.ps1 -Action Step # INSPECTING, after synchronous read
& .\tools\Invoke-LocalMission.ps1 -Action Step # COMPLETED
& .\tools\Invoke-LocalMission.ps1 -Action Status
```

Every command must succeed before the next. With `ErrorActionPreference=Stop`,
a thrown error terminates a script. Do not blindly paste subsequent commands
after a refusal in an interactive terminal. Status files record each stage;
the wrapper emits progress and elapsed call timing. No fabricated percentage or
unimplemented FAILED state is presented. On any lost response use Status first.
If challenge.json exists but authorization.json does not, Status queries its
persisted challenge ID; Accept can recover DERIVED without repeating derivation.
If prepare acknowledgement was lost before its ID was saved, preserve the attempt
and stop: no public list-by-mission API exists. Do not prepare another mission.
If an attempted consume did not visibly advance, its marker prohibits retry.
If status proves the exact nonce was consumed and advanced, Step selects the next
transition only. Keep all incident/attempt files; never remove a marker to retry.

## 6. Public reconstruction and separate analysis

Save the returned COMPLETED status as completed.json (from its retained status
file, no editing) and retain chain.json/public-trust.json. Then:

```powershell
& $php .\tools\local-isolation.php manifest "$base\ProtectedMissionTarget" > "$exchange\target-after.json"
if($LASTEXITCODE -ne 0){throw 'After manifest failed'}
& $php .\tools\local-isolation.php verify "$exchange\chain.json" "$exchange\completed.json" "$exchange\public-trust.json" "$exchange\target-inventory.json" "$base\ProtectedMissionTarget" "$exchange\target-before.json" "$exchange\target-after.json" > "$exchange\receipt-verification.json"
if($LASTEXITCODE -ne 0){throw 'Public receipt verification failed'}
& .\tools\Test-LocalIsolationInstalledPackage.ps1 -Manifest "$exchange\package-manifest.json" -ManifestSha256 $digest > "$exchange\installed-hashes-after.json"
```

Expected PUBLIC_RECEIPT_BYTES_AND_GENERATION_VERIFIED, 15 files, 448476 accepted
object bytes, commit a1fc4f27634319f2a22df2e6a1b370f70cdb98bf and tree
21780452f5815d4342f8f5c96923b2b276d9930a. Compare complete target manifests with
the package inventory too; unchanged from before/after alone cannot establish
the reviewed starting state. The verifier checks both object identities and bytes.

The receipt is a trusted Runtime record, not a separate signed semantic report.
Its capability signatures bind transitions; public verification does not prove
that a compromised Runtime could not fabricate a self-consistent transcript.
Elapsed deadlines require retained timing and trusted execution provenance.

Only after verified bytes return, the agent writes the separate discrepancy report
using docs/local-isolation-report-template.md. Cite exact claims, blobs/lines,
expected/observed behavior and confidence, distinguish historical text from current
flow, mark outside-allowlist claims unverified, and report zero if warranted.
Provide terminal public evidence after inspecting it for secrets/usable authority;
do not commit raw owner exchange. No provider, remediation, push, PR, merge or deletion.
