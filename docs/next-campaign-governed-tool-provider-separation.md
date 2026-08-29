# Next campaign: Governed Tool and Provider Separation

## Status

`BATCH_4_COMPLETE_BATCH_5_ELIGIBLE_NOT_AUTHORIZED`

Provider Execution Assurance Preparation Batch 0 established useful AgentMail facts, but review of
the executable corridor found that AgentMail is not merely an adapter behind a governed
`email.send` tool. Provider identity and response shape are embedded in Curia request validation,
authorization scope, credential-bound invocation, result sealing, Lazaretto admission,
reconstruction evidence and the live command.

This campaign separates five identities that must never be collapsed:

1. the governed tool operation (`email.send`);
2. the exact authorized external effect;
3. the selected provider implementation;
4. the credential capability usable by that implementation; and
5. the provider-specific evidence decoded into a normalized tool result.

Preparation Batch 0 is complete in
`docs/governed-tool-provider-separation-preparation-inventory.md`. Only Batch 1 is authorized.

Batch 1 is now complete in `docs/governed-tool-provider-separation-contracts.md`. Five separately
versioned provider-neutral contracts define exact identities, producer/consumer postures,
substitution prohibitions and non-authorities. No producer or consumer is implemented. Only Batch 2
may next be considered, and it is not authorized by campaign status alone.

Batch 2 is now complete in `docs/governed-email-send-tool-definition.md`. Armory owns one immutable,
provider-neutral `email.send` version-1 definition with exact payload, effect, normalized-result,
secret and provider-binding policies. It remains `DEFINED_INACTIVE`; no provider is selected, no
credential is available and no consumer is migrated. Only Batch 3 may next be considered, and it is
not authorized by campaign status alone.

Batch 3 is now complete in `docs/governed-provider-binding-route.md`. La Cortine can consume one
exact, pre-existing Imperator provider-binding authority and seal its provider, adapter, assurance,
credential-family, encoder, decoder and destination-policy identities against one exact inactive
tool target. The binding producer has no provider-selection arguments and cannot issue its source
authority. Only Batch 4 may next be considered, and it is not authorized by campaign status alone.

Batch 4 is now complete in `docs/governed-agentmail-adapter-profile.md`. AgentMail endpoint,
authentication-header and credential-reference syntax, request encoding and receipt decoding are
separately extracted but inert. Existing consumers remain unchanged. Only Batch 5 may next be
considered, and it is not authorized by campaign status alone.

## Governing invariants

- Tool authority never implies authority to select or replace a provider.
- Provider selection never supplies execution authority.
- A provider adapter never issues its own credential capability.
- A credential capability never identifies the tool authority that may exercise it by implication.
- Provider-specific response decoding never occurs inside generic Lazaretto policy.
- Generic evidence preserves the provider implementation, binding, assurance profile and exact raw
  bytes without treating those facts as the normalized tool result.
- A command may consume an already-opened authority; it may not manufacture a commission,
  authorization, provider binding or credential capability for itself.
- Provider substitution, decoder substitution, assurance-profile substitution and
  credential/provider mismatch fail before external I/O or admission.

## Preparation Batch 0 result

The generic substrate exists but is fragmented. `OutboundRequest`, `IronGate`,
`DeterministicTransport`, `TransportResult`, generic `Lazaretto` provenance and credential
capabilities already distinguish operation, tools and capabilities. No canonical deterministic tool
registry, provider-implementation binding, provider-neutral result decoder contract or normalized
`email.send` receipt exists. The unused receipt-binding corridor is AgentMail-specific, and the live
AgentMail command self-assembles authority-shaped identifiers and directly instantiates its provider
transport.

The inventory classifies the current posture and proposes the smallest safe sequence. It changes no
runtime behavior and authorizes no provider call or live migration.

## Proposed sequence

Only the specifically named next batch is authorized at each handoff.

1. **Batch 1 — separation contracts only.** Define separately versioned contracts for governed tool
   identity, provider implementation binding, provider assurance reference, provider request
   encoding, provider evidence decoding and normalized tool result. Define producer and consumer
   postures; implement none of them.
2. **Batch 2 — canonical `email.send` tool definition.** Add the immutable provider-neutral
   operation, payload semantics, effect semantics and normalized return contract.
3. **Batch 3 — provider binding route.** Create the separately authorized selection and binding of
   one provider implementation and assurance profile to one exact tool execution.
4. **Batch 4 — AgentMail adapter/profile extraction.** Move AgentMail endpoint, header, key syntax,
   request encoding and receipt decoding behind the new provider contracts without live I/O.
5. **Batch 5 — credential/provider enforcement.** Bind the opaque Clavium capability to the selected
   provider binding and refuse mismatch before resolution.
6. **Batch 6 — provider-neutral evidence and Lazaretto admission.** Preserve raw provider evidence,
   invoke the bound decoder, and admit only the normalized `email.send` result.
7. **Batch 7 — reconstruction migration.** Reconstruct tool authority, provider binding, credential
   attempt, raw provider evidence, decoder identity and normalized result read only.
8. **Batch 8 — live-command authority correction.** Replace self-assembled commission,
   authorization, capability and provider selection with consumption of exact pre-existing records.
   This batch remains ineligible until separately authorized after the unused corridor is complete.
9. **Batch 9 — adversarial proof and terminal audit.** Prove substitution, mismatch, collision,
   tamper, unknown outcome, secret exclusion and sterile second-adapter replaceability; then decide
   whether Provider Execution Assurance may resume.

Refusal or a narrower sequence is valid if a batch shows the abstraction cannot preserve an exact
authority or evidence fact.

## Exact Batch 1 boundary

Batch 1 may define and test contracts only. It may not change an existing runtime service, create a
provider registry, select AgentMail, resolve credentials, perform external I/O, migrate the command,
open Iron Gate or Lazaretto, or change sortie, credential-platform, revocation, propagation,
telemetry, reassessment, containment or incident behavior.
