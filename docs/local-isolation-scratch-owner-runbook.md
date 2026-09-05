SCRATCH_WORKSPACE_CORRECTION_NATIVE_PROOF_PENDING.

First complete docs/local-isolation-scratch-owner-proof.md after independent review.
That proof creates only a disposable relocation with existing standard accounts.
No real installation or account creation is performed by this correction task.
The deployment commands below are the existing parent Batches 3–5 continuation,
for future owner execution only when their authentic prerequisites exist.
Both package-47bcc44a and package-readiness-6bb2628d remain historical and must not
be installed. Use the newly hashed package in local-isolation-scratch-audit.json.

Scratch is an administrator-provisioned sibling, ProtectedMissionScratch.
Runtime creates nested work there and cleans before authority publication;
Caller is excluded. State/exchange owner-reference permissions stay unchanged.
During active work, phase measurement refuses busy scratch; wait for the command
to finish and verify the clean layout. A leftover workspace is an incident:
stop mutation, preserve it and all attempt markers, and use Status with saved IDs.
Status still checks identity, code and state but does not need valid scratch or
readiness. Never delete unknown work or reset/migrate authority to recover.
No automatic retry or generic scratch cleanup command is authorized.

# Owner setup and resume — protected scratch correction

Use only the fresh package named in `docs/local-isolation-scratch-audit.json`.
The earlier package-47bcc44a and original runbook remain historical evidence;
do not install that candidate. This runbook supersedes its readiness commands.
No real installation, accounts, trust, signature or mission has been performed
by the implementation agent. All commands below are future owner actions.

Required public inputs: actual Runtime/Caller SIDs and token group sets; reviewed
fresh package digest; independently held Operator public trust and independently
confirmed fingerprint; owner custody/transport statements; actual pre/post/current
measurements and native startup results; fresh exact plan expiry and authentic
signed response. Never supply passwords, private keys, journals or issuer state.

Use owner-controlled terminals, outside agent control, PowerShell 7.5+ and the
packaged PHP 8.4.14. Runtime and Caller must be distinct dedicated standard
accounts. Administrators, a compromised trusted Runtime and signing environment,
OS integrity and human forwarding are explicit premises, not excluded attackers.
The caller receives no Runtime shell, command selection, password or PHP execution.

## 1. Owner administrator: review and fresh installation

Read this runbook, C0/C1/C2 audit, reviewed policy module and all setup/probe scripts
before applying anything. In an elevated owner PowerShell 7 terminal:

```powershell
$ErrorActionPreference='Stop'
$audit=Get-Content 'E:\htdocs\imperium\docs\local-isolation-scratch-audit.json' -Raw|ConvertFrom-Json
$package=$audit.package.path
$digest=$audit.package.manifest_sha256 # Compare independently with task/packet.
if((Get-FileHash "$package\package-manifest.json").Hash -cne $digest){throw 'Wrong package'}
if(Test-Path 'C:\ProgramData\Imperium'){throw 'Existing installation: preserve; no replacement'}
foreach($name in @('PmaRuntime','PmaCaller')){
    if(Get-LocalUser -Name $name -ErrorAction SilentlyContinue){throw "Existing account: $name"}
}
# Only after reviewing the two new standard accounts and fresh paths:
foreach($name in @('PmaRuntime','PmaCaller')){
    $password=Read-Host "New owner-custody password for $name" -AsSecureString
    try{New-LocalUser -Name $name -Password $password -Description 'Dedicated protected mission account'}
    finally{$password.Dispose()}
    $users=Get-LocalGroup -SID 'S-1-5-32-545'
    if(-not(Get-LocalGroupMember $users|Where-Object{$_.SID.Value -eq (Get-LocalUser $name).SID.Value})){
        Add-LocalGroupMember -Group $users -Member $name
    }
}
$runtime=(Get-LocalUser PmaRuntime).SID.Value
$caller=(Get-LocalUser PmaCaller).SID.Value
Get-LocalGroupMember (Get-LocalGroup -SID 'S-1-5-32-544') # Neither may be a member.
& "$package\ProtectedMissionCode\tools\Install-LocalIsolationOwnerPackage.ps1" -Package $package -ManifestSha256 $digest -RuntimeSid $runtime -CallerSid $caller -WhatIf
# Review the exact proposed changes, then apply from this owner-only terminal:
& "$package\ProtectedMissionCode\tools\Install-LocalIsolationOwnerPackage.ps1" -Package $package -ManifestSha256 $digest -RuntimeSid $runtime -CallerSid $caller
```

