# Credential-boundary Batch 7 governance substrate complete

Batch 7 establishes the common mechanical lifecycle for internal governance cognition without migrating a gateway or granting any cluster new authority.

## Exact sequence

1. A cluster-specific resolver rereads one existing native authority record and returns its normalized, digest-bound view.
2. The common request service binds that exact authority, cluster, Seat, purpose, input digest, model requirement, and expiry without consuming it.
3. Imperator independently authorizes or refuses the exact provider/model configuration and resource ceiling.
4. An authorized decision opens one single-use Clavium lease-activation authority; refusal opens none.
5. An authorized Locksmith occupancy issues one opaque, expiring lease without resolving or disclosing a credential.
6. A durable claim atomically consumes the lease and the original normalized governance authority before provider I/O.
7. The shared invocation journal reserves pre-I/O authority and supports in-flight, pre-I/O failure, unknown-outcome, and response-identity transitions; the shared response envelope accepts the governance claim family.

The substrate stops there. It contains no credential broker, provider adapter, direct agent, prompt, or gateway migration.

## Authority separation

`GovernanceCognitionAuthorityResolver` is an adapter contract, not an authority source. Every future cluster resolver must reread its native immutable record and normalize only facts already present: cluster/type/identity, source ID and digest, Seat, purpose, input digest, expiry, and single-use exercisability. The registry requires exactly one matching resolver. Zero or multiple resolvers fail stopped.

The common services cannot select a directory supplied by a caller, fabricate a source record, infer a purpose, widen a Seat, substitute a cluster, or convert a refusal into activation authority.

## Records

- `imperium.governance-cognition-request/v1`
- `imperium.imperator-governance-provider-resource-decision/v1`
- `imperium.clavium-governance-cognition-lease/v1`
- `imperium.clavium-governance-cognition-invocation-claim/v1`
- existing `imperium.clavium-provider-invocation-journal/v1`
- existing `imperium.clavium-provider-response-envelope/v1`

All records are immutable and digest-bound. The claim carries one stable idempotency identity, durable pre-I/O state, no credential material/reference, and no automatic replay after unknown outcome.

## Verification

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/GovernanceCognitionAccessSubstrateTest.php
```

The focused suite proves the authorized chain, refusal, missing and cross-cluster resolvers, expired/mismatched/consumed authority, divergent reuse, partial consumption, exact replay, opaque lease properties, and secret exclusion.

## Next batch

Batch 8 migrates Foundry only: Artificer specification, revision, ordinary review, and adversarial review. It must add Foundry-native authority resolvers, claim-bound broker invocation, stage-specific at-most-once proof, remove `artificer_specification` and `adversarial_reviewer`, and decrement the executable inventory from 32 to 30. No other cluster moves.
