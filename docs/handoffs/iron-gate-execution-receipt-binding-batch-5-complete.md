# Iron Gate Execution Authority and Receipt Binding Batch 5 complete

## Result

Batch 5 implements the native Curia request, Imperator authorize/refuse decision and separate
Imperator authorization-issuance records. The canonical mechanics and boundary are documented in
`docs/iron-gate-outbound-email-native-records.md`.

The exact lineage is durable, content-digested and immutable. Request replay is identity-scoped,
decision is one-winner per request, and issuance consumes one expiring single-use issuance
authority. The issued authorization is embedded in the same immutable issuance aggregate, so the
transition cannot leave a partial issuance/authorization pair. Refusal, competing replay, source
tamper and secret exclusion fail closed in the Batch 5 proof.

No email command was migrated. No execution claim or credential capability was consumed. No
provider header, network call, raw receipt or Lazaretto admission was created. Iron Gate and sortie
behavior are unchanged.

## Next separately bounded batch

Only Batch 6 may next be considered: define and prove the durable execution claim that consumes one
exact issued authorization before external I/O. It may not resolve a credential, migrate
`AgentMailEmailSendCommand`, invoke AgentMail, create or admit a receipt, alter Lazaretto, or touch
sortie. Batch 6 is not authorized by this handoff and requires an explicit continuation instruction.

Credential-platform, revocation, propagation, telemetry, reassessment, containment and incident
boundaries remain closed. No Delegate Mission step or terminal campaign is reopened.

