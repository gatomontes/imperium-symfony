> Subsequent acceptance: the owner reported independent Batch 5 review passed for the 57-entry public package, object/file reconstruction, installed-file mapping and bounded analysis; owner-run verifier passes and all custody/Runtime qualifications remain. Campaign closed within those bounds. GitHub integration is separately authorized; see [integration record](local-isolation-integration.md). Original local disposition below is preserved.

# Parent Batch 5 — verification and bounded analytical completion

Disposition: BATCH_5_LOCAL_COMPLETE_PENDING_INDEPENDENT_REVIEW.
Recorded 2026-09-06. The owner supplied independent Batch 4 acceptance, expressly
reserving authorization-chain, actual-target and installed-package verification.
Those owner-run checks now pass. The bounded analytical report is complete:
zero confirmed documentation/executable contradictions in the comparisons below,
one qualified historical environment robustness gap, and explicit unresolved
questions. This is not independent acceptance or a universal isolation claim.
No further campaign starts from this disposition.

## Source identities and evidence custody

Entry branch: codex/local-isolation-scratch-correction. Entry HEAD:
f4b077151c9cd79338e89fe783648366e060c51f; tree
b412fe2bc08d7829de51e81aa314e756693f6b4e. Working tree was clean. No subsequent
executable work was changed. A first unquoted PowerShell Git revision expression
was parsed incorrectly; the read-only command was corrected to quoted HEAD^{tree}.
This was not a mission or verifier failure.

Historical target: a1fc4f27634319f2a22df2e6a1b370f70cdb98bf, tree
21780452f5815d4342f8f5c96923b2b276d9930a, at
C:/ProgramData/Imperium/ProtectedMissionTarget.
Installed executable tested commit: a5d7e63d921057b529b6e2c5e2de8d12ed4ca0f8.
Package manifest: CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558.
Authorization: mission-authorization-04f8932e7394eb8cbfa1.
Generation: 08c0ae05e115848f78b7f7d06e0702a3a521c6435e4ac54a175a60156f37ec92.
Mission: local-isolation-96b8620453243dbb4987c48a.
Operator public fingerprint:
e68888eb2fd8d0a360761dc29621fab42022e85f24cd10f3a585d6eedc7bb06a.

Owner ran the reviewed commands under Runtime SID
S-1-5-21-4230689943-3750815333-1810161870-1007. Public output directory:
C:/Users/PmaRuntime/Batch5Verification-db53f4972d04452f94370806ba03990c.
The agent read only its three public output files through existing access,
without changing ACLs or opening chain.json, journals or private authority state.
No replay, signing, enrollment, reinstall, rebuild, target remediation or provider
operation occurred. No historical attempts, packages or records were removed.

Known VPS access routes were removed, but absence of persistence was not
independently established. Owner custody, transport and trusted OS/administrator/
Runtime/signer assumptions remain premises. No stronger custody assurance follows.

## Exact owner checks and actual results

In the PmaRuntime PowerShell 7 terminal, working directory
C:/ProgramData/Imperium/ProtectedMissionCode, variables were:

```powershell
$base='C:\ProgramData\Imperium'
$exchange="$base\ProtectedMissionExchange"
$php="$base\ProtectedMissionPHP\php.exe"
$digest='CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558'
$env:PHPRC="$base\ProtectedMissionPHP\php.ini"
$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
```

The surrounding block checked PowerShell >=7.5 and the exact Runtime SID,
created a fresh Batch5Verification-GUID directory in USERPROFILE, and ran:

```powershell
& $php .\tools\local-isolation.php verify `
    "$exchange\chain.json" "$exchange\completed.json" `
    "$exchange\public-trust.json" "$exchange\target-inventory.json" `
    "$base\ProtectedMissionTarget" `
    "$exchange\target-before.json" "$exchange\target-after.json" `
    2> "$out\receipt-error.txt" |
    Tee-Object -FilePath "$out\receipt-verification.json"
