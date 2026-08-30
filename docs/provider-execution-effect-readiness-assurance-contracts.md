# Provider Execution Effect Readiness — Batch 1 assurance contracts

## Result

`BATCH_1_AUTHORITY_EMPTY_PROVIDER_ASSURANCE_CONTRACTS_COMPLETE`

Three separately versioned contracts define provider-assurance evidence without
creating a producer, validator, admitted record or runtime authority.

| Contract | Exact role | Explicitly not |
| --- | --- | --- |
| `ProviderAssuranceEvidenceSourceContract` | Identifies an exact official source, immutable provider artifact or later sterile observation with URI, observation time, digest, version and immutability posture | Provider conformance, evidence admission, activation or execution authority |
| `AgentMailDirectSendAssuranceProfileContract` | Defines exact AgentMail `email.send` endpoint, organization collision scope, idempotency syntax, request equivalence, completed duplicate behavior, changed-request conflict, completion-anchored retention and explicit unknowns | An observed provider result, live-call contract or permission to retry |
| `ProviderAssuranceEvidenceAdmissionContract` | Defines the future immutable admission result binding exact sources, profile, admitted claims, unknowns, threat model, review validity and revocation | A producer, current admitted record, principal/binding activation, credential access or provider call |

## Exact assurance boundary

The profile is restricted to:

- provider `agentmail`;
- operation `email.send`;
- endpoint `POST /v0/inboxes/{inbox_id}/messages/send`;
- organization-, endpoint-, inbox- and content-bound collision semantics;
- an explicit `Idempotency-Key` syntax contract;
- completed exact-duplicate return of the original message and thread identity;
- changed-request conflict plus mandatory local collision refusal;
- retention anchored to provider completion, never local effect-start; and
- explicit unknowns for in-progress duplicates, query by key, completion time
  without a response and remote cryptographic authorship.

Every contract preserves `UNKNOWN_REPLAY_PROHIBITED`. Completed-duplicate
documentation is evidence input only and cannot be converted into automatic
retry permission.

## Evidence versus authority

Source definition records provenance. The assurance profile describes claims
and unknowns. A future admission record may state which claims were admitted
for a bounded review period. None is execution authority.

No contract can activate the `ATTESTED_INERT` executor principal, make the
`BOUND_INACTIVE` implementation binding live-capable, define the missing
live-call runtime, consume the corrected v2 winner, resolve a stationary
credential or approach the first outbound byte.

## Threat model and secret exclusion

The future admission contract remains limited to one-root
`TRUSTED_WRITER_CANONICAL_INTEGRITY`, authenticated-channel trust and no
hostile-writer or distributed-execution claim. It records provider/operation
facts and digests only. Credential references, secrets and capability material
are outside every schema.

## Closed perimeter

Batch 1 defines constants only. It adds no service, validator, store, fixture,
provider evidence record, principal lifecycle transition, binding activation,
live-call contract, credential access, provider adapter, transport, external
I/O, retry, live-consumer migration, Iron Gate or Lazaretto behavior.

## Batch 2 gate

Only Batch 2 may next be considered: pure fail-closed validators and immutable
caller-supplied offline fixture stores for the three Batch 1 contracts.

Batch 2 may validate and store evidence fixtures only. It may not fetch provider
documentation, call a provider, claim observed conformance, activate a principal
or binding, define a live-call runtime contract, issue or consume execution
authority, handle a credential, authorize retry, migrate a live consumer, or
open Iron Gate or Lazaretto.
