# Runtime Integrity Hardening Step 29 Complete

## Scope

The Delegate Clavium model-access attestation and provider-invocation activation services now use canonical record loading and digest verification.

## Digest contract

`RecordReferenceValidator::isIntact()` now accepts an optional explicit digest prefix. The default remains the unprefixed canonical SHA-256 format used by Imperium runtime records. Clavium's provider-access assertion may retain its established `sha256:` representation without maintaining a separate digest implementation.

## Boundaries retained

- Model binding, provider assertion, Locksmith occupancy, and Imperator resource-decision chains remain exact.
- Credential references remain inside Clavium and only their digest enters the single-use lease.
- No credential possession or disclosure authority is introduced.
- Provider invocation remains pending until the bounded cognition turn claims it.
- Existing `CLV320`–`CLV339` failures, schemas, statuses, and replay behavior remain unchanged.

## Verification

- `RecordReferenceValidatorTest` covers prefixed integrity and proves the unprefixed default remains strict.
- `DelegateMissionClaviumReferenceMigrationTest` covers both credential-adjacent services.
- The full Delegate flow remains the behavioral gate in the local/CI PHP 8.4 environment.
