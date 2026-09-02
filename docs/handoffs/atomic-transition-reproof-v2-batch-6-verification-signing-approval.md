# Atomic Transition Reproof v2: Batch 6 approval boundary

`REPROOF_BATCH_5_COMPLETE_AWAITING_SEPARATE_VERIFICATION_SIGNING_APPROVAL`

Batch 5 executed once and produced an unverified candidate. Read its completion
handoff, operator-approval record and the immutable public candidate. This
request covers only the new event; it grants no access to the historical v1
receipt and no mission retry. Batch 6 has not executed.

## Exact intake and verifier

- Event: `reproof-v2-20260902-proof-2`.
- Source: `2b5cb56c8ae60d80b628311377f929830401ca3e`.
- Source manifest: `7867dede38bca0f4aace144868338c22d486d1e05467877407b9fa95bc9674d7`.
- Candidate: `cc86f24082e3d254e6802e8d81f675e334f4577790f92997d088b4c0c64fb3ab`.
- Private receipt: `36f84646ed977eaaa7bf45803ce4ae326f174f2d43405d7dbfba7b02f339cbfb`.
- Finalization manifest: `048fe867714173e98dd5490f4e0cedbb2a299fc888eb543f16fa045fb718399e`.
- Independent verifier: six explicit source files from the detached source
  checkout, implementation root `ea2925e14c23c2bfe9346375597f446c7c28b3c1ff4ae9d492a999f1340d883d`.

The new controller is `tools/verify-and-sign-atomic-transition-reproof-v2.php`.
Reviewed local controller SHA-256:
`dd15523c8515ae8ec3842dd7b470205310d98310ee9b893d801992b9a67a02b4`.
The public request is
`docs/atomic-transition-reproof-v2-verification-signing-request.json`, canonical
SHA-256 `11731fa32c45d2731f1a961d4be5d492d3b34b6573fd072dbb444dea80393f9b`.
Both the request and this handoff remain descriptions, not authorization.

## Proposed separate custody and sequence

Approve one new local Ed25519 identity, valid for 24 hours, solely for
`imperium.atomic-transition-reproof.independent-report/v2`. Provision it in the
new separate signing directory under operator/SYSTEM-only Windows ACLs. Do not
search for or open any existing key. The key stays outside the receipt and Git.
The identity is established from this independently approved provisioning step
before private receipt intake, never accepted from the producer package.

The controller pins the PHP executable, Sodium extension/version and verifier
sources. It records the new public identity and external trust pins before
reading only the exact new package. It verifies publication checksums, record
commitments and all eight independent semantic domains. It signs only a PASS
report whose identity/source/receipt/candidate bindings match those pins.
Synthetic, refused, incomplete or indeterminate reports cannot be signed.
The signature covers purpose, NUL and the sanitized report digest. Its own
detached verification must succeed before finalization.

The fresh custody directory permits only one attempt. On any failure, preserve
it and do not retry automatically. Private key, receipt and trust files remain
local. Only strict public identity/report/attestation records may later be
projected for review. They are pending Batch 7 admission, not accepted closure.
Native PHP/Windows infrastructure and the reviewed local source remain trusted;
this does not claim a hostile-host sandbox or hardware-backed key custody.

## Exact proposed commands

Run only after the operator separately approves this request. The ACL commands
apply only to the newly created signing directory; no existing key is selected.

```powershell
$ErrorActionPreference = 'Stop'
$signingDirectory = 'E:/ai/imperium-reproof-v2-signing/reproof-v2-20260902-proof-2'
if (Test-Path -LiteralPath $signingDirectory) { throw 'REPROOF_SIGNING_RESERVATION_EXISTS' }
$signingParent = 'E:/ai/imperium-reproof-v2-signing'
if (-not (Test-Path -LiteralPath $signingParent)) { New-Item -ItemType Directory -Path $signingParent | Out-Null }
New-Item -ItemType Directory -Path $signingDirectory | Out-Null
$operatorSid = [System.Security.Principal.WindowsIdentity]::GetCurrent().User.Value
icacls $signingDirectory /inheritance:r /grant:r "*$($operatorSid):(OI)(CI)F" '*S-1-5-18:(OI)(CI)F' | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'REPROOF_SIGNING_ACL_FAILED' }
C:/php/php-8.4/php.exe -n -d extension_dir=C:/php/php-8.4/ext -d extension=sodium E:/htdocs/imperium/tools/verify-and-sign-atomic-transition-reproof-v2.php --approved-request 11731fa32c45d2731f1a961d4be5d492d3b34b6573fd072dbb444dea80393f9b
```

The Sodium extension requires the explicit extension directory under `php -n`;
module availability was checked without creating keys or signing. Controller
validation so far is syntax and documentary boundary testing only. Cryptographic
operation and actual independent verification remain unperformed until approval.

`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`
remains controlling. V1 is unchanged; `BOUND_INACTIVE`, `NOT_IMPLEMENTED` and
`UNKNOWN_REPLAY_PROHIBITED` remain binding. Three stages remain and the campaign
is open. Batch 8 still requires a separate audit from merged Batch 7 main.
