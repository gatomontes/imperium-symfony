# Handoff: Delegate Mission Step 9 complete

## Transition

Imperator decides the exact sealed Step 8 Delegate Profile-scope request after revalidating its reservation and original capability-demand lineage.

Only `AUTHORIZED` opens one exact single-use Profile-derivation authority for Conscription. The authority permits later acceptance and commissioning of derivation; it does not itself derive a Profile or authorize Laboratorium to act without the next governed handoff.

Refusal, return, alternative, clarification, and deferral dispositions are sealed and non-authorizing.

## Checkpoints

- Authorized: `DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE`
- Otherwise: `DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PROFILE_SCOPE_DISPOSITION_RECORDED`

All branches preserve the prohibition on Profile instantiation/activation, examination, approval, installation, Delegate assembly, Seat binding, deployment, operational use, resource use, external action, execution, Mission Plan amendment, follow-up commission, and continuing authority.

## Implementation

- `src/Imperium/Runtime/Imperator/DelegateMissionProfileScopeDecisionService.php`
- Step 9 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-profile-scope-decision.md`

## Next bounded transition

Delegate Mission Step 10 is Conscription's acceptance of the exact authorized Profile-derivation scope and construction of a custody-bound Laboratorium commission request. Acceptance must consume the Step 9 authority without deriving the Profile.
