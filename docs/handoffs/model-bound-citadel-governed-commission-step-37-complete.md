# Handoff: model-bound Citadel governed commission issuance complete

## Step 37

An actively occupied caller Seat may issue one exact governed commission only when its own sealed binding explicitly grants `governed_commission_issuance_authority` and names the target Officer Seat in `commissionable_seats`.

Runtime machinery revalidates the target's exact runtime activation, current Seat binding, unexpired model-access attestation, issuer occupancy, instance, target jurisdiction, and record digests. The immutable commission binds the exact task, purpose, inputs, evidence requirements, constraints, output contract, and stop conditions.

Success seals `CITADEL_OFFICER_GOVERNED_COMMISSION_ISSUED_PENDING_OFFICER_ACCEPTANCE`.

## Boundary

Issuance is not Officer acceptance and the commission is not exercisable. It creates only one single-use acceptance authority addressed to the exact target Seat. Autonomous cognition, governed cognition, provider invocation, credentials, tools, external action, execution, operational use, and continuing-turn authority remain false.

Runtime readiness does not confer commissioning authority, and active occupancy alone is insufficient. The caller must carry explicit target-specific commissioning jurisdiction.

## Verification

Dedicated tests cover exact issuance and replay, target-specific caller authorization, expired model access, complete contract enforcement, and the inert post-issuance boundary.

## Next

Step 38 is the target Officer's independent acceptance or refusal of the exact commission. Acceptance may bind the Officer to the contract, but it must not itself invoke the provider or perform cognition; bounded cognition-turn authorization remains a later, separate transition.
