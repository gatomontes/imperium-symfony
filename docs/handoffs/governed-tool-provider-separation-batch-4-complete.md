# Governed Tool and Provider Separation Batch 4 complete

## Result

Batch 4 extracts AgentMail provider facts behind the provider request-encoder and evidence-decoder
contracts. The profile fixes the exact endpoint, Bearer header, credential reference syntax,
adapter version and receipt fields. Request encoding and receipt decoding are inert and independently
testable.

No credential was resolved. No external I/O occurred. No evidence was admitted. The existing
command, transport, Iron Gate, Lazaretto and reconstruction consumers were not migrated. Runtime behavior is unchanged.

## Authorized continuation

Only Batch 5 may next be considered: enforce exact credential-family/provider-binding compatibility
before opaque credential resolution.

Batch 5 may not invoke a provider, perform external I/O, admit provider evidence, migrate
reconstruction or change the live command. Batch 5 is not authorized by completion alone.

## Continuation

Read the campaign, contracts, Batch 2 definition, Batch 3 binding route,
`docs/governed-agentmail-adapter-profile.md`, and this handoff. Begin Batch 5 only after explicit authorization.
