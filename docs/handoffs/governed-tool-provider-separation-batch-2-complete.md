# Governed Tool and Provider Separation Batch 2 complete

## Result

Batch 2 implements the immutable Armory-owned, provider-neutral canonical `email.send` tool
definition in `CanonicalEmailSendToolDefinitionService` and documents it in
`docs/governed-email-send-tool-definition.md`.

The definition binds exact payload semantics, the irreversible external-communication effect class,
normalized-result semantics, credential exclusion and the mandatory separate provider-binding
policy. Exact replay converges; changed content conflicts. The definition remains
`DEFINED_INACTIVE`.

No provider was selected or bound. No credential was issued or resolved. No request was encoded, no
external I/O occurred, and no existing runtime consumer was migrated. Runtime behavior is unchanged.

## Authorized continuation

Only Batch 3 may next be considered: define and implement the separately governed provider-binding
route that binds one exact provider implementation, adapter version, assurance profile, credential
family, request encoder, evidence decoder and destination policy to one exact tool authorization
target. Provider selection authority and binding production must remain separate.

Batch 3 may not extract AgentMail into the new adapter, resolve credentials, invoke a provider,
perform external I/O, decode or admit provider evidence, migrate reconstruction or change the live
command. Batch 3 is not authorized by completion alone.

## Preserved perimeter

AgentMail, Iron Gate, Lazaretto, the live command, inbound webhook, sortie, credential-platform,
revocation, propagation, telemetry, reassessment, containment and incident behavior remain closed.

## Continuation

Read `docs/next-campaign-governed-tool-provider-separation.md`,
`docs/governed-tool-provider-separation-contracts.md`,
`docs/governed-email-send-tool-definition.md`, and this handoff. Begin Batch 3 only.
