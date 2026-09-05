param([Parameter(Mandatory)][string]$Package,[Parameter(Mandatory)][string]$ManifestSha256,
      [Parameter(Mandatory)][string]$OutputDirectory,[Parameter(Mandatory)][string]$TestedCommit,
      [string]$Source=(Split-Path $PSScriptRoot))
$ErrorActionPreference='Stop'
if(Test-Path -LiteralPath $OutputDirectory){throw 'FRESH_REVIEW_DIRECTORY_REQUIRED'}
if([IO.Path]::GetFullPath($OutputDirectory).StartsWith([IO.Path]::GetFullPath($Package),[StringComparison]::OrdinalIgnoreCase)){throw 'REVIEW_MUST_NOT_MODIFY_PACKAGE'}
$manifestPath=Join-Path $Package 'package-manifest.json'
if((Get-FileHash $manifestPath).Hash -cne $ManifestSha256){throw 'PACKAGE_DIGEST_CHANGED'}
$manifest=Get-Content $manifestPath -Raw|ConvertFrom-Json -AsHashtable
$actual=(& php "$Source\tools\local-isolation.php" manifest $Package) -join "`n"
if($LASTEXITCODE -ne 0){throw 'PACKAGE_ENUMERATION_FAILED'}
$actual=$actual|ConvertFrom-Json -AsHashtable;$actual.Remove('package-manifest.json')
. "$Source\tools\LocalIsolationReadiness.ps1"
Assert-PmaEqual $actual $manifest 'PACKAGE_FILES_CHANGED'
$head=(& git -C $Source rev-parse HEAD).Trim();$tree=(& git -C $Source rev-parse 'HEAD^{tree}').Trim()
$testedTree=(& git -C $Source rev-parse ($TestedCommit+'^{tree}')).Trim()
$sources=[Collections.Generic.List[object]]::new()
function Add-ReviewSource([string]$Path,[string]$Category){
 $bytes=[IO.File]::ReadAllBytes($Path);$content=[Text.UTF8Encoding]::new($false,$true).GetString($bytes)
 $sources.Add([ordered]@{path=$Path;category=$Category;sha256=(Get-FileHash $Path).Hash;bytes=$bytes.Length;content=$content})
}
foreach($name in @('local-isolation-scratch-owner-runbook.md','local-isolation-scratch-owner-proof.md','local-isolation-scratch-s0.md','local-isolation-scratch-s1.md','local-isolation-scratch-s2.md','local-isolation-scratch-audit.json','local-isolation-scratch-terminal-audit.md','next-campaign-local-isolation-scratch-correction.md','local-isolation-scratch-permission-review.md','local-isolation-readiness-owner-runbook.md','local-isolation-readiness-c0.md','local-isolation-readiness-c1.md','local-isolation-readiness-c2.md','local-isolation-readiness-audit.json','local-isolation-readiness-terminal-audit.md','local-isolation-package-independent-review.md','next-campaign-local-isolation-measurement-readiness.md','next-campaign-local-isolation-useful-mission.md','local-isolation-terminal-audit.md','local-isolation-target-manifest.json','local-isolation-report-template.md')){Add-ReviewSource "$Source\docs\$name" 'Public instructions/evidence; historical records retain their attribution'}
foreach($name in @('ProtectedMissionScratch.ps1','New-ProtectedScratchOwnerProof.ps1','Test-ProtectedScratchOwnerProof.ps1','Test-ProtectedScratchCeremony.php','Build-LocalIsolationScratchReview.ps1','LocalIsolationReadiness.ps1','New-LocalIsolationProbePlans.ps1','New-LocalIsolationMeasurement.ps1','Test-LocalIsolationAccess.ps1','Collect-LocalIsolationStartup.ps1','Import-LocalIsolationMeasurement.ps1','Test-LocalIsolationPhase.ps1','Seal-LocalIsolationReadiness.ps1','Test-LocalIsolationReadiness.ps1','Install-LocalIsolationOwnerPackage.ps1','Install-ProtectedMission.ps1','Assert-ProtectedMissionInstallation.ps1','Invoke-LocalMission.ps1','LocalMission.ps1','ProtectedMission.ps1','LocalIsolation.php','local-isolation.php','sign-protected-mission.php','Build-LocalIsolationPackage.ps1','Build-LocalIsolationReadinessReview.ps1','Test-LocalIsolationInstalledPackage.ps1')){Add-ReviewSource "$Package\ProtectedMissionCode\tools\$name" 'Exact packaged executable'}
foreach($file in Get-ChildItem "$Package\ProtectedMissionCode\src\ProtectedMission" -File|Sort-Object Name){Add-ReviewSource $file.FullName 'Exact packaged Runtime source'}
foreach($name in @('protected-mission.php','protected-git-worker.php')){Add-ReviewSource "$Package\ProtectedMissionCode\bin\$name" 'Exact packaged CLI'}
foreach($file in (& rg --files "$Source\tests\Imperium\Runtime"|Where-Object{$_ -match '(LocalIsolation|ProtectedMission|MissionAmendment|local_isolation|protected_mission|mission_amendment)'}|Sort-Object)){Add-ReviewSource $file 'Preserved/additive test source; no generated test authority'}
foreach($name in @('package-manifest.json','target-inventory.json','mission-draft.json')){Add-ReviewSource "$Package\$name" 'Exact fresh package manifest and inert target/draft'}
$builder=[Text.StringBuilder]::new()
[void]$builder.AppendLine('# Protected Scratch Workspace — independent review packet')
[void]$builder.AppendLine("`nSCRATCH_WORKSPACE_CORRECTION_NATIVE_PROOF_PENDING is a local preparation status, not independent acceptance or deployed isolation. Parent Batches 3–5 remain conditional on authentic owner setup/evidence and exact signature. No real mission has run.")
[void]$builder.AppendLine("`nTested commit: $TestedCommit; tree: $testedTree. Compilation source: $head; tree: $tree. Fresh package: $Package. Manifest SHA-256: $ManifestSha256.")
[void]$builder.AppendLine("`nRead the scratch S0/S1/S2 notes, owner proof/runbook and terminal audit first, then executable/test sources and complete manifest. The original package-47bcc44a and its 8DAB48F98D147A6ABA9E89260D25082BA5B91AD2C72638F1F10FFB9A449CF6AE manifest remain preserved historical evidence; do not install it. Historical instructions/counts are not current authority. Both historical packages, manifests, review assemblers and packets remain unchanged. Equivalent native ceremony is NOT RUN; the owner harness is an executable pending proof, not successful evidence.")
[void]$builder.AppendLine("`nThis file compiles exact source text. Source hashes bind original local bytes, including line endings; copying rendered Markdown does not guarantee byte preservation. Execute reviewed originals. The derivative JSON hashes this Markdown and every source, and is not installation or mission authority. Binaries and transitive vendor implementations are inventoried by the complete package manifest and available locally, not embedded or independently security-audited here. Source test key-generation code is not generated authority; raw journals, signatures, capabilities and private transcripts are excluded.")
[void]$builder.AppendLine("`n## Source inventory`n`n| Source | Bytes | SHA-256 |`n|---|---:|---|")
foreach($s in $sources){[void]$builder.AppendLine('| '+$s.path+' | '+$s.bytes+' | '+$s.sha256+' |')}
foreach($s in $sources){
 $fence=([string][char]96)*12;if($s.content.Contains($fence)){throw 'SOURCE_FENCE_COLLISION'}
 [void]$builder.AppendLine("`n## Source: "+$s.path+"`n`n"+$s.category+". SHA-256: "+$s.sha256+".`n`n"+$fence)
 [void]$builder.Append($s.content);if(-not $s.content.EndsWith("`n")){[void]$builder.AppendLine()};[void]$builder.AppendLine($fence)
 if((Get-FileHash $s.path).Hash -cne $s.sha256){throw 'SOURCE_CHANGED_DURING_COMPILATION'}
}
[void][IO.Directory]::CreateDirectory($OutputDirectory)
$packet=Join-Path $OutputDirectory 'local-isolation-scratch-independent-review.md'
[IO.File]::WriteAllText($packet,$builder.ToString(),[Text.UTF8Encoding]::new($false))
$derivative=[ordered]@{schema='imperium.independent-review-compilation/v2';packet=@{path=$packet;sha256=(Get-FileHash $packet).Hash;bytes=(Get-Item $packet).Length};package=$Package;package_manifest_sha256=$ManifestSha256;manifest_entries=$manifest.Count;tested_commit=$TestedCommit;tested_tree=$testedTree;compilation_commit=$head;compilation_tree=$tree;sources=@($sources|ForEach-Object{@{path=$_.path;category=$_.category;sha256=$_.sha256;bytes=$_.bytes}});actual_deployment_isolation=$false;independent_acceptance=$false}
$path=Join-Path $OutputDirectory 'review-packet-manifest.json'
[IO.File]::WriteAllText($path,($derivative|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
@{packet=$packet;derivative_manifest=$path;packet_sha256=$derivative.packet.sha256;sources=$sources.Count}|ConvertTo-Json
