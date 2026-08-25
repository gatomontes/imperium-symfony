# Runtime-integrity hardening Step 13 complete

The three Delegate Senate question-authorship implementations now share one jurisdiction-parameterized engine.

- Existing Trust, Security, and Usability service interfaces remain stable.
- Each wrapper selects one fixed jurisdiction; callers cannot silently substitute it.
- Jurisdiction-specific Senator identity, checkpoints, sequence numbers, prior-testimony references, authority purposes, statuses, and error codes remain intact.
- Cognition still receives the exact jurisdiction and unchanged commission/opening evidence.
- An unknown jurisdiction fails before filesystem reads or cognition.
- The existing full Delegate flow continues to exercise all three wrappers and their distinct records.

This consolidates implementation mechanics only. Trust, Security, and Usability remain separate Senate jurisdictions with separate Officers, authorities, questions, and evidence.
