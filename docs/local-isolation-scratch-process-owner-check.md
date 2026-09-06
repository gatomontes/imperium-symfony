# Process correction — fresh owner candidate check

This is the next check in the existing protected scratch correction, not a new
campaign or real mission. The historical workspace
`C:\ProgramData\PmaScratchProof-7eb3b2a094064483be6143bb9ac5a293`, all keys/journals,
packages and the 304,942-row qualified pass remain untouched. Do not replace its
code, renew its authority or rerun its ceremony. Its broken-pipe attribution stays
unconfirmed. New results must refer to the new candidate and fresh workspace.

The implementation change has two boundaries: Invoke-PmaProcess sets the exact
Windows PowerShell child's PSModulePath via ProcessStartInfo.Environment;
InstalledRuntime sets it in the proc_open environment for the same checker.
The native system module directory is fixed to
`C:\Windows\System32\WindowsPowerShell\v1.0\Modules`. No parent, user or machine
environment is changed. PHP and PowerShell 7 retain their normal module environment.
The ceremony now loops over partial writes, records incomplete stdin delivery,
retains child exit status, and accepts the expected abandoned-prepare refusal
only on exact exit code 2, empty stdout and `PMA_INSTALLATION_CHECK_FAILED` stderr.
Success requires complete transport delivery and exit 0. The helper does not
replay an operation. Public process_input diagnostics contain only fixed operation
labels, exit code, delivery state and outcome, never child streams or private inputs.

## Administrator — new disposable setup

Use an elevated PowerShell **7.5+**, `-NoProfile` (tested 7.6.5). Do not run setup
under Windows PowerShell 5.1. Confirm the candidate digest from the audit and
review packet independently before applying this disposable setup. All commands
below are owner-run; passwords and private keys must never be sent to the agent.

```powershell
& {
    $ErrorActionPreference='Stop'
    if($PSVersionTable.PSVersion -lt [version]'7.5'){throw 'PowerShell 7.5+ required'}
    $id=[Security.Principal.WindowsIdentity]::GetCurrent()
    if(-not ([Security.Principal.WindowsPrincipal]::new($id)).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'Elevated Administrator required'}
    $audit=Get-Content 'E:\htdocs\imperium\docs\local-isolation-scratch-process-audit.json' -Raw|ConvertFrom-Json
    $package=$audit.package.path;$digest=$audit.package.manifest_sha256
    if((Get-FileHash "$package\package-manifest.json").Hash -cne $digest){throw 'Wrong package'}
    $runtime=Get-LocalUser PmaRuntime -ErrorAction Stop
    $caller=Get-LocalUser PmaCaller -ErrorAction Stop
    if(-not $runtime.Enabled -or -not $caller.Enabled -or $runtime.SID -eq $caller.SID){throw 'Enabled distinct existing accounts required'}
    $admins=@(Get-LocalGroupMember (Get-LocalGroup -SID 'S-1-5-32-544')|ForEach-Object{$_.SID.Value})
    if($runtime.SID.Value -in $admins -or $caller.SID.Value -in $admins){throw 'Standard accounts required'}
    $workspace='C:\ProgramData\PmaScratchProof-'+[guid]::NewGuid().ToString('N')
    & "$package\ProtectedMissionCode\tools\New-ProtectedScratchOwnerProof.ps1" -Package $package -ManifestSha256 $digest -Workspace $workspace -RuntimeSid $runtime.SID.Value -CallerSid $caller.SID.Value
    "$workspace\proof.json"
}
```

Record the new proof path. No account creation, historical installation replacement
or journal migration is permitted. On failure retain the partial workspace and stop.

## Runtime, then Caller — actual account terminals

From the owner's gatom terminal, open each role separately, entering its password
only in the local runas prompt. Clipboard stays on the owner desktop:

```powershell
runas.exe /profile /user:MiniMe\PmaRuntime "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile"
# Open Caller later, when its measurement step is due:
runas.exe /profile /user:MiniMe\PmaCaller "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile"
```

In each role window, paste the NEW public proof path, then launch its packaged PS7:

```powershell
$proof=Read-Host 'NEW public proof.json path'
$p=Get-Content $proof -Raw|ConvertFrom-Json
& "$($p.base)\ProtectedMissionShell\pwsh.exe" -NoProfile
```

At the new prompt run `$PSVersionTable.PSVersion.ToString()` and `whoami.exe /all`.
Verify Runtime SID -1007 / Caller SID -1008 and no Administrators group, including
deny-only. In PS7 restore `$proof` with Read-Host; variables do not cross child shells.
Keep all three role windows open. Do NOT manually sanitize PSModulePath: normal
PS7 module paths must remain inherited so this proves the corrected boundaries.

Continue the exact blocks in **local-isolation-scratch-owner-proof.md sections 2–4**
against this new proof only: Runtime fresh owner-private disposable key/canary;
Administrator public-trust import and pre plans; Runtime/Caller pre capture;
Caller canary; Administrator import/validate; Runtime enroll once; post captures
and validation; then current captures, validation and sealing. Public trust can be
copied as public JSON to an Administrator-readable file; never copy the private tree.
Use the original required expected startup outputs, without weakening any check.
Pre/post are reusable only in this new setup session; historical bundles are not.

Validate current and seal in the same Administrator step, then run the Runtime
ceremony immediately. The existing 15-minute freshness limit is unchanged. If it
expires before ceremony, preserve that attempt and create fresh current evidence;
never re-enroll or reset. Any failure after ceremony starts requires exact-failure
review, not a blind rerun. Keep any notices and process_input results.

The fresh check must establish normal PS7 environment → corrected collector and
PHP startup success under actual separate accounts, plus actual prepare/export/
render/sign/submit/derive/verify, clean scratch and abandoned-work/Status recovery.
Each normal process_input row must be complete/success; abandoned-prepare must be
expected_refusal with exit 2 (delivery may be complete or incomplete depending on
pipe timing). A partial write alone must never be reported as successful input.

Return only the public proof descriptor, canary outputs/plans/native tokens,
phase bundles/manifests, native phase token files, owner-readiness and new ceremony
result plus SHA-256s. Keep all keys, payload transport and journals owner-controlled.
This establishes new candidate evidence, not retrospective attribution of the old
broken-pipe notice and not deployment authority.

## Existing parent Batches 3–5

Next gate: independent review of these two corrections and the fresh owner result.
Then resume the existing parent only with authentic real owner setup, separately
held Operator trust/fingerprint, current measurements, exact reviewed plan and
real signature under its existing scope. The historical or new disposable key,
approval and readiness must not substitute for those prerequisites. No real
installation, provider invocation, target mutation or second mission is performed here.
