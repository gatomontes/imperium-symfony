# Handoff: model-bound Citadel cognition-result review complete

## Step 43

The original commissioner consumes the exact Step 42 single-use review-disposition authority and independently records `ACCEPTED` or `REJECTED` with a required rationale. Runtime machinery revalidates the sealed delivery, unchanged result, exact recipient binding and digest, current sole occupancy, authority identity, instance, Manifestation, generation, and all terminal no-authority boundaries.

Acceptance seals `CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY`. It means only that the result satisfies the reviewed commission; it is not operational adoption or authorization to act.

Rejection seals `CITADEL_LEGATE_COGNITION_RESULT_REJECTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY`. It does not silently retry cognition or authorize revision. Conflicting replay is prohibited on both branches.

## Terminal boundary

The commission is closed. No follow-up commission, cognition, provider invocation, credential, operational use, tool, external action, execution, or continuing-turn authority survives. Any further cognition requires a new exact governed commission. Any use of an accepted result belongs to a separate operational-adoption and action-authorization lifecycle.

## Verification

Dedicated tests cover exact acceptance and replay, terminal rejection, wrong-authority rejection, conflicting-disposition rejection, immutable result preservation, and the terminal no-downstream-authority boundary.
