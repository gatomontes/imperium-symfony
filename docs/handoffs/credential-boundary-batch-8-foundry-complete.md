# Credential Boundary Batch 8: Foundry Complete

Batch 8 migrates only Foundry's four model-backed stages: initial Persona specification, specification revision, ordinary completeness review, and adversarial review.

The Foundry resolver rereads each exact native authority and its immutable chain, derives the ordered provider-input digest, and exposes a stage-specific Seat and purpose. A gateway proceeds only when one separately authorized governance request/decision/lease/claim chain matches that authority, stage, Seat, purpose, and digest. The generic governance broker validates the durable claim before issuing an opaque credential capability. The invoker reserves the provider journal before credential resolution, starts it immediately before external I/O, seals the response identity, and leaves unknown outcomes non-replayable.

Removed direct definitions: `artificer_specification` and `adversarial_reviewer`. Removed direct agent injection from all four Foundry gateways. The executable inventory falls from 32 to 30 agents. No other cluster moved and the system-wide gate remains open.

Batch 9 migrates resident-requirements cognition only: `sanctographer` and `chancellor`. Reuse the common governance broker/invoker mechanics, add native Authorship authority resolution, preserve both gateway contracts, prove cross-Seat and cross-commission refusal plus at-most-once behavior, then reduce the inventory from 30 to 28.