Planned changes are a fresh `C:\ProgramData\Imperium` and its code, state, protected scratch, PHP,
shell, target, exchange and two probe-plan siblings. The installer copies reviewed
bytes, writes protected session/SID metadata and harmless canaries, and reserves
owner-readiness/public-trust slots. Runtime can add exchange/state files, but
cannot delete owner-controlled children through their parent. Both identities
read plans; neither modifies them. Caller cannot read exchange/state.

On partial setup preserve all directories/evidence; enroll nothing and do not
rerun over them. Disable only an account confirmed newly created by this attempt
pending owner review (`Disable-LocalUser -Name PmaRuntime`, or PmaCaller as
applicable). Never disable a pre-existing account, delete branches, remove markers,
reset/migrate a journal or replace trust. There is no automated destructive rollback.

## 2. Pre-enrollment measurement

Administrator, using the installed protected PowerShell with `-NoProfile`:

```powershell
$base='C:\ProgramData\Imperium';$code="$base\ProtectedMissionCode"
$exchange="$base\ProtectedMissionExchange"
& "$code\tools\Test-LocalIsolationInstalledPackage.ps1" -Manifest "$exchange\package-manifest.json" -ManifestSha256 $digest
$pre=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase pre|ConvertFrom-Json).directory
$pre
```

Each owner-operated actual account logs on privately and runs the following in
`C:\ProgramData\Imperium\ProtectedMissionShell\pwsh.exe -NoProfile`. Set `$role`
to `Runtime` in PmaRuntime and `Caller` in PmaCaller; paste the exact public `$pre`
directory printed above as `$bundle`. No `runas` credentials enter this task.

```powershell
$ErrorActionPreference='Stop'
$base='C:\ProgramData\Imperium';$code="$base\ProtectedMissionCode"
$role='Runtime' # Caller uses 'Caller' in its own actual terminal.
$bundle=Read-Host 'PUBLIC exact fresh phase directory printed by administrator'
$out=Join-Path $env:USERPROFILE ('PmaIsolationResults\'+(Split-Path $bundle -Leaf))
if(Test-Path $out){throw 'Existing account evidence: preserve, do not overwrite'}
New-Item -ItemType Directory -Path $out|Out-Null
whoami.exe /all > "$out\token.txt"
& "$code\tools\Test-LocalIsolationAccess.ps1" -Plan "$bundle\$role-plan.json" -Output "$out\$role-access.json"
& "$code\tools\Collect-LocalIsolationStartup.ps1" -Plan "$bundle\$role-plan.json" -Output "$out\$role-startup.json"
$out
```

Administrator imports each role's exact public files, using the profile evidence
directory printed by that account. Imports only fill reserved slots once.

```powershell
$runtimeOut=Read-Host 'PUBLIC exact Runtime profile evidence directory'
$callerOut=Read-Host 'PUBLIC exact Caller profile evidence directory'
& "$code\tools\Import-LocalIsolationMeasurement.ps1" -Directory $pre -Role Runtime -Access "$runtimeOut\Runtime-access.json" -Startup "$runtimeOut\Runtime-startup.json"
& "$code\tools\Import-LocalIsolationMeasurement.ps1" -Directory $pre -Role Caller -Access "$callerOut\Caller-access.json" -Startup "$callerOut\Caller-startup.json"
& "$code\tools\Test-LocalIsolationPhase.ps1" -Directory $pre
```

Expect EXACT_PHASE_OBSERVATIONS_VALIDATED_NOT_OWNER_CUSTODY_PROOF. Inspect every
row/token and actual custody. Runtime checker succeeds followed by exact
PMA_TRUST_ABSENT; Caller checker and CLI both return exit 2 with exact
PMA_RUNTIME_IDENTITY_REFUSED. Generic errors, file absence, filtered administrator
groups, unknown results and skipped rows refuse. Native probes only open handles;
they do not read journal bytes or attempt operational writes/deletion.

## 3. Independent public trust and post-enrollment measurement

The independent Operator supplies only public-trust.json: `identity` (8–100
letters/digits/_/-), competence `APPROVE_CANONICAL_MISSION_PLAN`, base64 32-byte
Ed25519 `public_key`, integer Unix `not_before` and `expires_at`. Its key stays
outside this task. Administrator independently confirms the public fingerprint,
then fills the reserved public-trust slot once without replacing its ACL:

```powershell
$publicFile=Read-Host 'PUBLIC path to independently supplied public-trust.json'
if([IO.File]::ReadAllText("$exchange\public-trust.json") -cne 'PMA_RESERVED_UNMEASURED'){throw 'Trust slot already filled: preserve and review'}
[IO.File]::WriteAllBytes("$exchange\public-trust.json",[IO.File]::ReadAllBytes($publicFile))
```

