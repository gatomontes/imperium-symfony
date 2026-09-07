# Source-review local verification

Branch: `codex/bounded-source-review`.
Entry checkpoint: `bae87a72da11dd19157cc03df3912e995b854c5f`.
Implementation and initial tests: `fb779b6dccea984aa254cbb2d006ecd0b2cb29c5`.
Final implementation/test checkpoint: `0759d716c0336802ba09ef720cb0121d77237a4d`.
Final implementation tree: `9337b425578ce9f54893f8ef2ccbabfb27a45236`.
Platform: Windows, PHP 8.4.14; PHPUnit 13.3.0.

The available global AGENTS.md was empty; no repository or ancestor AGENTS.md was
found. The initially untracked preparation document was preserved and committed.
No unrelated user changes were present at entry. The existing installation,
accounts, trust, authority records and closed campaign evidence were not modified.

## Verification record

- Initial focused source-review tests passed 13 tests / 58 assertions, including
  production Symfony command construction and offline preparation.
- PHP lint passed all 15 changed PHP files. `git diff --check` passed.
- The first full run at fb779b6d took 7:51.497 and ran 2702 tests / 52687 assertions
  with three failures. All three identified the changed `config/services.yaml`
  hash in historical frozen-source evidence checks: CanonicalConsumerCorrectionBatch4Test,
  NativeInspectionSnapshotConsistencyBatch5TerminalAuditTest and
  NativeInspectionSnapshotConsistencyPreparationBatch0Test.
- The correction restored the original configuration bytes. Existing Symfony
  discovery already resolves the single transport implementation and its optional
  offline mock defaults to null. No historical assertion, ledger or evidence hash
  was relaxed or rewritten. Source-review plus all three affected classes passed
  28 tests / 801 assertions in 6.951 seconds after the correction.
- Final full-suite run at 0759d716 passed **2702 tests / 52825 assertions** in
  **7:52.253**, using 156 MiB. Command: `php vendor/bin/phpunit tests --no-progress`.
  No runtime or test changes followed this successful run.

The suite generates unrelated config/reference.php output while constructing
kernels. Only that test-generated change is discarded; it is not part of this
implementation. No Composer dependency installation or live provider conformance
test was performed.

## Offline command proof and packet

`php tools/demo-source-review.php var/source-review-public-packet-0759d716`
passed at the final implementation checkpoint. It exercised the real command
actions prepare, inspect, authorize, lease, execute and status through the production
workflow, with synthetic prerequisite records, a fake credential broker and a
recording provider. There was exactly one provider-double call and its delivered
payload matched the approved payload byte-for-byte. The predetermined finding is
a fixture, not a live model review or a claimed discovery in owner software.

Synthetic packet: `var/source-review-public-packet-0759d716`.
Its `sha256-manifest.json` SHA-256:
`e5b7820aff5c6efd2cfd2793d5e377c161a99802c543e5652e794496b7807e6b`.

The public implementation packet is assembled from the exact final implementation
Git blobs, the seven synthetic demonstration artifacts and this verification note.
Its per-file manifest identifies implementation commit/tree and the later
documentation commit. No real authority store, credentials, installed package or
owner source is included. Artifact locations:

- `var/source-review-implementation-0759d716.zip`
- `var/source-review-implementation-0759d716/sha256-manifest.json`

## Retained boundaries and next owner step

See source-review.md for the complete input schema, exact commands and authority
contracts. This route uses the existing development-local-cli Operator decision
basis and local store custody, not the closed package's signed inspection ceremony.
Offline tests prove neither deployed isolation nor real provider availability.

The 32,000-token preflight is a conservative versioned byte-based bound with fixed
framing reserve, not a live tokenizer measurement. The USD 1 check depends on
owner-reviewed current tariff upper bounds and is not an account-level invoice cap.
Provider alias/version behavior, hidden framing and pricing must be reviewed before
activation. A local timeout does not guarantee remote cancellation or zero billing.

Next owner step: supply the service, necessary dependency text, existing tests and
expected behavior in the documented local input layout. `pricing: null` is valid
for offline preparation. Before a live activation request, separately review the
exact proposal and outgoing payload, current tariff, legitimate operational custody
and seat bindings, and intended execution environment. No real signing, credential
handling, source transmission, installation change or live mission follows from
this local implementation.