if($LASTEXITCODE -ne 0){throw 'Receipt verification failed; preserve this directory'}
& .\tools\Test-LocalIsolationInstalledPackage.ps1 `
    -Manifest "$exchange\package-manifest.json" -ManifestSha256 $digest |
    Tee-Object -FilePath "$out\installed-verification.json"
```

Receipt result: PUBLIC_RECEIPT_BYTES_AND_GENERATION_VERIFIED, expected generation,
authorization, commit/tree, 15 files and 448476 object_bytes_read. Error file is
empty and the owner block proceeded past the exit-code guard. Installed result:
INSTALLED_BYTES_MATCH_REVIEWED_PACKAGE, 6483 files, recorded UTC
2026-09-06T21:38:31.8881121+00:00, expected Runtime SID and manifest digest.
Both result objects explicitly report isolation_proved false; receipt result
also reports semantic_conclusions_certified false. These are expected limitations.

Exact output SHA-256 values:

| Public file | SHA-256 |
| --- | --- |
| receipt-verification.json | 87C20BEAD9F7F0F5853048739DB7B42488DCE97A6F1FDE39C0D97E32A9B35DA2 |
| installed-verification.json | F2716C49844E526D0173A8E1B37351B6857D04E347C5E99A2BBB68A9AFF99722 |
| receipt-error.txt (empty) | E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855 |

## What the reviewed verifier establishes

Reviewed tools/LocalIsolation.php:135–200 and tools/local-isolation.php dispatch
were compared byte-for-byte with the reviewed package before the owner command.
The checker verifies the Ed25519 signature on canonical payload bytes using
supplied public trust, fingerprint, identity and competence; dossier/review/
authorization record digests and signed preview; source references and derived
authorization ID; initial-null-predecessor activation; generation bindings across
status, lifecycle, receipt and snapshot; commission identity and receipt identity.
For each of three lifecycle records it checks capability signature, consistent
issuer, generation/commission/top-level binding, exact signed transition, matching
expiry, nondecreasing consume times within mission and trust validity, unique
nonces, consumed-nonce map and completion time. It compares scope to inventory,
reconstructs actual target objects and receipt bytes, and enforces file/finding/
object-byte budgets. Recorded consumption occurred before expiry 1788732474.

It is not a substitute for every live AuthorityOwner/ceremony check. In particular:

- Capability issuer signatures are checked against the recorded issuer, not an
  independently authenticated issuer anchor or private issued-capability registry.
- The receipt is not independently signed. Currentness, historical revocation,
  exclusive custody and actual process provenance are not reconstructed from an
  independent journal/OS witness. Raw chain and journals stay owner-held.
- Elapsed max_seconds is not independently enforced by reconstruction. Recorded
  Batch 4 inspect timing is 7.114787 seconds; that is trusted Runtime telemetry.
- The verifier checks signed plan equality and selected budgets/bindings, not a
  complete repeat of mission schema validation, all authorization flags, resource
  preparation checks or every canonical derivation-service invariant. Permission
  and prohibition disclosure is bound by the signed plan; no dynamic proof of
  absence of network or other side effects follows from a consistent transcript.
- This initial-authorization verifier is not an amendment-history verifier. It
  checks historical event times, not that the completed authority remains usable
  at verification time. Completion grants no replay authority.

These are verifier coverage limits, not observed failed checks.

## Independent byte accounting and installation mapping

A separate Python byte parser, included in public evidence, decompressed the
reviewed package's exact target copy without executing any embedded source. It
checked Git SHA-1 headers/IDs, traversed only the 15 signed paths, and compared
each body to receipt base64, byte length, SHA-256, blob ID and exported inventory.
The owner verifier separately reconstructed the actual protected target. This
distinction avoids representing agent access to a package copy as a new owner
measurement. Both before/after manifests also equal the package target manifest.

| Quantity | Independently reconstructed |
| --- | ---: |
| File-content bytes, 15 returned blobs | 239613 |
| Commit/tree content bytes, including repeated traversal reads | 208863 |
| Inflated object-content bytes charged by reader | 448476 |
| Object read calls | 40 |
| Unique objects | 23 |