In the actual Runtime's protected terminal:

```powershell
$base='C:\ProgramData\Imperium';$code="$base\ProtectedMissionCode";$exchange="$base\ProtectedMissionExchange"
$env:PHPRC="$base\ProtectedMissionPHP\php.ini";$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
Set-Location $code
. .\tools\ProtectedMission.ps1
$php="$base\ProtectedMissionPHP\php.exe";$cli="$code\bin\protected-mission.php"
$fingerprint=Read-Host 'Independently confirmed PUBLIC fingerprint (64 lowercase hex)'
$enroll=Invoke-PmaProcess -Php $php -Script $cli -Arguments @('enroll',$fingerprint) -InputText (Get-Content "$exchange\public-trust.json" -Raw)
if($enroll.ExitCode -ne 0){throw $enroll.Error}
$enroll.Output
$bad=Invoke-PmaProcess -Php $php -Script $cli -Arguments @('request') -InputText '{"operation":"shell","arguments":[]}'
if($bad.ExitCode -ne 2 -or $bad.Error.Trim() -cne 'PMA_OPERATION_REFUSED'){throw 'Forwarding boundary failure'}
```

Retain the public enrollment/refusal outputs in the account profile. Administrator:

```powershell
$post=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase post|ConvertFrom-Json).directory
```

Repeat every
account capture and import command in section 2 with `$post`, then run
`Test-LocalIsolationPhase.ps1 -Directory $post`. This adds journal-handle coverage
and exact enrolled trust startup. Any failure stops the real route.

## 4. Current evidence and first preparation

Actual Runtime creates the one inert draft's fresh-expiry plan and target-before
manifest **before** the current measurement. No replacement is permitted:

```powershell
. .\tools\LocalMission.ps1
$expiry=[DateTimeOffset]::UtcNow.AddHours(2).ToUnixTimeSeconds()
$draft=& $php .\tools\local-isolation.php draft "$exchange\target-inventory.json" "$base\ProtectedMissionTarget" $expiry
if($LASTEXITCODE -ne 0){throw 'Draft failed'}
Write-PmaNewJson ($draft|ConvertFrom-Json -AsHashtable -DateKind String) "$exchange\plan.json"
$before=& $php .\tools\local-isolation.php manifest "$base\ProtectedMissionTarget"
if($LASTEXITCODE -ne 0){throw 'Target manifest failed'}
Write-PmaNewJson ($before|ConvertFrom-Json -AsHashtable) "$exchange\target-before.json"
```

Administrator:

```powershell
$current=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase current|ConvertFrom-Json).directory
```

Both accounts repeat capture/import with that fresh directory. Then, administrator:

```powershell
$fingerprint=Read-Host 'Independently confirmed PUBLIC fingerprint (64 lowercase hex)'
& "$code\tools\Seal-LocalIsolationReadiness.ps1" -Pre $pre -Post $post -Current $current -PublicFingerprint $fingerprint -Transport 'Owner-mediated fixed request stdio; caller has no Runtime shell or command selection' -Custody 'Administrator personally collected exact outputs from the named actual account terminals and reviewed tokens and rows'
& "$code\tools\Test-LocalIsolationReadiness.ps1"
if($LASTEXITCODE -ne 0){throw 'Readiness refused'}
```

Use those owner statements only if true. Expect LOCAL_ISOLATION_READINESS_VALID.
The seal archives previous readiness bytes in the new current bundle before
updating the owner-protected pointer. Raw operational exchange remains private.
Supply this task the public readiness pointer, all three exact bundles/manifests,
token outputs, package digest, public trust and authentic custody facts for review.

The actual Runtime can now run `& .\tools\Invoke-LocalMission.ps1 -Action Prepare`.
It revalidates all evidence/installed bytes and actual enrolled public trust before
any attempt marker or preparation. Current measurements/startup/readiness have a
15-minute window. Pre/post are historical session-bound observations, not a claim
of current access: they remain bound to unchanged retained inventory, exact bytes
and phase ordering. This C2 phase model avoids demanding pre-enrollment again from
an already enrolled journal; no reset or migration is permitted.

## 5. Exact signing, renewal, execution and recovery

Review every numbered line and the full canonical payload, exact historical target
a1fc4f27634319f2a22df2e6a1b370f70cdb98bf / tree
21780452f5815d4342f8f5c96923b2b276d9930a, 15 allowlisted paths, ceilings 15 files /
15 findings / 4,000,000 inflated bytes / 60 seconds and fresh expiry. Keep the
installed build, inspected target and later evidence commit distinct.

