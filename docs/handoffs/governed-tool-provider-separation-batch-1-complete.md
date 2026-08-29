# Governed Tool and Provider Separation Batch 1 complete

## Result

Batch 1 defines five provider-neutral contracts:

1. `GovernedToolOperationContract`;
2. `ProviderImplementationBindingContract`;
3. `ProviderRequestEncoderContract`;
4. `ProviderEvidenceDecoderContract`; and
5. `NormalizedToolResultContract`.

Their exact shapes, producer/consumer postures, substitution prohibitions and non-authorities are
recorded in `docs/governed-tool-provider-separation-contracts.md`.

No producer or consumer was implemented or migrated. Existing AgentMail services do not consume the
new contracts. Runtime behavior is unchanged.

## Authorized continuation

Only Batch 2 may next be considered: implement one immutable provider-neutral canonical
`email.send` tool definition owned by Armory and conforming exactly to
`GovernedToolOperationContract`. Its payload, effect and normalized-return semantics must be exact.
It may create only the tool-definition producer and record; it may not select a provider, create a
provider binding, issue or resolve credentials, encode a provider request, decode evidence, execute
the tool, perform external I/O or migrate any existing consumer.

Batch 2 is not authorized by completion alone. Proceed only under an explicit continuation
instruction.

## Preserved perimeter

AgentMail, Iron Gate, Lazaretto, the live command, inbound webhook, sortie, credential-platform,
revocation, propagation, telemetry, reassessment, containment and incident behavior remain closed.

## Continuation

Read `docs/next-campaign-governed-tool-provider-separation.md`,
`docs/governed-tool-provider-separation-contracts.md`, and this handoff. Begin Batch 2 only.
