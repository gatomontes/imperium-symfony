# Governed AgentMail adapter and profile extraction

## Status

`BATCH_4_AGENTMAIL_ADAPTER_PROFILE_EXTRACTED_INERT`

Batch 4 extracts the AgentMail provider identity, adapter version, assurance identity, credential
family and reference syntax, exact endpoint policy, Bearer header syntax, request encoding and
receipt decoding into separately named La Cortine components.

`AgentMailProviderRequestEncoder` is pure and performs no external I/O. Its transient request may
carry opaque authentication in memory, while its evidence record contains no secret or secret-derived
digest. `AgentMailProviderEvidenceDecoder` accepts already-sealed raw bytes, requires exact content
identity, extracts `message_id` and `thread_id`, and produces sealed decoder evidence. It does not
admit that evidence or produce a normalized result by itself.

The existing command, credential broker, transport, Iron Gate, Lazaretto and reconstruction routes
are unchanged and do not consume the extracted components. No credential is issued or resolved.
Runtime behavior is unchanged.