Transfer only payload.json and reviewed signing code/dependencies to the independent
key-holder environment. Never transfer authority state or the whole exchange.
In that independent terminal, outside agent custody:

```powershell
$ErrorActionPreference='Stop'
$reviewedCode=(Resolve-Path (Read-Host 'PUBLIC path to reviewed code copy')).Path
$signingPhp=(Resolve-Path (Read-Host 'PUBLIC path to PHP 8.4+ with Sodium')).Path
$payload=(Resolve-Path (Read-Host 'PUBLIC path to exact payload.json')).Path
$response=Join-Path (Split-Path $payload) 'response.json'
if(Test-Path $response){throw 'Existing signature: preserve'}
Get-FileHash $payload # Compare canonical digest independently.
$env:PATH=(Split-Path $signingPhp)+';'+$env:PATH
if((Get-Command php).Source -ine $signingPhp){throw 'Signer PHP mismatch'}
. "$reviewedCode\tools\ProtectedMission.ps1"
$held=Read-Host 'Separately held Ed25519 secret, base64' -AsSecureString
try{Sign-PmaApproval -Payload $payload -Response $response -HeldKey $held}
finally{$held.Dispose()}
```

Return only the authentic response privately to Runtime, as a fresh response.json.
Prepare created new outputs, and the signature is another new output. Generate a
**new current bundle**, repeat both actual-account captures/imports and seal with
the preserved `$pre`/`$post` before Accept. Repeat current measurement and seal
before **each** Step, because prior actions created further finite exchange files.
Reuse no old current bundle; keep all directories, evidence and attempt markers.

```powershell
& .\tools\Invoke-LocalMission.ps1 -Action Accept
# Renew current evidence and seal after each preceding mutation:
& .\tools\Invoke-LocalMission.ps1 -Action Step # ADMITTED
# Renew current evidence and seal:
& .\tools\Invoke-LocalMission.ps1 -Action Step # INSPECTING after synchronous read
# Renew current evidence and seal:
& .\tools\Invoke-LocalMission.ps1 -Action Step # COMPLETED
& .\tools\Invoke-LocalMission.ps1 -Action Status
```

Stop after every exception; do not paste later actions after refusal. Status stays
available when readiness is missing/stale/invalid, writes no snapshot or authority,
and uses saved authorization/challenge IDs. Its output may be retained manually
in an owner profile. Lost prepare acknowledgement before ID persistence requires
preservation and review; there is no list-by-mission recovery API. Accept can
recover a saved DERIVED challenge without repeating derivation. An uncertain
issue/consume marker prohibits replay. Never remove markers to retry. Confirmed
advancement permits only the next transition after current evidence renewal.

## 6. Parent Batches 4–5 completion

After authentic setup evidence/signature exist, resume the already selected parent
without a new campaign. No second mission, provider or target remediation.
Save exact completed status as a new completed.json; use persisted authorization
to confirm COMPLETED. Capture a fresh target-after manifest and run:

```powershell
$completed=& .\tools\Invoke-LocalMission.ps1 -Action Status
if($completed.lifecycle.state -cne 'COMPLETED'){throw 'Mission not complete'}
Write-PmaNewJson $completed "$exchange\completed.json"
$after=& $php .\tools\local-isolation.php manifest "$base\ProtectedMissionTarget"
if($LASTEXITCODE -ne 0){throw 'After manifest failed'}
Write-PmaNewJson ($after|ConvertFrom-Json -AsHashtable) "$exchange\target-after.json"
& $php .\tools\local-isolation.php verify "$exchange\chain.json" "$exchange\completed.json" "$exchange\public-trust.json" "$exchange\target-inventory.json" "$base\ProtectedMissionTarget" "$exchange\target-before.json" "$exchange\target-after.json"
if($LASTEXITCODE -ne 0){throw 'Receipt reconstruction refused'}
& .\tools\Test-LocalIsolationInstalledPackage.ps1 -Manifest "$exchange\package-manifest.json" -ManifestSha256 $digest
```

Expect PUBLIC_RECEIPT_BYTES_AND_GENERATION_VERIFIED, 15 files and 448476 inflated
object bytes. Compare target manifests with the package's starting target too.
Then produce the separate analyst report using the preserved report template.
Mechanical receipt bindings do not certify semantic conclusions or a compromised
Runtime's honesty. Windows-only/local loose SHA-1, bounded journal capacity,
unavailable migration, unmeasured hardware power loss and trusted OS/admin/Runtime/
signing custody remain explicit limits. Local readiness is not independent acceptance.
