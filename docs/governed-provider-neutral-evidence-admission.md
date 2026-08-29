# Governed provider-neutral evidence and admission

## Status

`BATCH_6_PROVIDER_NEUTRAL_EVIDENCE_ADMISSION_COMPLETE_INACTIVE`

Raw provider bytes are now preserved without interpreting provider fields. The preserved record
binds exact tool, authorization, execution claim and provider-binding references plus HTTP metadata
and a content digest. Only the decoder named by that provider binding may interpret the bytes.

The bound decoder produces a provider-neutral `NormalizedToolResultContract` record. Lazaretto's
new admission route accepts that normalized schema and never parses raw provider content. Raw
evidence cannot bypass normalization. Decoder substitution and changed bytes fail closed.

The existing result sealer, live Lazaretto admission and command remain unchanged. No provider was
invoked and no external I/O occurred. Runtime behavior is unchanged.
