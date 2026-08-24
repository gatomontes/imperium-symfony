# Handoff: Delegate Mission Step 20 complete

## Transition

The exact occupied trust Senator (`LEGATE`) independently accepts or refuses the Step 19 first-question commission. The service revalidates the identity-bound commission, Step 18 opening, unchanged hearing contract, current Garrison custody, and live Senate occupancy. Classification alone grants no disposition authority.

Acceptance consumes the commission disposition authority and opens one exact single-use trust-question authorship authority. That authority excludes dispatch. Refusal opens nothing.

No question cognition or authorship has occurred, and no dispatch, testimony, finding, Profile approval, operational authority, or execution authority exists.

## Checkpoints

- Accepted: `DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_ACCEPTED_PENDING_TRUST_QUESTION_AUTHORSHIP`
- Refused: `DELEGATE_MISSION_FIRST_QUESTION_COMMISSION_REFUSED_NO_QUESTION_AUTHORITY`

## Implementation

- `src/Imperium/Runtime/Senate/DelegateMissionFirstQuestionCommissionDispositionService.php`
- Step 20 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-first-question-commission-disposition.md`

## Next bounded transition

Delegate Mission Step 21 is authorship and sealing of the one bounded trust question. It consumes only the Step 20 authorship authority and must stop before dispatch or testimony.
