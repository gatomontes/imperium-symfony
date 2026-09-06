# Protected scratch — process correction disposition

This is a continuation of codex/local-isolation-scratch-correction from
b7ab71765d3deeeb201a14981b82ffeadbc8689c. The working tree was clean before edits;
no unrelated work was stashed or discarded. Main and the measurement-readiness
branch remain preserved. See local-isolation-scratch-process-audit.json for tested
commit/tree, exact test results, package identity and evidence hashes.

## Startup correction

Actual paths are:

1. Collect-LocalIsolationStartup.ps1 → Invoke-PmaProcess → ProcessStartInfo →
   Windows PowerShell 5.1 → Assert-ProtectedMissionInstallation.ps1.
2. Collector or owner PHP invocation → protected-mission.php → InstalledRuntime
   → proc_open → the same Windows PowerShell checker (including Recovery).

Only each fixed checker child's environment now receives
PSModulePath=C:\Windows\System32\WindowsPowerShell\v1.0\Modules. The PHP environment
builder removes case variants of the inherited variable before setting it. The
PowerShell helper sets ProcessStartInfo.Environment for the exact checker executable.
No global environment mutation, alternate identity, permission bypass or changed
checker verdict is introduced. All metadata, code, ownership, ACL, scratch and
identity checks remain intact. Owner orchestration is PowerShell 7.5+; native
Windows PowerShell 5.1 remains the checker child. Tested PS7 version is 7.6.5.

The Windows regression deliberately places PS7 modules ahead of native modules.
An uncorrected ProcessStartInfo launch reproduces MODULE_IMPORT_FAILED at Get-Acl.
The corrected PowerShell boundary and PHP environment boundary both execute the
actual native Get-Acl successfully; the parent's contaminated environment remains
unchanged. This is native process/module regression evidence, not separate-account
installation proof for the new candidate.

## Process input correction

ProofProcess is used by the disposable ceremony harness for draft and every CLI
invocation. It writes in bounded chunks, advances on partial progress, closes stdin,
and retains child exit status on failed/zero-progress writes. The narrowly suppressed
fwrite OS notice is replaced by an explicit incomplete delivery outcome. No input
is replayed. A pipe accepting bytes is transport delivery, not semantic consumption;
the actual ceremony still checks canonical export/render, submitted signature,
derived authorization, verified payload, lifecycle and recovery invariants.

Normal operations require complete delivery, exit zero, readable stdout and empty
stderr. Any other combination refuses, including exit zero after incomplete input.
The single expected abandoned-prepare call requires exit 2, empty stdout and exact
PMA_INSTALLATION_CHECK_FAILED plus newline. It may report complete or incomplete
delivery because rejection can happen before stdin is read. Other errors cannot
be converted to expected refusal. Safe exceptions and final process_input rows
contain only fixed operation labels, exit codes, delivery states and outcomes;
payloads, signatures, capabilities, keys and raw child streams are not logged.

Local real child tests demonstrate exact binary input delivery, early refusal with
incomplete input, early exit zero refusal, retained unexpected exit status and
non-disclosure of a private sentinel. A controlled stream tests partial progress
and zero-progress handling. Every real child test verifies exactly one invocation.
No pre-existing LI01/LI02, AM01/AM02 or receipt test was removed or weakened.

The historical 5764-byte broken-pipe notice remains unconfirmed as to invocation.
The expected abandoned prepare is plausible; local reproduction establishes the
mechanism, not retrospective attribution. The historical native pass, 304,942
observations, cleanup/refusal/Status evidence and all failed/expired attempts remain
unaltered. A new owner run will produce attributable per-operation delivery rows.

Limits: this helper is for the trusted bounded disposable CLI, not a general
hostile-child supervisor. It uses synchronous pipe I/O and introduces no general
deadline or concurrent output draining. A hung/malfunctioning child still requires
owner preservation and diagnosis, not retry. No private historical journal or
signing material was read by the agent. New candidate native equivalence remains
pending fresh owner execution and independent review.

## Next executable owner step

Follow local-isolation-scratch-process-owner-check.md: Administrator PowerShell
7.5+ verifies the separately identified package digest and existing enabled distinct
standard PmaRuntime/PmaCaller accounts, then creates a new disposable relocation.
Proceed one role at a time with normal PS7 module paths; no manual workaround.
Never overwrite the historical proof or reuse its trust, keys or consumed authority.
The exact owner sequence and public result requirements are in that document.

After the fresh check and independent acceptance, resume the existing parent
Batches 3–5 only when their real owner prerequisites exist. Disposable proof
does not authorize real installation, signing, provider invocation or target work.
