# Deterministic execution lane

Mechanical external actions do not create cognitive agency.

When Imperium has already determined the exact operation, destination and payload, Iron Gate dispatches a deterministic boundary execution. Infrastructure consumes an opaque, bounded credential capability, the deterministic transport performs the exact external action, and the provider's raw receipt returns through Lazaretto.

The lane must preserve these invariants:

- `OutboundExecutionMode::Deterministic`; no sortie manifest, manifestation or external cognition.
- The executable payload bytes must match the authorized payload digest.
- The execution binds to one exact destination and an exact operation.
- Credential secrets remain in infrastructure custody; cognitive code receives neither secret material nor authenticated transport context.
- Credential capabilities are scoped, expiring and consumable; replay is refused.
- The provider receipt is treated as a raw external payload and admitted by Lazaretto with runtime-owned provenance.

`email.send` is the canonical example: if recipients, content and attachments are already prepared and authorized, sending is mechanical. A sortie is only warranted when external cognition is itself required.
