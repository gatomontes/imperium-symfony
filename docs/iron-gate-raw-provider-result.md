# Iron Gate raw provider result

## Status

`BATCH_9_RAW_PROVIDER_RECEIPT_AND_OUTCOME_DURABLE`

Batch 9 turns one actually observed AgentMail HTTP response into one immutable result aggregate.
The aggregate binds the exact provider-invocation admission and execution claim, HTTP status,
provider idempotency key, effect and resolution times, raw response digest and bytes, and recovery
posture.

For a 2xx response, the service requires valid JSON containing non-empty `message_id` and
`thread_id` before it records `ACCEPTED`. A non-2xx observed response records `REJECTED` and cannot
invent a provider receipt identity. Missing bytes, an interrupted callback or process death creates
no result; the Batch 8 admission remains `UNKNOWN_REPLAY_PROHIBITED`.

The raw bytes are base64-encoded inside the same immutable result aggregate as the provider
outcome. This avoids a partial raw-receipt/outcome pair. Content digest is computed over the exact
decoded bytes, not the encoded representation. One admission has one result winner; conflicting
response content or status fails closed.

Recovery is sealed at `RAW_RECEIPT_SEALED` with `automatic_replay_permitted=false` and
`provider_reinvoked=false`. Forward recovery may use only the named raw receipt.

## Closed boundary

Batch 9 uses only supplied observed response bytes. It performs no provider invocation, credential
resolution or network I/O. No existing command or transport changes. The raw result is not yet a
Lazaretto-admitted artifact and does not satisfy the final receipt-binding contract. Sortie remains
separate.

## Smallest safe continuation

Only a separately authorized Batch 10 may validate the expected return contract, admit the exact
raw result through a deterministic Lazaretto boundary and provide read-only reconstruction from
source authorization to admitted receipt. It may not expand Lazaretto trust or sanitization policy,
reinvoke the provider or merge sortie.

