# Provider Effect Principal and Binding Activation Resumption Batch 1 contracts

## Result

RESUMPTION_BATCH_1_AUTHORITY_EMPTY_CANONICAL_RESOLUTION_AND_ACTIVATION_INPUT_CONTRACTS_COMPLETE

Batch 1 defines two authority-empty contracts and no runtime implementation.

## Canonical production-winner resolution admission

`ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract`
defines the future immutable admission that joins exactly one canonical
provenance-production winner to:

- its sealed activation decision;
- its principal attestation, provider-assurance admission and
  execution-boundary evidence;
- the exact provider-executor-principal generation, binding, process boundary,
  provider and operation target;
- an unconsumed, exercisable, single-use activation authority with exact
  validity and revocation identity; and
- a shared replay/contention root spanning the instance, principal generation,
  process boundary, production, decision and authority identities.

Every evidence reference is the exact `id`, `digest` and `schema` triple.
Changed evidence conflicts. Replay is exact only.

## Canonical activation input

`ProviderExecutorPrincipalActivationCanonicalInputContract` defines the exact
sealed envelope a later canonical activation entry point may accept. It carries
the resolution admission and repeats the same immutable evidence, target,
authority and replay/contention identities. The shared field contracts prevent
the activation input from weakening or substituting the admitted target or
authority.

## Closed runtime perimeter

These contracts do not resolve live custody, create either record, create an
activation winner, issue or consume authority, activate a principal or provider
binding, handle a credential or capability, invoke a provider, start an effect
or external I/O, authorize retry, migrate a live consumer, or grant continuing
authority. Iron Gate and Lazaretto remain closed.

The provider binding remains `BOUND_INACTIVE`.
`UNKNOWN_REPLAY_PROHIBITED` remains binding.
