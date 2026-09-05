# Owner-mediated orchestration. All output stays in the owner-controlled exchange.
if($PSVersionTable.PSVersion -lt [version]'7.5'){throw 'POWERSHELL_7_5_REQUIRED_FOR_EXACT_JSON_DATE_STRINGS'}
. (Join-Path $PSScriptRoot 'ProtectedMission.ps1')
function Invoke-PmaLocalAction($Context,[ValidateSet('Prepare','Accept','Step','Status')][string]$Action) {
    # Status bypasses readiness and never calls the snapshot-writing progress helper.
    if($Action -eq 'Status') {
        $path=Join-Path $Context.Exchange 'authorization.json'
        if(Test-Path -LiteralPath $path){return Invoke-PmaChecked $Context @('status',(Get-Content $path -Raw|ConvertFrom-Json).authorization_id)}
        $path=Join-Path $Context.Exchange 'challenge.json'
        if(Test-Path -LiteralPath $path){return Invoke-PmaChecked $Context @('challenge-status',(Get-Content $path -Raw|ConvertFrom-Json).challenge_id)}
        return @{result='NO_PERSISTED_ID_PRESERVE_ATTEMPT_MARKERS'}
    }
    . (Join-Path $PSScriptRoot 'LocalIsolationReadiness.ps1')
    try {
        $ready=Read-PmaEvidence (Join-Path $Context.Exchange 'owner-readiness.json')
        $validation=Test-PmaReadiness $ready
    } catch {$validation=@{result='LOCAL_ISOLATION_READINESS_REFUSED';reason=$_.Exception.Message}}
    if($validation.result -cne 'LOCAL_ISOLATION_READINESS_VALID'){
        Write-Output ($validation|ConvertTo-Json -Depth 20 -Compress)
        throw 'ACTUAL_OWNER_READINESS_REQUIRED'
    }
    $token=Get-PmaToken
    Assert-PmaToken $token $validation.binding.runtime_sid
    Assert-PmaEqual $token.groups $validation.groups.Runtime 'READINESS_LIVE_RUNTIME_GROUPS_CHANGED'
    $liveTrust=Invoke-PmaChecked $Context @('trust')
    Assert-PmaEqual $liveTrust $validation.public_trust 'READINESS_LIVE_TRUST_CHANGED'
    switch($Action) {
        'Prepare' {Start-PmaPrepared $Context (Join-Path $Context.Exchange 'plan.json')}
        'Accept' {Accept-PmaSigned $Context (Join-Path $Context.Exchange 'response.json')}
        'Step' {Step-PmaMission $Context}
    }
}
function Write-PmaNewJson($Value,[string]$Path) {
    $stream=[IO.File]::Open($Path,[IO.FileMode]::CreateNew,[IO.FileAccess]::Write)
    try{$bytes=[Text.UTF8Encoding]::new($false).GetBytes(($Value|ConvertTo-Json -Depth 100));$stream.Write($bytes,0,$bytes.Length);$stream.Flush($true)}finally{$stream.Dispose()}
}
function Invoke-PmaChecked($Context,[string[]]$Command,[string]$InputJson='') {
    $r=Invoke-PmaProcess -Php $Context.Php -Script $Context.Script -Arguments (@($Context.Prefix)+$Command) -InputText $InputJson
    if($r.ExitCode -ne 0){throw $r.Error}
    return ($r.Output|ConvertFrom-Json -AsHashtable -DateKind String)
}
function Invoke-PmaRequestChecked($Context,[string]$Operation,$Arguments) {
    Invoke-PmaChecked $Context @('request') ([ordered]@{operation=$Operation;arguments=$Arguments}|ConvertTo-Json -Depth 100 -Compress)
}
function Get-PmaProgress($Context,[string]$Authorization) {
    $status=Invoke-PmaChecked $Context @('status',$Authorization)
    $file=Join-Path $Context.Exchange ('status-'+[guid]::NewGuid().ToString('N')+'.json')
    Write-PmaNewJson $status $file
    Write-Host ($Authorization+': '+$status.lifecycle.state+'; '+$status.currentness)
    return $status
}
function Start-PmaPrepared($Context,[string]$Plan) {
    $marker=Join-Path $Context.Exchange 'prepare-attempt.json'
    Write-PmaNewJson @{plan_sha256=(Get-FileHash -LiteralPath $Plan).Hash;utc=[DateTimeOffset]::UtcNow.ToString('o')} $marker
    $prepared=Invoke-PmaChecked $Context @('prepare') (Get-Content -LiteralPath $Plan -Raw)
    Write-PmaNewJson $prepared (Join-Path $Context.Exchange 'challenge.json')
    $export=Invoke-PmaProcess -Php $Context.Php -Script $Context.Script -Arguments (@($Context.Prefix)+@('export',$prepared.challenge_id))
    Write-PmaCanonicalPayload $export (Join-Path $Context.Exchange 'payload.json')
    $render=Invoke-PmaProcess -Php $Context.Php -Script $Context.Script -Arguments (@($Context.Prefix)+@('render',$prepared.challenge_id))
    if($render.ExitCode -ne 0){throw $render.Error}
    [IO.File]::WriteAllText((Join-Path $Context.Exchange 'render.txt'),$render.Output,[Text.UTF8Encoding]::new($false))
    Write-Host $render.Output
    return $prepared
}
function Accept-PmaSigned($Context,[string]$Response) {
    $cid=(Get-Content (Join-Path $Context.Exchange 'challenge.json') -Raw|ConvertFrom-Json).challenge_id
    $status=Invoke-PmaChecked $Context @('challenge-status',$cid)
    if($status.status -eq 'PENDING_NON_AUTHORIZING'){
        Write-PmaNewJson @{challenge_id=$cid;response_sha256=(Get-FileHash $Response).Hash} (Join-Path $Context.Exchange 'submit-attempt.json')
        $null=Invoke-PmaChecked $Context @('submit') (Get-Content -LiteralPath $Response -Raw)
        $status=Invoke-PmaChecked $Context @('challenge-status',$cid)
    }
    if($status.status -eq 'APPROVED_PENDING_DERIVATION'){
        Write-PmaNewJson @{challenge_id=$cid} (Join-Path $Context.Exchange 'derive-attempt.json')
        $null=Invoke-PmaChecked $Context @('derive',$cid)
        $status=Invoke-PmaChecked $Context @('challenge-status',$cid)
    }
    if($status.status -ne 'DERIVED' -or -not $status.authorization_id){throw 'CHALLENGE_NOT_DERIVED_QUERY_STATUS'}
    $path=Join-Path $Context.Exchange 'authorization.json'
    if(-not(Test-Path $path)){Write-PmaNewJson @{authorization_id=$status.authorization_id} $path}
    else{if((Get-Content $path -Raw|ConvertFrom-Json).authorization_id -cne $status.authorization_id){throw 'AUTHORIZATION_CHANGED'}}
    $chain=Invoke-PmaChecked $Context @('verify',$status.authorization_id)
    if(-not(Test-Path (Join-Path $Context.Exchange 'chain.json'))){Write-PmaNewJson $chain (Join-Path $Context.Exchange 'chain.json')}
    Get-PmaProgress $Context $status.authorization_id
}
function Step-PmaMission($Context) {
    $aid=(Get-Content (Join-Path $Context.Exchange 'authorization.json') -Raw|ConvertFrom-Json).authorization_id
    $status=Get-PmaProgress $Context $aid
    if($status.lifecycle.state -eq 'COMPLETED'){return $status}
    if($status.currentness -cne 'CURRENT'){throw 'AUTHORITY_NOT_CURRENT'}
    $capsPath=Join-Path $Context.Exchange 'capabilities.json'
    if(-not(Test-Path $capsPath)){
        Write-PmaNewJson @{authorization_id=$aid} (Join-Path $Context.Exchange 'issue-attempt.json')
        $issued=Invoke-PmaRequestChecked $Context 'issue' ([ordered]@{authorization_id=$aid})
        Write-PmaNewJson $issued $capsPath
    }
    $issued=Get-Content $capsPath -Raw|ConvertFrom-Json -AsHashtable -DateKind String
    $cap=@($issued.capabilities|Where-Object{$_.payload.from -ceq $status.lifecycle.state})
    if($cap.Count -ne 1){throw 'NO_UNIQUE_NEXT_TRANSITION'}
    $action=$cap[0].payload.action
    # CreateNew refuses any earlier uncertain attempt. Recovery is status-only.
    Write-PmaNewJson @{authorization_id=$aid;action=$action;nonce=$cap[0].payload.nonce;utc=[DateTimeOffset]::UtcNow.ToString('o')} (Join-Path $Context.Exchange ($action+'-attempt.json'))
    Write-Host ('Attempting '+$action+'; inspect may take up to 60 seconds before persisted INSPECTING.')
    $timer=[Diagnostics.Stopwatch]::StartNew()
    try{$null=Invoke-PmaRequestChecked $Context 'consume' ([ordered]@{capability=$cap[0]})}
    catch{
        $incident=$_.Exception.Message
        Write-PmaNewJson @{action=$action;error=$incident;disposition='STOP_NO_BLIND_RETRY'} (Join-Path $Context.Exchange ('incident-'+[guid]::NewGuid().ToString('N')+'.json'))
        $null=Get-PmaProgress $Context $aid
        throw $incident
    }
    $timer.Stop()
    Write-PmaNewJson @{action=$action;elapsed_seconds=$timer.Elapsed.TotalSeconds;utc=[DateTimeOffset]::UtcNow.ToString('o')} (Join-Path $Context.Exchange ($action+'-completed-timing.json'))
    Get-PmaProgress $Context $aid
}
