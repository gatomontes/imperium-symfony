# Canonical Native Effect Corridor Activation — Batch 2 inert admission v1

`BATCH_2_INERT_EXACT_NATIVE_ROOT_JOIN_COMPLETE_PROVIDER_CALLBACK_IMPOSSIBLE`

`NativeEffectAdmissionValidator` performs a read-only join from an exact sealed
effect-authority shape to the current native transition, receipt, successor,
selected v3 admission, executor principal, execution boundary and immutable
provider descriptor. It derives the effect replay identity from those stored
facts and the exact request/provider scope.

The returned view explicitly records that authority, credential capability,
provider callback, provider invocation, external I/O and retry remain false.
The validator imports no credential broker, capability, AgentMail adapter,
transport, Iron Gate or Lazaretto class and writes no state.

Batch 3 is not authorized by this document alone.