The 448476 total excludes Git headers and compressed storage lengths. Shared
subtrees are charged on each read. Historical OfflineGitInspector.php:21–35,
58–60 matches this accounting; LocalIsolation.php:77–97 mirrors traversal cost.

Raw target-before.json/target-after.json SHA-256:
030E9E152EF36A424D1C8DDA4A94E3E1A1CA746C61EBECD5BB19DA13B7ADD5BD.
Canonical decoded manifest SHA-256 returned by the verifier:
210be98e3bcfcb385dee6d5cd8635d7eb66d553e3f892fa1f74d487eb8317ef0.
These differ because one hashes file formatting and the other canonical JSON;
the independent parser confirmed the latter too.

Package mapping: 5780 ProtectedMissionCode + 22 ProtectedMissionPHP + 658
ProtectedMissionShell + 23 ProtectedMissionTarget = 6483 installed files.
The 6485-entry package additionally includes mission-draft.json and
target-inventory.json outside those four roots. The existing installation verifier
checks exact file sets, reparse-point absence and each SHA-256 within those roots;
it does not hash arbitrary machine files or establish ACL isolation.

## Bounded analytical report

All citations below refer to the historical target blobs in the appendix unless
explicitly labeled later installed source. Line numbers are one-based. Static
source comparison did not execute inspected code, rerun an amendment or simulate
an attack. The large flow document's relevant active amendment section (1–50)
was compared; older campaign entries were identified as historical, not promoted
to current instructions. This is not a semantic audit of every earlier campaign
claim in that file. External referenced tests/services remain outside scope.

### A1 — inert proposals and exact predecessor activation agree

Documented claim: runbook:95, "A proposal is inert" and "Signed canonical
derivation alone atomically replaces the exact predecessor." Campaign:34–44
and flow:11–14 agree. Ceremony.php:14–38 appends its own pending challenge;
61–86 verifies the predecessor before successor publication. AuthorityOwner.php:
42–58,246–295 wraps dispatch in the common transaction. This supports the
documented local state ordering. It does not independently reprove the external
canonical services' internals or historical race-test results. Classification:
supported agreement, high confidence for shown control flow, no discrepancy.

### A2 — fresh generations cannot inherit the shown predecessor inspection

Campaign:40 says "A successor starts fresh at AUTHORIZED." Handoff:84–90 describes
generation-bound evidence. Ceremony.php:81–85 creates an empty lifecycle keyed
by authorization; Generation.php:12–23 binds authorization/dossier/target/paths/
budgets. AuthorityOwner.php:147–191 requires that binding and prior inspection
under the same authorization before completing. Status:221–237 returns that
authorization's records. Expected and observed static behavior agree. High
confidence within these files; zero runtime amendment tests were performed here.

### A3 — mechanical receipt and bounded execution are consistent with disclosures

Runbook:97–107 states "Budget bytes count accepted inflated commit/tree/blob
content" and explicitly qualifies OS deadline and hardware durability. Inspector:
14–40,42–75 enforces local loose-object/file constraints and byte checks;
InspectionProcess.php:13–32 supervises the worker and cleans temporary files;
AuthorityOwner.php:172–189 persists INSPECTING only after synchronous inspection
returns. Receipt reconstruction supports bytes and lifecycle, not semantic truth
or proof of all possible effects. Classification: supported agreement, high
confidence for accounting; actual worker internals outside the allowlist are an
unverified dependency. INSPECTING is not an incremental progress indicator.

### H1 — historical shell environment inheritance was a robustness gap

Runbook:3 specifies "PowerShell 7" and :32–35 describes startup through system
PowerShell while explicitly leaving actual-account success unproved. Historical
InstalledRuntime.php:24–29 passes null as proc_open's environment and launches
Windows PowerShell 5.1; tools/ProtectedMission.ps1:4–24 does not isolate its child
environment. Therefore inherited module discovery remains a startup dependency.
This is a supported static dependency, not a reproduced failure in this mission
and not a contradiction of the runbook's qualified deployment claim. Confidence:
high for inheritance, conditional for its effect in a particular environment.

