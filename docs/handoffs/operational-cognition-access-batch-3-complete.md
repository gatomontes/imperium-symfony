# Operational Cognition Access Batch 3 complete

## Completed boundary

The exact opaque Clavium lease and exact Curia cognition authority are now consumed together into one durable operational invocation claim before external I/O.

The cognition authority has an explicit deterministic identity. Claiming acquires fixed-order locks for that authority and the lease, rereads and validates the complete request, Imperator decision, and lease lineage, and seals both consumptions in one immutable record. This makes contention single-winner even when claimants cross lease and authority identities.

The claim binds the target Manifestation, provider/model/configuration, resource ceiling, input and Profile/model requirement digests, iteration, source identities and digests, consumption time, and one stable provider idempotency identity. Identical replay returns the same claim. Expired, consumed, substituted, divergent, tampered, or partially consumed sources fail stopped.

The checkpoint is durable pre-I/O. No credential has been resolved, no adapter constructed, no provider invoked, and no network access performed. Automatic replay after an unknown provider outcome remains prohibited.

## Verification

```bash
php vendor/bin/phpunit tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php
```

The focused suite includes process-level contention. The user performs local PHP verification; no runtime result is claimed from the implementation workspace.

## Next batch

Implement **Operational Cognition Access Batch 4: claim-bound operational broker**.

The broker must reread the exact durable claim, resolve the credential only inside the broker callback, construct the strict DeepSeek adapter for that call only, preserve the provider idempotency identity and existing failure/response/recovery contracts, and return only bounded output to the Manifestation. The directly injected operational Symfony agent must be removed. This migration will not close the system-wide bypass gate while other direct platform-bound agents remain.
