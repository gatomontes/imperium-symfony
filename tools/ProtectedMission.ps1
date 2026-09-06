# PowerShell 7 helper. Private key bytes are never placed in process arguments.
function Invoke-PmaProcess {
    param([Parameter(Mandatory)][string]$Script, [string[]]$Arguments = @(), [string]$InputText = '',
          [string]$Php = (Get-Command php -ErrorAction Stop).Source,
          [ValidateRange(1000,180000)][int]$TimeoutMilliseconds = 120000)
    if($PSVersionTable.PSVersion -lt [version]'7.5'){throw 'PMA_POWERSHELL_75_REQUIRED'}
    $start = [Diagnostics.ProcessStartInfo]::new()
    $start.FileName = $Php
    $start.UseShellExecute = $false
    # Apply only to this exact Windows PowerShell child, not the parent or PHP/PS7.
    if([IO.Path]::GetFullPath($Php) -ieq 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe'){
        $start.Environment['PSModulePath']='C:\Windows\System32\WindowsPowerShell\v1.0\Modules'
    }
    $start.CreateNoWindow = $true
    $start.RedirectStandardInput = $true
    $start.RedirectStandardOutput = $true
    $start.RedirectStandardError = $true
    $start.StandardInputEncoding = [Text.UTF8Encoding]::new($false)
    $start.StandardOutputEncoding = [Text.UTF8Encoding]::new($false)
    $start.StandardErrorEncoding = [Text.UTF8Encoding]::new($false)
    $start.ArgumentList.Add($Script)
    foreach ($item in $Arguments) { $start.ArgumentList.Add($item) }
    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $start
    try {
        [void]$process.Start()
        $output = $process.StandardOutput.ReadToEndAsync()
        $errors = $process.StandardError.ReadToEndAsync()
        $process.StandardInput.Write($InputText)
        $process.StandardInput.Close()
        if (-not $process.WaitForExit($TimeoutMilliseconds)) { $process.Kill($true); throw 'PMA_PROCESS_TIMEOUT_QUERY_PERSISTED_STATUS_BEFORE_ANY_RETRY' }
        $result = [pscustomobject]@{ ExitCode = $process.ExitCode; Output = $output.GetAwaiter().GetResult(); Error = $errors.GetAwaiter().GetResult() }
        return $result
    } finally { $process.Dispose() }
}

function Write-PmaCanonicalPayload {
    param([Parameter(Mandatory)]$Result, [Parameter(Mandatory)][string]$Path)
    if ($Result.ExitCode -ne 0) { throw $Result.Error }
    [IO.File]::WriteAllText($Path, $Result.Output, [Text.UTF8Encoding]::new($false))
}

function Sign-PmaApproval {
    param([Parameter(Mandatory)][string]$Payload, [Parameter(Mandatory)][string]$Response,
          [Parameter(Mandatory)][Security.SecureString]$HeldKey)
    $memory = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($HeldKey)
    try {
        $keyInput = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($memory)
        $result = Invoke-PmaProcess -Script (Join-Path $PSScriptRoot 'sign-protected-mission.php') -Arguments @($Payload, $Response) -InputText ($keyInput + "`n")
        if ($result.ExitCode -ne 0) { throw $result.Error }
        return $result.Output
    } finally {
        $keyInput = $null
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($memory)
    }
}
