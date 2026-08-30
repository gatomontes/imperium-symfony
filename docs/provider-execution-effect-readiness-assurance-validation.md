# Provider Execution Effect Readiness — Batch 2 assurance validation

## Result

`BATCH_2_FAIL_CLOSED_ASSURANCE_FIXTURE_VALIDATION_COMPLETE`

Batch 2 adds one pure fail-closed validator and one immutable offline fixture
store for the three Batch 1 assurance contracts.

The validator requires exact field order, schema, canonical digest, sealed
posture, provider, operation, endpoint, evidence references, collision scope,
idempotency syntax, request equivalence, completed-duplicate behavior,
changed-request conflict, completion-anchored retention, explicit unknowns,
threat limits, review validity and non-authorizing admission status.

## Fixture boundary

`ProviderAssuranceEvidenceFixtureStore` stores only caller-supplied fixtures
under `var/imperium/evidence/provider-execution-effect-readiness`:

- exact evidence-source fixtures;
- exact AgentMail direct-send assurance-profile fixtures; and
- exact provider-assurance admission fixtures.

It performs no network fetch and observes no provider. Immutable storage proves
only that a supplied fixture passed the local contract validator. It does not
prove that remote documentation is current, that AgentMail conforms, or that an
admission record is competent live provider authority.

## Fail-closed rules

Validation refuses:

- provider, operation or endpoint substitution;
- changed or missing source references;
- non-HTTPS source identity;
- changed organization/inbox/endpoint/content equivalence;
- altered key syntax;
- local-time substitution for provider-completion retention;
- converting an explicit unknown to a known claim;
- hostile-writer or distributed guarantees;
- noncanonical fields or digest; and
- any status that implies execution authority.

Exact replay of one stored fixture converges through the immutable record store.
Changed content under the same ID conflicts.

## Closed perimeter

The validator and fixture store import no credential capability, environment
broker, provider transport, AgentMail transport, combined execution admission,
Iron Gate or Lazaretto component. They activate nothing, handle no credential,
invoke no provider, perform no external I/O and authorize no retry or adoption.

## Batch 3 gate

Only Batch 3 may next be considered: offline interruption, exact replay,
changed-evidence conflict and immutable-store contention proof for the three
fixture paths.

Batch 3 may not fetch or observe provider evidence, promote fixtures into live
authority, activate a principal or binding, define a live-call runtime, handle
credentials, invoke a provider, perform external I/O, authorize retry, migrate
a consumer, or open Iron Gate or Lazaretto.
