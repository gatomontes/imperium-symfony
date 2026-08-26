# Credential Boundary Batch 13H — Security Baseline Testimony Complete

Batch 13H consumes only the exact testimony authority opened by the sealed Security question.

The Persona-confirmation resolver now supports `testimony-security`. It rereads the exact question, deposition, sterile witness, and three ordered prior testimony turns. The input digest binds the question payload, deposition augmented with all three prior turns, and exact witness. Finding-stage and cross-cluster substitution remain unsupported.

The Security completion service requires the unconsumed single-use authority, invokes the shared claim-bound broker path, seals the exact answer, and records the authority as consumed. The resulting immutable fourth turn stops at `SECURITY_BASELINE_TESTIMONY_SEALED_PENDING_FINDING_AUTHORITY_OPENING`.

No Senator finding authority is opened. No finding, disposition, admission, spawning, Seat-binding, or execution authority is created. No direct agent is removed; inventory remains 15 and the system-wide credential gate remains open.

Next: Batch 13I migrates the four jurisdictional question-authoring agents to exact claim-bound authorities and removes their direct definitions, reducing inventory from 15 to 11.
