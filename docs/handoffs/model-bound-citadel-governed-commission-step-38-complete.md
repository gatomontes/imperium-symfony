# Handoff: model-bound Citadel governed commission disposition complete

## Step 38

The exact target Officer independently accepts or refuses the exact Step 37 commission. Runtime machinery revalidates the immutable commission, single-use acceptance authority, runtime activation, exact target binding, current sole occupancy, unexpired model-access attestation, instance, Manifestation, generation, contract, and record digests.

Acceptance seals `CITADEL_OFFICER_GOVERNED_COMMISSION_ACCEPTED_PENDING_COGNITION_TURN_AUTHORIZATION`. The Officer is bound to the exact contract, but the commission remains non-exercisable until a later bounded cognition-turn authorization.

Refusal seals `CITADEL_OFFICER_GOVERNED_COMMISSION_REFUSED_NO_AUTHORITY`. Both branches consume the exact acceptance authority and prohibit replay with a conflicting disposition or rationale.

## Boundary

Acceptance is not cognition authorization. Autonomous cognition, governed cognition, provider invocation, credentials, tools, external action, operational use, execution, and continuing-turn authority remain false on both branches.

## Verification

Dedicated tests cover exact acceptance and replay, sealed refusal, wrong-target rejection, expired-access rejection, authority consumption, and the inert post-decision boundary.

## Next

Step 39 is a separate authorization for one exact bounded cognition turn against the accepted commission. It must distinguish authorization from provider invocation and preserve refusal, expiry, and revocation as non-authorizing branches.
