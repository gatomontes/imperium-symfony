# Governed Tool and Provider Separation Preparation Batch 0 complete

## Result

Preparation Batch 0 is complete in
`docs/governed-tool-provider-separation-preparation-inventory.md`.

The runtime has a real generic substrate—operation, tool and capability identities, a transport
interface, Iron Gate propagation and generic Lazaretto lineage—but the native outbound-email proof
corridor fuses AgentMail into authority validation, credential-bound invocation, result decoding,
receipt admission and reconstruction. The live command additionally self-assembles commission,
authorization, credential capability and provider selection instead of consuming the native
authority corridor.

Runtime behavior is unchanged. Provider Execution Assurance remains paused after its completed
Preparation Batch 0.

## Authorized continuation

Only Batch 1 is authorized: define and test the separately versioned
`GovernedToolOperationContract`, `ProviderImplementationBindingContract`,
`ProviderRequestEncoderContract`, `ProviderEvidenceDecoderContract` and
`NormalizedToolResultContract`, including exact producer/consumer postures and explicit
non-authorities.

Batch 1 may not implement a producer or consumer, modify an existing runtime service, select or call
AgentMail, resolve credentials, perform external I/O, create a registry, migrate the command, open
Iron Gate or Lazaretto, or change inbound webhook, sortie, credential-platform, revocation,
propagation, telemetry, reassessment, containment or incident behavior.

## Continuation

Read `docs/next-campaign-governed-tool-provider-separation.md` and
`docs/governed-tool-provider-separation-preparation-inventory.md`. Begin Batch 1 only.
