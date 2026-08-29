# Governed Tool and Provider Separation Batch 8 complete

## Result

Batch 8 removes the live command's self-assembled commission, authorization, provider selection
and credential capability. `imperium:email:send-agentmail` now fails closed before credential
resolution or external I/O.

The separated corridor cannot truthfully replace that behavior yet. Provider bindings are sealed
`BOUND_INACTIVE`, credential eligibility records explicitly deny external I/O, and
`EnvironmentCredentialBroker` retains an issued capability only in the issuing process. Reissuing
or reconstructing an opaque capability inside the command would manufacture the authority this
campaign exists to remove. The campaign's narrower-sequence rule therefore requires refusal.

No provider was invoked. No credential was issued or resolved. Iron Gate and Lazaretto were not
opened. Existing durable authority, binding, eligibility, evidence and reconstruction records are
unchanged.

## Authorized continuation

Only Batch 9 may next be considered: adversarial proof and terminal audit. It must decide whether a
separate campaign may define provider-binding activation and cross-process opaque capability
custody without weakening the existing credential boundary.

Batch 9 may not activate a provider binding, persist credential references or secrets, reissue a
capability, invoke AgentMail, perform external I/O, open Iron Gate or Lazaretto, or change sortie,
credential-platform, revocation, propagation, telemetry, reassessment, containment or incident
behavior. Batch 9 is not authorized by completion alone.

## Continuation

Read `docs/next-campaign-governed-tool-provider-separation.md`, the preparation inventory, the
contracts, and this handoff. Begin Batch 9 only after explicit authorization and a fresh green local
suite.
