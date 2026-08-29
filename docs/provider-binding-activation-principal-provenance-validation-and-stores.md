# Provider Binding Activation Principal Provenance — validation and immutable stores

## Status

`BATCH_2_FAIL_CLOSED_OFFLINE_FIXTURE_VALIDATION_COMPLETE`

`ImperatorPrincipalProvenanceFixtureStore` provides three separate canonical immutable evidence
stores for caller-supplied offline constitution-authority, principal-version and lifecycle-
disposition fixtures. Each fixture must already contain an exact canonical digest and field order.
The boundary revalidates that digest before the immutable store seals the identical record.

## Fail-closed invariants

- Constitution route and transition must match exactly.
- Operator Root, operationalization, identity, target and scope shapes are exact.
- Only `provider_binding_activation_authority` may be true; outbound-email, credential,
  provider-execution and corridor-disposition authority must be false.
- Constitution authority is unconsumed, non-continuing, single-use and expires within fifteen
  minutes.
- Principal versions bind instance, identity, source references, positive generation, lifecycle
  dates and one contract status.
- Credential references, credential secrets and serialized capabilities are explicitly absent.
- Generation one has no prior version; later generations require one.
- Lifecycle source status must permit the requested disposition.
- Renewal and supersession require an exact successor reference; other dispositions prohibit one.
- Only activation or renewal may leave caller-authority issuance permitted after the effective time.
- Lifecycle disposition preserves historical attribution, changes no authority scope and performs
  no external action.

The directories are under `var/imperium/evidence/imperator-principal-provenance`. They are offline
fixtures, not the live Imperator principal registry or a current-state index.

## Preserved perimeter

The store exposes no authority issuer, principal producer, installer, lifecycle transition,
current-state index, recovery service, reconstruction service or runtime consumer. It installs and
mutates no principal, issues no caller authority, changes no corridor disposition or activation
artifact, handles no credential, invokes no provider, performs no external I/O, and opens neither
Iron Gate nor Lazaretto. Provider Execution Assurance remains paused.
