# Iron Gate journal-bound AgentMail invocation

## Status

`BATCH_8_JOURNAL_GATED_PROVIDER_CALLBACK_NO_LIVE_IO`

Batch 8 places credential use and AgentMail request construction behind the exact Batch 7
effect-start journal.

Before any provider callback is reachable, `DeterministicJournalBoundCredentialBroker` validates
the immutable journal and claim, exact capability ID and credential-reference digest, commission,
operation, payload digest, expiry, replay identity and provider key. It then persists one immutable
provider-invocation admission under the journal lock.

That admission is written before credential resolution and callback entry. It records
The version-2 admission records `admission_committed=true`, `consumption_attempted=false`,
`provider_callback_may_have_run=false` and `outcome=NOT_ATTEMPTED`. Separate immutable credential
attempt and callback-start checkpoints then record the exact progression to
`provider_callback_may_have_run=true` and
`outcome=UNKNOWN_REPLAY_PROHIBITED`, but never credential secret material. A crash after admission
and before callback therefore stops rather than replaying. Every later invocation against the same
journal is refused durably, including after process restart.

## AgentMail adapter

`AgentMailIdempotencyHeaderAdapter` accepts only `email.send`, an exact HTTPS
`api.agentmail.to/v0/inboxes/{id}/messages/send` destination, valid JSON with recipients, non-empty
authentication and the journal's non-empty key. It supplies the provider callback with:

- `Authorization: Bearer …`;
- `Content-Type: application/json`;
- `Accept: application/json`; and
- the exact governed `Idempotency-Key`.

Authentication exists only in callback memory. The durable admission contains its opaque
capability identity and credential-reference digest, never the header or secret.

## Closed boundary

Tests use an in-memory credential broker and provider callback. No live network request is made.
The existing command, `AgentMailEmailTransport`, `DeterministicBoundaryExecutor`, raw payload,
Lazaretto and sortie remain unchanged. No provider response is persisted or treated as accepted.

## Smallest safe continuation

Only a separately authorized Batch 9 may validate a governed provider result and persist the raw
provider receipt plus an exact accepted/rejected outcome against the invocation admission. Missing
or interrupted responses must remain `UNKNOWN_REPLAY_PROHIBITED`. Lazaretto admission and
reconstruction remain closed.
