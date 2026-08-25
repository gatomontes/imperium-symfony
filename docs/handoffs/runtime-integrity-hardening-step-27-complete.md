# Runtime Integrity Hardening Step 27 Complete

## Scope

The complete Citadel Legate governed-cognition chain now uses the canonical `RecordReferenceValidator`.

## Migrated boundaries

- governed commission issuance;
- governed commission disposition;
- cognition-turn authorization;
- the bounded cognition turn; and
- cognition-result delivery.

## Guarantees retained

- Each service keeps its existing schemas, actors, authorities, statuses, replay behavior, and `CIT3xx`/`CIT4xx` error vocabulary.
- Exact source identity and digest resolution now passes through one path-contained validator.
- Record loading and canonical digest verification no longer have five separate Citadel implementations.
- Provider invocation remains bounded to the single-use claim and cognition-turn authority; this migration grants no new authority.

## Verification

`LegateCognitionReferenceMigrationTest` covers all five services and verifies canonical validator delegation plus preservation of each service's chain-error contract. The complete PHPUnit suite remains the behavioral gate in the local/CI PHP 8.4 environment.

## Next

Continue with another cohesive lifecycle boundary rather than a system-wide mechanical rewrite.
