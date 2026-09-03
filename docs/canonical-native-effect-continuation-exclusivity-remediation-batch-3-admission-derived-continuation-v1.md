# Canonical Native Effect Continuation and Exclusivity Remediation — Batch 3 admission-derived continuation v1

`BATCH_3_ADMISSION_DERIVED_CONTINUATION_AND_RECEIPT_BINDING_COMPLETE_DOUBLES_ONLY`
`BATCH_4_NOT_AUTHORIZED`

First callback start now requires the exact `NativeEffectContinuationCapability`
recognized and consumed by the same process-local issuer registry. The service
validates its admission digest, semantic tuple, authority-consumption identity,
process boundary and expiry before writing callback start. A copied object,
fresh issuer, restarted process, wrong payload or wrong idempotency key cannot
start the callback.

`NativeEffectDoubleExecutionService::execute()` no longer accepts an authority
array. Operation, destination, provider/adapter identity, expected-return
contract, authority lineage and native receipt derive from the sealed v2
admission receipt input. Payload and key remain ephemeral but are checked
against admitted digests before callback start. Old-digest, resealed, stale or
caller-fabricated authority arrays have no parameter through which to alter the
request or receipt.

Callback-start without response remains terminal unknown. Once a response is
sealed, a fresh process may perform receipt-only forward completion after
expiry without recognizing or minting a continuation object; the provider
double is not invoked again. Existing receipts remain read-only.

This batch uses provider doubles only. It adds no credential resolver,
AgentMail transport, HTTP client, network/external I/O, command or live effect.
Batch 4 is not authorized by this document.
