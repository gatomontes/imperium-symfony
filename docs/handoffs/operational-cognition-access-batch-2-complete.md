# Operational Cognition Access Batch 2 complete

## Completed boundary

Clavium now issues one opaque operational cognition lease from the exact authorized Imperator provider-resource decision and its exact Curia cognition request.

The lease is immutable, expiring, single-use, and initially unconsumed. It binds the complete decision/request lineage, target Manifestation and operational Seat/binding/custody facts, provider/model/configuration, resource ceiling, input and Profile/model requirement digests, iteration, attributable Locksmith occupancy, issue time, and expiry.

Issuance requires a current `AUTHORIZED` decision, unconsumed lease-activation authority, intact current request, exact matching lineage, and an active Locksmith specifically authorized to issue operational cognition leases. Refused, expired, mismatched, superseded, or divergent inputs fail stopped.

The record contains no credential reference or credential material. It transfers no credential possession, network authority, provider-invocation authority, execution authority, or continuation authority. No credential is resolved and no provider is contacted.

## Verification

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/OperationalCognitionLeaseServiceTest.php
```

The user performs local PHP verification. No runtime result is claimed from the implementation workspace.

## Next batch

Implement **Operational Cognition Access Batch 3: durable invocation claim**.

The claim transition must atomically consume the exact lease and exact cognition authority before any external I/O, persist a stable provider idempotency identity, and reject expired, consumed, substituted, partially consumed, or divergent claims. It must remain credential-free and must not construct an adapter or invoke a provider.
