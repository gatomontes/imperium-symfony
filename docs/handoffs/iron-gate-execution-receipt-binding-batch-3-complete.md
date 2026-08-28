# Iron Gate Execution Authority and Receipt Binding Batch 3 complete

## Result

Batch 3 defines the declarative
`imperium.la-cortine.deterministic-outbound-email-authorization/v1` contract without identifying an
issuer or issuing, consuming or exercising authority. It binds the required source decision,
issuer, holder, exact email scope, expiry, single-use state, provider idempotency identity and
request fingerprint while excluding credential secret material.

Official AgentMail documentation now proves idempotent direct-send semantics. The canonical current
assessment is `docs/iron-gate-agentmail-idempotent-send-assessment.md`: same-key retries return the
original result without another email, changed requests conflict, empty keys fail, and keys expire
after 24 hours.

This proves only the provider prerequisite. The competent native issuer, stable local key binding,
durable pre-I/O winner and consumption, provider journal, transport adoption, durable receipt and
reconstruction remain absent. The exact consumer posture is
`BLOCKED_NATIVE_ISSUER_AND_DURABLE_CLAIM`. No issuer or consumer was migrated and runtime behavior
is unchanged.

## Next separately bounded batch

Only Batch 4 may next be considered: identify and define the competent native decision route and
declarative issuer contract for this exact outbound-email authorization. It may not issue an
authorization, migrate the command or transport, create a claim or journal, or perform external
I/O. Batch 4 is not authorized by this handoff and requires an explicit continuation instruction.

Iron Gate execution, Lazaretto persistence, sortie, credential-platform, revocation, propagation,
telemetry, reassessment, containment and incident boundaries remain closed. No Delegate Mission
step or terminal campaign is reopened.
