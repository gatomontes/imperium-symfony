> Subsequent status: owner supplied independent acceptance of this recorded Batch 3 result before authorizing Batch 4. Batch 4 is now recorded in [local-isolation-batch-4.md](local-isolation-batch-4.md). Original pre-acceptance disposition below is preserved as history.

# Parent Batch 3 — real owner setup and enrollment measurements

Recorded 2026-09-06 on codex/local-isolation-scratch-correction, entry HEAD
886d5603af070b0098c4dde204f0c855f2fa6be5 (clean). This continues the existing
parent; it does not reopen the accepted process correction or start Batch 4.

Disposition: real installation, public enrollment and recorded pre/post checks
passed. Public export verification is complete. Owner custody, forwarding and OS
integrity remain premises; independent acceptance of this real deployment result
has not been supplied. No current readiness seal, mission signature or execution
was created by this sequence. Do not repeat installation or enrollment.

## Identities and attribution

Real base: C:/ProgramData/Imperium. Setup session:
1078eb722d41495797d370245bd7f107. Existing Runtime SID ends 1007; Caller ends
1008 (common prefix S-1-5-21-4230689943-3750815333-1810161870).
Owner used PowerShell 7.6.5. Four native token records show medium integrity and
no Administrators SID. Normal shell module paths were retained, including the
inherited scratch-shell entry; no manual module-path workaround was applied.

Candidate: package-scratch-process-a5d7e63d; executable tested commit
a5d7e63d921057b529b6e2c5e2de8d12ed4ca0f8. Package manifest SHA-256:
CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558.
Owner's installed-byte verifier reported 6,483 matched installed files; the
source package has 6,485 manifested files. These are distinct counts, not a
claim that every packaged file has the same installed location.
No executable bytes changed, package rebuild occurred or tests were rerun.
Prior full 2689/52762 and focused 40/470, zero skips, retain their tested-commit
attribution in local-isolation-scratch-process-audit.json.

## Public trust and custody

Identity: imperium-operator; competence: APPROVE_CANONICAL_MISSION_PLAN.
Public fingerprint:
e68888eb2fd8d0a360761dc29621fab42022e85f24cd10f3a585d6eedc7bb06a.
Validity: 2026-09-06 18:54:45 UTC through 2026-12-05 18:54:45 UTC, exclusive end.
Owner generated fresh Ed25519 material in an owner-operated Ubuntu VPS session
using PHP 8.4.25/Sodium. The owner reported matching OpenSSL SHA-256 of the decoded
public key. Only public JSON was reconstructed locally from the supplied image;
its decoded fingerprint matched, and the imported/exported bytes hash to
873BF4B7558DED84E933EF200DFA16F34FF457DD6764FC1287EA6E8F32EAF45E.
Administrator reported unchanged import ACL and hash. Runtime enrollment reported
exit 0, enrolled_at 1788722606, matching fingerprint, explicit-owner-bootstrap.
Post Runtime trust output independently matches that public identity and key.

The private key remains on the VPS by owner statement; it was not opened,
transferred or committed by the agent. Before generation, the owner changed the
boss password, removed the two known authorized keys (OpenClaw and Windows),
showed only their phone SSH session, and reported provider logout/no API token.
Prior sudo-capable agent access is retained as a limitation: this was verification
of known access routes, not a forensic assurance against persistence, alternate
authentication rules, synchronized credentials or other privileged access.
Private directory mode 700 and file mode 600 were prescribed by bootstrap;
no independent remote filesystem audit is claimed. No mission was signed.

## Measurements and verification

Public evidence directory:
var/local-isolation-evidence/batch-3-public-65af0fc227f64ed5bc231670620da7bc.
Copy manifest SHA-256:
1D1D28B3099D692E02ECC53455C039F55FFFE8E5DE5BBF748D4D3982FEE94CBD.
All 25 copied-file hashes reverified locally, along with four access-plan hashes,
all access/startup deployment-binding fields, native tokens and public fingerprint.

| Phase | Runtime rows | Caller rows | Startup |
|---|---:|---:|---|
| pre-b64edd040955400b8a6a8b274c8acbb9 | 50,689 | 50,691 | Runtime checker exit 0; CLI exit 2 PMA_TRUST_ABSENT. Caller checker/CLI exit 2 PMA_RUNTIME_IDENTITY_REFUSED. |
| post-0bc63b5405a94d53b00c83c94e17eb8f | 50,754 | 50,758 | Runtime checker/CLI exit 0 with enrolled trust. Caller checker/CLI retain exact identity refusal. |

Total: 202,892 rows, all pass=true and expected/result equal. Owner reported exact
phase validation for both phases. Detached manifests and previous-readiness files
remain reserved; they were copied and preserved as such, not treated as sealed
current readiness. Runtime forwarding probe returned exit 2, empty output,
PMA_OPERATION_REFUSED for the shell operation. This refusal and enrollment are
supported by owner terminal screenshots; no original machine-readable transcript
of those two commands is included in the 25-file export.

One wrong-terminal Caller capture attempt stopped at the SID guard before output
creation or probes. The corrected Caller run then succeeded. Preserve that failed
attempt; it is not an access-probe failure and was not an authority replay.
All historical disposable proofs, their failures, packages and journals remain
separate, unchanged evidence, including the 304,942 and 304,526 row results.

## Next boundary

Installation, public trust/enrollment and real pre/post measurements are satisfied
for the recorded setup. Fresh real current measurement and readiness, a fresh
exact reviewed mission plan and authentic signature remain outstanding. The
owner authorized enrollment and Batch 3 measurement only, expressly excluding
mission signing/execution. Do not create a signature, Prepare, Accept or Step.
Next is review of this Batch 3 public result and owner custody/forwarding facts.
Only a subsequent authorized Batch 4 continuation may prepare its exact plan and
current measurements. Reuse session-bound pre/post evidence where the existing
runbook permits; never re-enroll, reset journals or borrow disposable authority.
