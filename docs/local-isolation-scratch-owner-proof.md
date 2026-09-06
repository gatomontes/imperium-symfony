# Disposable native proof — owner executable sequence

The c9516380 owner run is preserved as qualified historical evidence: 304,942
observations, successful authorization, cleanup, abandoned-work refusal and Status
recovery. See the owner native addendum under
var/local-isolation-evidence/owner-native-proof-public-93db28c4b3ea4f91a576d4100ee8f8a4/review-complete.
Its consumed authority must never be reused for this corrected candidate.

For the process correction candidate, start with
docs/local-isolation-scratch-process-owner-check.md and its exact package digest.
The new separate-account candidate run is NOT RUN until the owner executes it.
All Administrator/Runtime/Caller orchestration terminals require PowerShell 7.5+
(verified version 7.6.5), -NoProfile. Windows PowerShell 5.1 is only the fixed
checker child. Do not manually set PSModulePath: corrected child launch boundaries
set the native Windows module directory without changing the parent environment.

Requires an elevated owner terminal and two **existing distinct
standard accounts**. This creates only a fresh disposable relocation under
C:\ProgramData\PmaScratchProof-GUID; it neither creates accounts nor touches
C:\ProgramData\Imperium. Use owner-controlled terminals, outside agent control.
All keys below are disposable; no real private key/password belongs in this task.
Do not run against either historical package. Use the fresh scratch audit package.

Stop on every error and preserve the workspace. No rerun over an existing proof,
journal reset, unknown-workspace deletion or deployment migration is provided.
Review the three new owner-proof scripts and their exact packaged sources first.
The fixture uses production layout/ACL builders, installed CLI/startup checks,
finite native probe plans and readiness. Only fixed installation paths are
relocated; proof.json records every changed source hash and the relocated manifest.
No inventory/observation/checker replacement is part of this harness.

## 1. Elevated administrator — fresh disposable layout

```powershell
$ErrorActionPreference='Stop'
$audit=Get-Content 'E:\htdocs\imperium\docs\local-isolation-scratch-process-audit.json' -Raw|ConvertFrom-Json
$package=$audit.package.path;$digest=$audit.package.manifest_sha256
if((Get-FileHash "$package\package-manifest.json").Hash -cne $digest){throw 'Wrong package'}
$runtime=Read-Host 'PUBLIC SID of existing standard Runtime account'
$caller=Read-Host 'PUBLIC SID of existing distinct standard Caller account'
$workspace='C:\ProgramData\PmaScratchProof-'+[guid]::NewGuid().ToString('N')
& "$package\ProtectedMissionCode\tools\New-ProtectedScratchOwnerProof.ps1" -Package $package -ManifestSha256 $digest -Workspace $workspace -RuntimeSid $runtime -CallerSid $caller
$proof="$workspace\proof.json";$p=Get-Content $proof -Raw|ConvertFrom-Json
$base=$p.base;$code="$base\ProtectedMissionCode"
$proof # Public path to copy to both account terminals.
```

## 2. Actual Runtime — disposable signing custody and native operations

Use PowerShell 7.5+ with -NoProfile. Copy only the public proof.json path.

```powershell
$ErrorActionPreference='Stop'
$proof=Read-Host 'PUBLIC absolute proof.json path'
$p=Get-Content $proof -Raw|ConvertFrom-Json;$base=$p.base;$code="$base\ProtectedMissionCode"
$private=Join-Path $env:USERPROFILE ('PmaScratchPrivate-'+[guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory $private|Out-Null
$sid=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value
& icacls.exe $private /inheritance:r /grant:r ('*'+$sid+':(OI)(CI)(F)')
if($LASTEXITCODE -ne 0){throw 'Private fixture ACL failed'}
$env:PHPRC="$base\ProtectedMissionPHP\php.ini";$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$php="$base\ProtectedMissionPHP\php.exe"
& $php "$code\tools\Test-ProtectedScratchCeremony.php" keys $base $private
if($LASTEXITCODE -ne 0){throw 'Disposable key preparation failed'}
& "$code\tools\Test-ProtectedScratchOwnerProof.ps1" -Proof $proof -Action RuntimeCanary -Output "$private\runtime-canary.json"
$private # Transfer public-trust.json and public canary result only; never disposable.secret.
[IO.File]::ReadAllText("$private\public-trust.json") # Public JSON may be copied to an owner-readable file.
```

Administrator copies the public trust to its reserved slot without replacing ACL:

```powershell
$public=Read-Host 'PUBLIC path to Runtime disposable public-trust.json'
if([IO.File]::ReadAllText("$base\ProtectedMissionExchange\public-trust.json") -cne 'PMA_RESERVED_UNMEASURED'){throw 'Trust already populated'}
[IO.File]::WriteAllBytes("$base\ProtectedMissionExchange\public-trust.json",[IO.File]::ReadAllBytes($public))
$pre=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase pre|ConvertFrom-Json).directory
```

## 3. Both actual accounts — native phase measurements

Each account sets its own `$role` and the exact phase directory printed by admin.
Repeat this block for pre, then post, then current. Keep every profile output.

