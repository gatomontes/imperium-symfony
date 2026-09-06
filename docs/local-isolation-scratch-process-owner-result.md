# Fresh process-correction owner result — 2026-09-06

Disposition: both corrections passed the fresh separate-account disposable check.
Independent review accepted this bounded result, as reported by the owner on 2026-09-06. Both process corrections are closed within the disposable proof. Owner custody remains a premise; no real mission is authorized.

Repository was clean on branch `codex/local-isolation-scratch-correction` at
`b9fcbcd54e9e7e6623fd5edc12aa665f8baa16c0` before this evidence review.
Executable tested commit: `a5d7e63d921057b529b6e2c5e2de8d12ed4ca0f8`;
tree: `9162f1002b514f2961032e203819a486826ea8a8`.
Candidate: `package-scratch-process-a5d7e63d`, manifest SHA-256
`CC1544907815FE9E9F00E5F2ACB90CD4191CDF4E8F892F26D739A4EF7D139558`.
No executable bytes changed during this review; no package rebuild was needed.

## Owner run

Fresh workspace: `C:\ProgramData\PmaScratchProof-797482240f3b4a168b35e342873ccc28`.
Setup session: `1e2bfe34547f4b4a8cd379f09db6f4a2`.
Relocated manifest: `5E4992C5A161A06211D17F31C7F21BE35AEC5DCE7B5744F46AF6E243F02D82BA`.
Existing Runtime SID ends 1007; Caller SID ends 1008. Both used packaged
PowerShell 7.6.5 with normal module paths, including packaged shell modules.
Native tokens showed medium integrity and no Administrators membership.
Private key creation, enrollment and signing occurred in the owner Runtime terminal.
One enrollment and one ceremony were reported; no failed attempt was reported in this fresh sequence.

Pre, post and current phase exports contain 304,526 access observations, all with
pass=true and matching expected/result values. The separate Caller canary adds
20 passing rows. Runtime checker returned exit 0 and installation/identity verified
in all phases; Runtime CLI returned exact pre-enrollment PMA_TRUST_ABSENT, then
exit 0 after enrollment. Caller checker and CLI retained their exit-2
PMA_RUNTIME_IDENTITY_REFUSED results in all phases. No manual PSModulePath override
was used in the owner sequence. Both corrected launch paths were exercised.

Ceremony result: NATIVE_CEREMONY_AND_READINESS_PASSED and
ACTUAL_INSTALLED_CEREMONY_PASSED. All ten normal process_input rows are exit 0,
complete/success. The abandoned-prepare row is exit 2, complete/expected_refusal.
The reviewed helper enforces empty stdout and exact PMA_INSTALLATION_CHECK_FAILED
plus newline for that result; public diagnostics intentionally omit child streams.
This run delivered all bytes before refusal; it does not demonstrate incomplete
delivery in the owner run. Early-close and partial-write behavior remain covered
by the focused component tests. No broken-pipe notice is visible in supplied output.
Historical warning attribution remains unconfirmed.

Scratch cleanup and abandoned-work refusal/Status recovery are true.
Target execution and actual_deployment_isolation are false. Readiness establishes
consistency of trusted observations; custody and transport remain premises.
The .NET group list includes local group -513 beyond the native whoami list;
both original representations are preserved.

## Public evidence verification

Evidence directory (repository relative):
`var/local-isolation-evidence/owner-process-proof-public-f5d07ae906294210b5d7fc1574557183`.
The owner copied an explicit allowlist of 41 files. All 41 SHA-256s were independently
recomputed locally against copy-manifest.json. Its SHA-256 matches the owner output:
`DB77FB451F5B6CA2A964DC4420B3B7F99F6C69601C2B236FD7AA0915F3231B81`.
Also verified: 21 detached-manifest entries, three readiness manifest references,
six access-to-plan hash bindings, all 304,526 rows and 20 Caller canary rows.
The candidate manifest hash and packaged ProofProcess.php/source equality were
rechecked. These are exported public observations, not an independent custody proof.
Private keys and journals were neither collected nor opened.

Prior committed validation remains: full suite 2,689 tests / 52,762 assertions /
zero skips; focused suite 40 tests / 470 assertions / zero skips; native polluted
startup regression passed both corrected boundaries with parent unchanged.
See local-isolation-scratch-process-audit.json for original logs and identities.
This update changes documentation only; no executable tests were rerun or ceremony replayed.

## Continuation

The reviewed sequence in local-isolation-scratch-process-owner-check.md completed.
Do not repeat its setup, enrollment or ceremony against this consumed authority.
The historical 304,942-observation proof, earlier packages, manifests and failed
attempts remain separate and unchanged. Synchronous bounded CLI pipe handling is
not a generic hostile-child timeout/concurrent-drain supervisor.

Next executable step toward existing parent Batches 3–5: the accepted review permits us to
resume the existing parent prerequisite checks for authentic real owner setup,
separately held Operator trust, current real measurements, reviewed plan and real
signature. Neither disposable proof supplies those prerequisites. No deployment,
provider action or target mutation is authorized by this result.

## Accepted review and parent resumption

The owner reports independent acceptance of all 41 public hashes, 304,526 plan/native observations, 20 Caller denials, account/token/startup checks, ten complete/success operations, the exact exit-2 expected refusal, and candidate/relocation/proof/fingerprint bindings. This closes only the bounded correction. See local-isolation-parent-batch-3-prerequisites.md for the real resource inventory and first owner step. Original evidence files and the earlier derivative pending-review manifest are retained unchanged as historical records.
