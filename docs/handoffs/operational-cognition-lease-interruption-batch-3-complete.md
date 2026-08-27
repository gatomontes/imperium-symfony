# Operational Cognition Lease Interruption Batch 3 complete

Batch 3 consumes only the exact Batch 2 Locksmith authority under the native `oca-lease` lock and
seals `imperium.operational-cognition-lease-interruption-enforcement-result/v1`. The result proves
that the exact durable operational invocation claim was denied before creation.

`OperationalCognitionInvocationClaimService` now consults the operational interruption result while
holding the same lease lock and fails with `OCA407_OPERATIONAL_LEASE_INTERRUPTED_PRE_CLAIM` before
claim validation or persistence. The admission guard requires an intact exact authority/result
chain; malformed or substituted denial evidence also fails stopped.

The result consumes only the interruption authority. It leaves the cognition authority and lease
unconsumed, mutates no request, Imperator decision, lease, or credential, creates no claim or
provider journal, performs no cognition, provider invocation, network access, external action, or
propagation, and grants no continuing or perimeter authority.

Process-level contention proves both admissible race outcomes as one exclusive winner: claim-first
leaves no enforcement result; enforcement-first leaves no claim. No partial artifacts coexist.

Only Batch 4 may next be considered: one read-only reconstruction of the complete interruption
chain and mechanical durable-claim absence. Batch 4 is not authorized by this handoff.
