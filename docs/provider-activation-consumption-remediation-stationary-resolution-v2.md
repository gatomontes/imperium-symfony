# Provider Activation-Consumption Remediation — Batch 6 stationary resolution migration

## Result

BATCH_6_STATIONARY_RESOLUTION_REQUIRES_COMBINED_V2_WINNER

GovernedStationaryCredentialResolutionV2Service accepts only an exact v2 combined admission ID and
record. It validates both activation consumption and durable-authority consumption before resolving
the stationary deployment credential inside the same process.

## Required lineage

A first proof requires:

- intact combined-admission schema v2;
- exact consumed single-operation activation with non-continuing authority;
- exact consumed single-use durable execution authority;
- the activation-and-authority combined effect-start checkpoint;
- exact intact activation, authority, principal, binding and boundary lineage;
- current unexpired and unrevoked inputs;
- stationary same-process credential possession; and
- credential resolution still false before the callback-local proof.

A v1 admission ID is rejected. V1 records remain immutable historical evidence but do not prove the
corrected combined winner.

## Secret and replay posture

Credential material is exposed only to the fixed internal callback. The proof persists no secret,
environment-variable name, credential reference or capability. It records no provider invocation,
external I/O, outbound byte or provider outcome.

Exact completed proof replay returns the immutable proof before rereading credential material or
revalidating expired lineage. This is evidence reconstruction, not renewed authority.

## Preserved boundary

No live command is migrated. No provider is invoked, no external I/O occurs, no byte is sent, no
retry is authorized, and Iron Gate and Lazaretto remain closed.

## Next gate

Only remediation Batch 7 may next be considered: adversarial proof across the corrected combined
admission, lawful revocation race and v2 stationary resolution, followed by the repeated terminal
audit. No provider effect or live adoption is authorized.
