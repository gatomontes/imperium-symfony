# Governed provider-binding route

## Status

`BATCH_3_PROVIDER_BINDING_ROUTE_COMPLETE_INACTIVE`

## Result

`ProviderBindingAuthorizationContract` defines the exact upstream authority shape, while
`ProviderImplementationBindingService` consumes that pre-existing authority to produce one
immutable `ProviderImplementationBindingContract` record. The producer accepts only the authority
identifier and binding time. It cannot select or substitute a provider through call arguments.

The authority fixes the exact governed tool definition, authorization target, provider and adapter
version, assurance profile, credential family, request encoder, evidence decoder and destination
policy. The credential family must name the selected provider and must prohibit secret persistence.
The destination must remain exact and provider substitution must remain prohibited.

The canonical `email.send.v1` Armory record must exist, remain intact and match the authority's
exact digest. The source authority must be intact, exercisable, single use, unexpired and
non-continuing. Consumption is recorded through the canonical authority-consumption store. Exact replay converges;
changed sealed facts, provider/credential mismatch, target mismatch and expiry are refused.

## Closed boundary

The resulting record remains `BOUND_INACTIVE`. It grants no tool execution authority, credential
capability or continuing authority. It contains no credential material. It does not resolve a
credential, encode a request, perform external I/O, decode provider evidence, admit evidence or
change any existing runtime consumer.

No provider-specific implementation is introduced in this batch.
