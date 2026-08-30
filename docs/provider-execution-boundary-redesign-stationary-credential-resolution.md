# Provider Execution Boundary Redesign — Batch 7 stationary credential resolution

## Result

`BATCH_7_STATIONARY_CREDENTIAL_RESOLUTION_PROVED_NO_IO`

Batch 7 defines `StationaryCredentialResolutionProofContract` and implements
`GovernedStationaryCredentialResolutionService`.

The service accepts only one exact governed admission winner. It resolves the deployment-owned
credential inside the same process, exposes authentication only to a fixed callback-local
non-provider check, destroys the local variable and persists only a secret-free proof.

## Exact preconditions

A first proof validates:

- the intact `ADMITTED_EFFECT_START_PRE_RESOLUTION_PRE_IO` admission;
- the admission's exact authority-consumption winner;
- committed local effect-start;
- explicit permission to resolve only after that checkpoint;
- `credential_resolved: false`, `external_io_started: false` and
  `provider_invoked: false` on the admission;
- the exact intact durable execution authority;
- the exact current inert executor-principal attestation;
- the exact inactive provider binding;
- the exact same-process stationary execution boundary;
- provider and credential-family equality across authority, principal and binding;
- current, unrevoked validity; and
- one admitted provider/credential-family environment mapping.

No public callback accepts the credential. The only callback is fixed inside the governed service and
returns a boolean resolution result unrelated to secret content.

## Secret-free proof

The proof records:

- provider ID and credential family only;
- stationary possession and same-process resolution;
- `STATIONARY_CREDENTIAL_RESOLVED_CALLBACK_LOCAL_NO_IO`;
- `credential_resolved: true`;
- `callback_local: true`;
- `secret_exposed_to_caller: false`;
- `credential_reference_persisted: false`;
- `credential_secret_persisted: false`;
- `credential_capability_issued: false`;
- `credential_capability_reconstructed: false`;
- `provider_invoked: false`;
- `external_io_started: false`;
- `outbound_byte_sent: false`; and
- `provider_outcome_claimed: false`.

Tests scan every durable JSON record recursively and reject both the test secret and its environment
variable name.

## Replay, interruption and absence

The proof is serialized under the exact admission ID. Exact replay returns the same proof without
rereading the environment credential, including after expiry. This is read-only reconstruction of a
completed local proof, not renewed credential authority.

If no proof exists, an absent credential, unsupported provider/family, expired admission, expired
lineage or revoked authority/principal refuses before any proof is written. Because no provider or
external I/O occurs, retrying local resolution before proof creation cannot duplicate an external
effect.

## Capability category correction

No `CredentialCapability` is issued, transferred, serialized, reconstructed or consumed. Durable
execution authority remains the permission identity; the admission is its consumption winner; the
credential remains stationary deployment material. This closes the category error that forced the
earlier cross-process custody refusal.

## Closed effects and threat model

The implementation imports no credential-capability abstraction, general credential broker,
provider transport, AgentMail transport, Iron Gate or Lazaretto. It performs no provider invocation,
outbound I/O or outcome admission.

The guarantee remains one-root `TRUSTED_WRITER_CANONICAL_INTEGRITY`. Environment isolation and
trusted application code remain deployment assumptions; hostile process compromise is not solved or
claimed.

## Batch 8 gate

Only Batch 8 may next be considered: adversarial crash, replay, contention, expiry, revocation,
reconstruction and secret-exclusion proof across the redesigned boundary, authority, activation,
admission and stationary-resolution corridor.

Batch 8 may not invoke a provider, perform external I/O, send an outbound byte, migrate a live
command, open Iron Gate or Lazaretto, or claim provider outcome.
