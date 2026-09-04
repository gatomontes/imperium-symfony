# Canonical Native Effect Process Custody and Formal Closure Remediation — Batch 3 execution/recovery separation v1

`BATCH_3_FIRST_EXECUTION_RECONSTRUCTION_AND_FORWARD_RECOVERY_SEPARATED`
`PROVIDER_DOUBLES_ONLY`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

`NativeEffectDoubleExecutionService::execute()` is now first-execution only.
An existing receipt causes `CNE507_FIRST_EXECUTION_ALREADY_COMPLETED`; an
existing sealed response causes `CNE508_FORWARD_RECOVERY_REQUIRED`; callback
start without response remains `UNKNOWN_REPLAY_PROHIBITED`. No early branch
returns or mutates evidence before custody.

`reconstruct(receiptId)` remains read-only. `NativeEffectForwardRecoveryService`
accepts only an admitted forward-recovery claim ID and time. It has no
continuation or callback parameter. A reconciliation authority must bind exact
admission, callback-start, sealed response and deterministic receipt references,
carry the exact forward-only act, and explicitly deny provider invocation,
credential resolution, callback reinvocation and automatic retry.

Receipt binding is extracted into an admission-derived service shared by first
execution and recovery. Recovery acquires continuation scope, claim scope and
receipt store scope; it never holds a filesystem lock across a callback because
no callback exists.
