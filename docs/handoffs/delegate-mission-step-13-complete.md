# Handoff: Delegate Mission Step 13 complete

## Transition

The current occupied Recruiter (`LEGATE`) independently accepts or refuses the exact returned Profile candidate for the mission-bound `DELEGATE`.

Conscription revalidates the candidate digest, accepted derivation lineage, immutable Profile scope, Persona, custody lease, and current Garrison custody. Classification grants neither acceptance nor examination authority.

Acceptance consumes the return-intake disposition authority and opens one exact single-use examination-preparation authority. It does not yet create or deliver a Senate request. Refusal consumes the intake decision and grants nothing.

## Checkpoints

- Accepted: `DELEGATE_MISSION_PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_PREPARATION`
- Refused: `DELEGATE_MISSION_PROFILE_CANDIDATE_REFUSED_NO_AUTHORITY`

No branch grants Senate intake/examination, Profile approval, activation or installation, Manifestation assembly, Seat binding, deployment, operational cognition, resource use, perimeter crossing, external action, execution, Mission Plan amendment, follow-up commission, or continuing authority.

## Implementation

- `src/Imperium/Runtime/Conscription/DelegateMissionProfileCandidateIntakeDispositionService.php`
- Step 13 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-profile-candidate-intake-disposition.md`

## Next bounded transition

Delegate Mission Step 14 is Conscription's construction of one exact examination-preparation handoff for Senate. It must consume the Step 13 authority and bind an examination-only assembly contract without assembling a Manifestation or granting Senate examination authority.
