# Canonical Native Effect Corridor Activation — Batch 6 live-trial package v1

`BATCH_6_DISPOSABLE_LIVE_TRIAL_PACKAGE_FROZEN_NOT_EXECUTED`

## Frozen package and stop condition

The versioned package template is
`docs/evidence/canonical-native-effect-live-trial-package-template-v1.json`.
It is intentionally non-executable: the approved operation and destination are
null, the required authorization flags are false, and the future command is not
implemented. This is the correct Batch 6 state because no approved disposable
destination/operation or exact Batch 7 authorization marker has been supplied.

No authority/capability may be issued, no credential may be resolved, and no
network/provider action may occur while any package field remains blocked.

## Frozen Batch 7 command shape — do not run

The future Batch 7 implementation must accept private values by files outside
the repository, never as command-line values or environment variables:

```powershell
php bin/console imperium:canonical-native-effect:live-trial-once `
  --authorization-file="<operator-private-directory>/authorization.json" `
  --destination-file="<operator-private-directory>/destination.json" `
  --payload-file="<operator-private-directory>/synthetic-payload.json" `
  --private-evidence-directory="<operator-private-directory>/evidence" `
  --sanitized-output="docs/evidence/canonical-native-effect-live-trial-1-sanitized.json"
```

That command does not exist in Batch 6. Batch 7 must first validate the exact
Operator message marker, approved `email.send` operation, exact disposable
destination, current clean source, single-use authority, stationary capability
and empty prior trial receipt. It must never offer a force, resume or retry
option.

## Private evidence and sanitized join

Private evidence outside Git must retain the exact native transition/receipt,
effect authority/admission, callback start, response envelope/raw result,
Lazaretto admission and final receipt; request commitments; UTC times; provider
status/identifiers; and source/runtime identity. The repository candidate is
created only through `CanonicalNativeEffectEvidenceSanitizer`, which projects
digests and classifications and rejects headers, bodies, private paths,
environment material, credentials, tokens, secrets, raw destination/payload and
email addresses.

The retained result must continue to say that local callback lineage does not
prove remote cryptographic authorship and that provider-side idempotency is
unverified. `UNKNOWN` is terminal for automatic execution; reconciliation may
inspect but may not retry.

## Batch 7 preflight and post-run commands — prepared, not executed

Before any future trial:

```powershell
git status --short
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectCorridorActivationBatch6Test.php
php vendor/bin/phpunit tests
```

After a future separately authorized trial, the Operator must inspect the
private evidence outside Git, generate the sanitized candidate, recursively
scan the candidate and tracked files for credential-adjacent material, verify
that only the sanitized allowlist is newly retainable, and run the same PHPUnit
commands. Batch 8—not the producer—must independently verify the evidence.

## Current disposition

Batch 6 performed no live command, external I/O, AgentMail call, email send,
credential access, authority issuance/consumption, Iron Gate/Lazaretto opening
or runtime-state publication. Batch 7 is blocked until the exact marker and
approved destination/operation arrive in a new Operator message.
