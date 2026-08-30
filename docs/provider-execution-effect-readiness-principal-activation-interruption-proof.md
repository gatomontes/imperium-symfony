# Provider Execution Effect Readiness — Batch 8 principal-activation interruption proof

## Result

`BATCH_8_OFFLINE_PRINCIPAL_ACTIVATION_FIXTURE_INTERRUPTION_PROVED`

Both caller-supplied principal-activation fixture paths now have truthful
offline interruption, replay, conflict and same-root contention proof.

## Cut matrix

| Fixture path | Before immutable commit | After immutable commit | Recovery |
| --- | --- | --- | --- |
| Activation decision | Leaves no decision record | Leaves one immutable decision winner before the injected interruption | Exact replay returns the same winner |
| Principal activation | Leaves no activation record | Leaves one immutable activation winner before the injected interruption | Exact replay returns the same winner |

The injected cuts surround only `ImmutableRecordStore::put`. They do not model
or perform a competent decision, activation-authority consumption, principal
activation or any external effect.

## Replay, conflict and contention

Exact replay of either validated fixture converges without a second record.
Changed valid decision evidence under the same decision identity conflicts with
`PST111_IMMUTABLE_RECORD_CONFLICT`.

Changed valid activation evidence is proved using a second internally valid
decision lineage bound into the same activation identity. Validation succeeds,
then immutable storage refuses the changed winner with
`PST111_IMMUTABLE_RECORD_CONFLICT`. The conflict is therefore a durable
identity conflict, not merely malformed evidence.

Two separately constructed services sharing one authoritative root converge on
the same decision and activation records. This proves one-root filesystem-lock
contention only. It does not prove multi-host consensus, hostile-writer
non-forgeability or split-brain resistance.

## Recovery and non-authorities

A pre-commit cut authorizes nothing because no fixture exists. A post-commit cut
proves only that caller-supplied offline evidence was durably stored before the
injected exception. Recovery may replay the exact put; it may not infer or
repeat a runtime transition.

The principal remains inert and the provider binding remains inactive. A stored
activation-shaped fixture is not a live activation, execution authority,
credential capability, process identity or retry authority.
`UNKNOWN_REPLAY_PROHIBITED` remains binding after any possible provider
effect.

## Secret exclusion and closed perimeter

The interruption service delegates only to the offline fixture store. It imports
no authority-consumption store, credential broker, credential capability,
provider transport, execution-authority issuer, Iron Gate or Lazaretto
component.

Batch 8 does not produce a decision, issue or consume authority, activate a
principal or binding, define a live-call runtime, handle or resolve a credential
or capability, invoke a provider, perform external I/O, authorize retry, migrate
a live consumer or repair evidence.

## Batch 9 gate

Only Batch 9 may next be considered: read-only aggregate reconstruction and
classification of the exact decision/activation evidence chain as
`ELIGIBLE_OFFLINE_EVIDENCE`, `INCOMPLETE`, `CONFLICTED` or `REFUSED`.

Reconstruction may read and validate only. It may not create, repair, refresh,
revoke, expire, reactivate or promote a fixture or runtime principal.

Estimated campaign countdown after Batch 8: approximately two batches,
excluding any separately selected sterile provider-conformance campaign.
