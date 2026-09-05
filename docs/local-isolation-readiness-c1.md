# C1 — finite policy and deterministic readiness

C0: c94742c366735f8f1a8f0b8b57ae3ff64e06cce8. Planning integration:
d16c3833c089d4706eafe608603dcb5b0348b12e. Original branch/package preserved.

LocalIsolationReadiness.ps1 now derives per-role handle requests from current
installed inventory, including both plan roots, their complete finite contents,
exchange/reference/output classes and parent replacement. Owner evidence is
administrator-controlled; Runtime can create exchange files but cannot delete
owner children through DELETE_CHILD. Runtime legitimately owns generated outputs
and its journal. Public plan roots are read-only for both measured identities.

Fresh phase directories retain inventory, exact plans, imported actual-account
observations, exact startup stdout/stderr/exit codes and a detached final manifest.
The readiness reference binds pre/post/current manifests, package, installation,
SIDs, setup session, PHP configuration and independently confirmed public trust.
Existing slots refuse repeated import; previous readiness bytes are archived.
Canonical path/right plans never hash themselves.

Historical inventory is reconstructed from retained live objects' creation times
and metadata; changed, removed or replaced objects refuse. This uses trusted NTFS
timestamps/collection and does not claim cryptographic log authenticity. The
current inventory must equal live inventory, so new outputs require a new current
measurement bundle. Historical observations expire after 24 hours; current
measurements/readiness after 15 minutes. No automatic refresh attests measurements.

Prepare/Accept/Step dispatch validates before authority calls and attempt writes,
then compares current enrolled public trust. Status skips readiness, queries only
persisted IDs, and does not write status snapshots. CLI startup distinguishes exact
wrong-identity refusal using owner-protected public SID binding, including filtered
administrator membership. Generic checker/CLI errors never count as that refusal.

Precommit focused command:
`php vendor/bin/phpunit --filter 'LocalIsolation|ProtectedMissionAuthority|MissionAmendment' tests`
passed 29 tests / 425 assertions / zero skips, 00:33.375. This is precommit evidence;
the final committed C2 audit will provide the definitive counts and tree. Existing
conditional race assertions can vary. New readiness fixtures currently exercise a
722-row complete synthetic set and 36 refusals/recovery checks, including a real
disposable CLI journal/status check. Synthetic SIDs/observations are explicitly
not actual-account evidence. All old test methods and expectations remain unchanged.

C2 must independently exercise native handles/startup and the full preserved
PowerShell mission/amendment routes, finish the new owner runbook, commit final
executable/test changes, run the complete suite on that commit and rebuild into a
fresh package. No real installation, accounts, trust or mission has been performed.
