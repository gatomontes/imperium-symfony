# Handoff: model-bound Citadel provider invocation and cognition complete

## Step 40 — provider invocation activation

The occupied Locksmith consumes the exact Step 39 activation authority and revalidates the authorized turn, runtime activation, sealed model binding, unexpired Profile access attestation, underlying provider-access assertion, exact provider/model match, target, and complete digests.

Clavium seals `CITADEL_LEGATE_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN`. The opaque credential lease is single-use, expires at the earliest governing expiry, and stores only a digest of the credential reference. The credential reference is neither disclosed nor transferred. Provider invocation is authorized but has not occurred.

## Step 41 — one bounded cognition turn

The exact target Legate consumes the single-use cognition authority and credential lease through a tool-less gateway matching the exact configured model binding. Before the call, runtime machinery durably claims the invocation as non-replayable. A crash may therefore require recovery but cannot silently trigger a second provider call. One provider call receives only the sealed activation and commission contract. Runtime machinery validates the returned disposition, output, evidence references, uncertainties, and stop-condition result, rejecting undeclared evidence.

The result seals `CITADEL_LEGATE_GOVERNED_COGNITION_TURN_COMPLETED_SEALED_NO_CONTINUING_AUTHORITY` or the corresponding `STOPPED` checkpoint. The provider was invoked and one cognition turn occurred; both authorities are consumed.

## Boundary

No credential is disclosed or transferred. No tool, memory, network resource, external data, external action, operational execution, autonomous cognition, or continuing turn is authorized. The sealed output does not authorize another thought or any downstream action.

The production adapter currently supports the configured `deepseek/deepseek-v4-flash` binding and fails closed for any model without an exact configured gateway.

## Verification

Integrated tests cover opaque lease activation, exact provider/model lineage, expiry, one-call consumption, replay safety, output-contract validation, undeclared-evidence rejection, and the terminal no-continuing-authority boundary.