Later reviewed InstalledRuntime.php at tested commit a5d7e63d has blob
7f4df37df7b68868d651d9152337da60a0455c92 and explicitly supplies
WindowsPowerShell::environment() at lines 17–19. Later helper blob
0a37d4ed32fbd3474e886d9f427712776a87a362:10–13 sets PSModulePath for the exact
Windows PowerShell child only. The already accepted process-correction record
docs/local-isolation-scratch-process-owner-result.md supplies prior measured
attribution; its tests were not rerun. The helper implementation behind the PHP
call is outside this 15-path comparison and was not newly audited. The installed
package check binds the later reviewed build; this historical gap is not reported
as a newly reproduced installed defect. No remediation was performed.

### Unresolved questions and non-findings

The signer, PublicTrust, canonical derivation services, worker entrypoint,
ScratchWorkspace and tests referenced by these files are outside the authorized
analytical allowlist. Their complete behavior cannot be inferred from callers.
The historical scratch placement changed in later Ceremony.php, but an access
exploit or full correction cannot be proved from that call site alone. No extra
finding is invented. Historical audit counts and race assertions remain attributed
claims, not fresh Batch 5 test results. Flow's superseded STOP blocks are explicitly
historical; they do not contradict the separately approved real mission.

No universal no-network, no-persistence, crash-durability, hostile-admin or
unbounded-availability claim is made. Outside-allowlist gaps remain unresolved.

## Closure and review package

Batch 5's defined local work is complete. Independent review is next; no new
campaign, mission or corrective implementation is selected. The public package
contains this report, exact owner verification outputs, byte reconstruction,
allowlisted historical source bytes, reviewed public verifier source and provenance.
It excludes raw chains, signed capabilities, private signing material and journals.
The upload hash manifest identifies the final documentation commit and every
ZIP entry. Raw evidence remains untracked; only sanitized documentation is committed.
No executable tests were needed or rerun for documentation and passive analysis.

## Historical blob appendix

| Authorized path | Git blob |
| --- | --- |
| docs/delegate-mission-flow.md | dd4d705ac96f5bb418cb746d83fe08ecfa286e5f |
| docs/protected-mission-operator-runbook.md | 3d6a571b66d569c23ac4df73258447d1599f5bbd |
| docs/next-campaign-mission-amendment-correction.md | 3a1f0abd8a92de9f19d8589d58b6b37d2493d462 |
| docs/handoffs/mission-amendment-correction-local-audit-complete.md | b5426aaf819ff4a031cf0b8f34958c16749f2609 |
| bin/protected-mission.php | d61036240cac702ae94283d8e7321ff527fb069b |
| src/ProtectedMission/Cli.php | df0204dc189eded2444a39f3bc7220727983399e |
| src/ProtectedMission/InstalledRuntime.php | 1361d0311fea33dea8dc0540fc772e96f04c1237 |
| src/ProtectedMission/AuthorityOwner.php | bae7da1504cb6e8e6d69cf9d57ab7aeaad03819d |
| src/ProtectedMission/Ceremony.php | 8dcee5181eda3234cd786545da4d75c32ed88f42 |
| src/ProtectedMission/Generation.php | 1d44444573f13df68eaa65a64bc551ca1ebf546e |
| src/ProtectedMission/OfflineGitInspector.php | b8ab46586406ef899a899b34d489ced3330d3076 |
| src/ProtectedMission/InspectionProcess.php | e42e07a53c6fc20aa7f6c81c1fb241cafdb94eff |
| tools/ProtectedMission.ps1 | 70c90a34f6c9da0050aa87ccb5be872f676f176d |
| tools/Install-ProtectedMission.ps1 | c940b95f5bc4a5c03074847846fff63ebd9647c8 |
| tools/Assert-ProtectedMissionInstallation.ps1 | 1b0d6d6ccde23c9704e355758af8abb061f510d5 |