```powershell
$ErrorActionPreference='Stop'
$proof=Read-Host 'PUBLIC proof.json path'
$p=Get-Content $proof -Raw|ConvertFrom-Json;$base=$p.base;$code="$base\ProtectedMissionCode"
$role=Read-Host 'Runtime or Caller for this actual account'
$bundle=Read-Host 'PUBLIC exact phase directory'
$out=Join-Path $env:USERPROFILE ('PmaScratchResults-'+[guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory $out|Out-Null
whoami.exe /all > "$out\token.txt"
& "$code\tools\Test-LocalIsolationAccess.ps1" -Plan "$bundle\$role-plan.json" -Output "$out\$role-access.json"
& "$code\tools\Collect-LocalIsolationStartup.ps1" -Plan "$bundle\$role-plan.json" -Output "$out\$role-startup.json"
$out
```

Caller additionally runs once, after RuntimeCanary has created its nested files:

```powershell
& "$code\tools\Test-ProtectedScratchOwnerProof.ps1" -Proof $proof -Action CallerCanary -Output "$out\caller-canary.json"
```

Administrator reviews native token groups including deny-only entries, confirms
both accounts are standard, and imports exact profile files after each phase:

```powershell
$bundle=$pre # Later use $post, then $current.
foreach($role in @('Runtime','Caller')){
 $out=Read-Host "PUBLIC exact $role result directory for this phase"
 & "$code\tools\Import-LocalIsolationMeasurement.ps1" -Directory $bundle -Role $role -Access "$out\$role-access.json" -Startup "$out\$role-startup.json"
}
& "$code\tools\Test-LocalIsolationPhase.ps1" -Directory $bundle
```

After pre succeeds, Runtime enrolls the disposable public trust once:

```powershell
& "$base\ProtectedMissionPHP\php.exe" "$code\tools\Test-ProtectedScratchCeremony.php" enroll $base $private
if($LASTEXITCODE -ne 0){throw 'Enrollment refused; preserve'}
```

Administrator creates `$post`, repeats the complete capture/import/phase checks,
then creates `$current` and repeats again:

```powershell
$post=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase post|ConvertFrom-Json).directory
# Complete post captures/import/check before creating current.
$current=(& "$code\tools\New-LocalIsolationProbePlans.ps1" -Phase current|ConvertFrom-Json).directory
# Complete current captures/import/check before sealing.
$trust=Get-Content "$base\ProtectedMissionExchange\public-trust.json" -Raw|ConvertFrom-Json
$fingerprint=[Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Convert]::FromBase64String($trust.public_key))).ToLowerInvariant()
& "$code\tools\Seal-LocalIsolationReadiness.ps1" -Pre $pre -Post $post -Current $current -PublicFingerprint $fingerprint -Transport 'Disposable owner-operated actual account terminals; no caller Runtime command access' -Custody 'Owner personally reviewed and imported the exact native account observations'
```

Use custody statements only if true. Missing/failed/unknown native results refuse.
Keep both account terminals ready for current capture. Validate and seal in the
same Administrator step, then immediately run the Runtime ceremony. If freshness
expires before the PHP ceremony starts, retain the failed current bundle and
create a new current bundle only; keep pre/post and enrollment. Never reset a journal.

## 4. Runtime — actual ceremony, native abandonment and Status recovery

Within 15 minutes of current measurements, in the Runtime terminal retaining
`$private` (or restoring its exact private profile path):

```powershell
& "$code\tools\Test-ProtectedScratchOwnerProof.ps1" -Proof $proof -Action Ceremony -Private $private -Output "$private\ceremony-result.json"
```

This validates real readiness, invokes actual installed prepare, exact export and
render, signs only with the disposable key, submits, derives and verifies through
the production CLI/startup checks. Payloads remain in memory; no new exchange
files invalidate current coverage during this bounded proof. It creates a known
inert abandoned-workspace probe, proves new prepare refusal and actual Status
recovery without journal changes, then removes only that known probe. No issue,
consume, inspection, provider or target effect occurs. Real mission authority is
never claimed. Do not substitute its disposable key or approval for parent Batches 3–5.

Return only proof.json, both canary results and their plans/native token records,
pre/post/current public bundles, owner-readiness.json, ceremony-result.json and
their SHA256s. Review all finite native rows, especially scratch root and owner
reference direct/parent replacement denials. Preserve the private proof journal
and key locally; do not upload them. Unknown leftovers require owner inspection;
there is no generic delete command. The separate local PHPUnit native test covers
denied cleanup and actual junction refusal; report those as component evidence,
distinct from this equivalent ceremony run. Any unexecuted step remains NOT RUN.

The corrected ceremony-result.json includes process_input: operation label, child
exit code, complete/incomplete pipe delivery, and success/expected_refusal. This
reports transport delivery, not a claim that pipe acceptance alone proves consumption.
Normal calls require full delivery and exit zero; only abandoned-prepare accepts
the exact exit-2 PMA_INSTALLATION_CHECK_FAILED refusal even if stdin closed early.
Unknown errors, short delivery with exit zero, or a different refusal stop the run.
No automatic process retry is permitted. Diagnostics exclude child output, input,
signatures, capabilities and private material. Retain any new exception verbatim.
