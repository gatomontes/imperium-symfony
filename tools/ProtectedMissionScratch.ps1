# Shared production policy; relocation belongs to the disposable owner harness only.
function New-PmaScratchAcl([string]$RuntimeSid) {
 $acl=[Security.AccessControl.DirectorySecurity]::new()
 $acl.SetAccessRuleProtection($true,$false)
 foreach($kind in @('Owner','Group')){
  if($kind -eq 'Owner'){$acl.SetOwner([Security.Principal.SecurityIdentifier]'S-1-5-32-544')}
  else{$acl.SetGroup([Security.Principal.SecurityIdentifier]'S-1-5-32-544')}
 }
 foreach($sid in @('S-1-5-32-544','S-1-5-18')) {
  $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]::FullControl,[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit',[Security.AccessControl.PropagationFlags]::None,[Security.AccessControl.AccessControlType]::Allow))
 }
 $runtime=[Security.Principal.SecurityIdentifier]$RuntimeSid
 $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new($runtime,[Security.AccessControl.FileSystemRights]'ReadAndExecute,CreateFiles,CreateDirectories',[Security.AccessControl.AccessControlType]::Allow))
 $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new($runtime,[Security.AccessControl.FileSystemRights]::Modify,[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit',[Security.AccessControl.PropagationFlags]::InheritOnly,[Security.AccessControl.AccessControlType]::Allow))
 return $acl
}
function Assert-PmaScratchPolicy([string]$Path,[string]$RuntimeSid) {
 $item=Get-Item -LiteralPath $Path -Force -ErrorAction Stop
 if(-not $item.PSIsContainer){throw 'PMA_SCRATCH_ROOT_REQUIRED'}
 for($cursor=$item;$null -ne $cursor;$cursor=$cursor.Parent){
  if($cursor.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'PMA_SCRATCH_REPARSE'}
 }
 $acl=Get-Acl -LiteralPath $Path
 $expected=New-PmaScratchAcl $RuntimeSid
 if(-not $acl.AreAccessRulesProtected -or $acl.GetOwner([Security.Principal.SecurityIdentifier]).Value -cne 'S-1-5-32-544' -or $acl.GetGroup([Security.Principal.SecurityIdentifier]).Value -cne 'S-1-5-32-544'){throw 'PMA_SCRATCH_POLICY_REFUSED'}
 # Ignore descriptor auto-inherit bookkeeping; compare every effective ACE.
 $sets=@($acl,$expected)|ForEach-Object{
  (@($_.GetAccessRules($true,$true,[Security.Principal.SecurityIdentifier])|ForEach-Object{
   $_.IdentityReference.Value+'|'+[int]$_.FileSystemRights+'|'+[int]$_.InheritanceFlags+'|'+[int]$_.PropagationFlags+'|'+$_.AccessControlType+'|'+$_.IsInherited
  }|Sort-Object) -join "`n")
 }
 if($sets[0] -cne $sets[1]){throw 'PMA_SCRATCH_POLICY_REFUSED'}
}
function Assert-PmaScratchEmpty([string]$Path) {
 if(@(Get-ChildItem -LiteralPath $Path -Force -ErrorAction Stop).Count){throw 'PMA_SCRATCH_ABANDONED_OR_BUSY'}
}
