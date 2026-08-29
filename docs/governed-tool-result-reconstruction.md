# Governed tool-result reconstruction

## Status

`BATCH_7_READ_ONLY_SEPARATED_RESULT_RECONSTRUCTION_COMPLETE`

The new reconstruction route begins from one normalized Lazaretto admission and rebuilds the exact
canonical tool definition, source authorization, execution claim, provider binding, credential
eligibility, neutral raw evidence, bound decoder identity, normalized result and admission.

Every persisted reference and digest is revalidated. A different credential eligibility record,
provider binding, decoder, authorization target or raw evidence record cannot be attached to the
chain. The route is read only and explicitly reports that no provider was reinvoked, no credential
was resolved, no external I/O was performed and no continuing authority exists.

The credential-consumption attempt is explicitly `null` for this inactive route: Batch 5 proved
eligibility before resolution, and no later batch has authorized consumption or invocation.
Runtime behavior is unchanged.
