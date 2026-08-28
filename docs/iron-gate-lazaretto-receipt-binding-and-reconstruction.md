# Iron Gate Lazaretto receipt binding and reconstruction

## Status

`BATCH_10_ACCEPTED_RECEIPT_BOUND_AND_RECONSTRUCTIBLE`

Batch 10 completes the accepted deterministic email evidence path without changing the existing
general Lazaretto policy.

`DeterministicLazarettoReceiptAdmissionService` admits only an intact Batch 9 `ACCEPTED` result
whose exact raw bytes decode as the expected `agentmail.message/v1` contract and whose
`message_id`/`thread_id` equal the sealed provider outcome. It recomputes the raw content digest and
creates one immutable `imperium.la-cortine.deterministic-receipt-binding/v1` record.

The binding preserves the execution claim, source authorization, complete request scope, provider
outcome and idempotency key, sealed raw-result location and digest, observed/received times,
validated Lazaretto artifact and recovery posture. Transformation is
`VALIDATED_AGENTMAIL_MESSAGE_RECEIPT_NO_CONTENT_MUTATION`.

Rejected results are truthful evidence but do not satisfy the expected message-return contract and
are not admitted as message artifacts. Unknown outcomes have no raw result and remain stopped.

## Read-only reconstruction

`DeterministicReceiptReconstructionService` performs no writes and no callbacks. From one binding it
resolves and digest-validates the exact claim, raw provider result, and Imperator issuance aggregate
containing the source authorization. It rechecks replay and execution identity, source
authorization, raw digest and outcome before returning the lineage.

Reconstruction seals its behavior in the returned proof as `provider_reinvoked=false`,
`credential_resolved=false` and `external_io_performed=false`. Credential secret material is absent
from every persisted artifact.

## Closed boundary

This is a deterministic receipt-admission slice, not an expansion of inbound trust, sanitization or
sortie policy. It invokes no provider, resolves no credential and performs no network I/O. Existing
`Lazaretto`, `InboundLazaretto`, command and transport behavior remain unchanged.

## Smallest safe continuation

Only a separately authorized Batch 11 may run the campaign-wide adversarial proof across concurrent
winners, crash windows, unknown outcomes, rejected results, tamper, reconstruction and secret
exclusion, then close or refuse to close the campaign. It may not open sortie or any deferred
governance boundary.

