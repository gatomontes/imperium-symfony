# Existing parent Batch 3 — prerequisite inventory, 2026-09-06

This resumes Batch 3 of next-campaign-local-isolation-useful-mission.md, using
local-isolation-scratch-owner-runbook.md. It is not a new campaign. Independent
review acceptance was supplied by the owner in this task; both process corrections
close within the fresh disposable proof. Real deployment isolation remains unproved.

Entry branch: codex/local-isolation-scratch-correction.
Entry HEAD: b9fcbcd54e9e7e6623fd5edc12aa665f8baa16c0.
Entry tree: 0038aa1ec8d74a84dc52a8f0f61dec913fe5e7de.
Only the four previous proof-review documentation changes were present. No later
code or unrelated work was found. No applicable AGENTS.md was found in repository
or E:/ and E:/htdocs ancestors. No package, proof workspace or journal was changed.

## Prerequisites

| Item | Disposition | Evidence / exact missing item |
|---|---|---|
| Process correction source and bounded native proof | Satisfied | Accepted owner result; executable commit a5d7e63d921057b529b6e2c5e2de8d12ed4ca0f8. Prior full suite 2689/52762, focused 40/470, zero skips; no new executable changes. |
| Reviewed installation candidate | Satisfied for setup preparation | package-scratch-process-a5d7e63d; manifest CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558; preserved original unrelocated package. |
| Existing accounts | Satisfied for reuse | Get-LocalUser reports enabled PmaRuntime SID S-1-5-21-4230689943-3750815333-1810161870-1007 and PmaCaller SID S-1-5-21-4230689943-3750815333-1810161870-1008. Administrators membership lists Administrator and gatom, neither role. Recheck at owner setup. |
| Real installation | Missing | Get-Item -Force reports C:/ProgramData/Imperium absent. Fresh owner installation is needed; absence is not isolation proof. |
| Protected PHP/shell and installed code | Needs deployment verification | Candidate contains reviewed binaries/configuration; actual installed manifest, ACLs and startup have not been measured at real paths. |
| Actual account tokens/access at real paths | Needs fresh measurement | Disposable tokens are historical evidence of the accounts only. Real pre/post phase bundles, native token outputs and startup responses are absent because real installation is absent. |
| Independent Operator public trust/fingerprint | Missing from supplied/local deployment evidence | No authentic public-trust.json or independently confirmed fingerprint has been supplied. Named public trust files found in local-isolation-evidence belong to the two disposable exports; neither is usable. No private profile/key search was performed. External owner custody is unknown, not asserted absent. |
| Real enrollment and post-enrollment evidence | Missing | Requires independent public trust, successful real pre phase and one owner Runtime enrollment. Do not copy a disposable journal or reset state. |
| Custody/transport facts | Needs owner confirmation for real route | Owner-mediated fixed request stdio; Caller has no Runtime shell, command selection or credentials. Disposable statements do not establish this for real deployment. |
| Exact target/draft resources | Prepared candidate only | Existing target is historical a1fc4f27634319f2a22df2e6a1b370f70cdb98bf; draft/target inventory packaged. Later fresh plan expiry and installed before-manifest are required. No new mission was prepared. |
| Current readiness, real signature and authority | Not satisfied; later boundary | New current real measurements and exact reviewed plan belong to the later sequence. Real signing and mission execution are expressly not authorized by this continuation. Batches 4–5 remain pending. |

## First owner action

Use the elevated gatom PowerShell 7.5+ terminal (7.6.5 tested), not either
Runtime/Caller proof terminal. The installer was inspected: -WhatIf validates
the package file set, all hashes, reparse absence, distinct resolvable SIDs and
absence of the destination before ShouldProcess. It returns before writes.
The agent may run that read-only preview; applying remains an owner-terminal action.

Review the proposed effect: create a fresh C:/ProgramData/Imperium with protected
code, state, scratch, PHP, shell, target, exchange and pre/post plan directories;
set administrator-owned ACLs and installation/session metadata; copy reviewed
bytes and create harmless canaries/reserved trust/readiness slots. Existing
accounts are reused. No trust enrollment, signing, mission or target inspection
occurs. The script refuses an existing destination; partial setup is preserved.

In that Administrator terminal, execute only this first setup step after reviewing
those concrete changes:

```powershell
& {
    $ErrorActionPreference='Stop'
    if($PSVersionTable.PSVersion -lt [version]'7.5'){throw 'PowerShell 7.5+ required'}
    $id=[Security.Principal.WindowsIdentity]::GetCurrent()
    if(-not ([Security.Principal.WindowsPrincipal]::new($id)).IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )){throw 'Elevated Administrator required'}
    if(Test-Path -LiteralPath 'C:\ProgramData\Imperium'){
        throw 'Existing installation: preserve and stop'
    }
    $runtime=Get-LocalUser PmaRuntime
    $caller=Get-LocalUser PmaCaller
    if(-not $runtime.Enabled -or -not $caller.Enabled){throw 'Enabled accounts required'}
    if($runtime.SID.Value -ne 'S-1-5-21-4230689943-3750815333-1810161870-1007' -or
       $caller.SID.Value -ne 'S-1-5-21-4230689943-3750815333-1810161870-1008'){
        throw 'Account identity changed; stop'
    }
    $admins=@(Get-LocalGroupMember -SID 'S-1-5-32-544'|ForEach-Object{$_.SID.Value})
    if($runtime.SID.Value -in $admins -or $caller.SID.Value -in $admins){throw 'Standard accounts required'}
    $package='E:\htdocs\imperium\var\local-isolation-evidence\package-scratch-process-a5d7e63d'
    & "$package\ProtectedMissionCode\tools\Install-LocalIsolationOwnerPackage.ps1" `
        -Package $package `
        -ManifestSha256 'CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558' `
        -RuntimeSid $runtime.SID.Value -CallerSid $caller.SID.Value
}
```

Return the public result or exact error. Expected setup marker is
OWNER_FRESH_SETUP_APPLIED_NOT_YET_ISOLATION_PROVED. Stop after this action;
do not paste enrollment or mission commands. Any partial failure requires review,
not rerunning setup. Next guidance will validate installed bytes and prepare the
real pre phase, one terminal/role at a time. The independent Operator may supply
only public trust and its independently confirmed fingerprint before enrollment;
passwords and all real private keys remain outside agent custody.

## Local read-only verification result

The packaged installer -WhatIf completed successfully against the exact SIDs and digest above: all 6,485 manifested files, exact file set and reparse check passed before the preview returned. C:/ProgramData/Imperium was not created. The 41 exported public file hashes were reverified against the preserved copy manifest. Existing source/main/measurement branches were inspected and preserved. No executable changes were made; full tests are retained at their original tested commit rather than rerun for documentation. The embedded owner command was parsed locally without execution; git diff --check passed before commit.
