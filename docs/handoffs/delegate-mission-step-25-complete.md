# Handoff: Delegate Mission Step 25 complete

The exact current occupied Lord Speaker (`LEGATE`) consumes the Step 24 single-use authority and issues one bounded security-question commission to the exact current occupied security Senator (`LEGATE`). Classification alone grants neither issuance nor acceptance authority.

The commission preserves the unchanged hearing subject and complete identity, custody, examination, trust-question, and sealed trust-testimony lineage. The security Senator may only accept or refuse through a separate single-use disposition authority.

Checkpoint: `DELEGATE_MISSION_SECURITY_QUESTION_COMMISSION_ISSUED_PENDING_SECURITY_SENATOR_ACCEPTANCE`

No security question has been accepted, authored, or dispatched. No further testimony, finding, deliberation, Profile approval, operational authority, or execution authority exists.

Implementation:

- `src/Imperium/Runtime/Senate/DelegateMissionSecurityQuestionCommissionIssuanceService.php`
- Step 25 coverage in `tests/Imperium/Runtime/DelegateMissionGuildhallResolutionFlowTest.php`
- `contracts/delegate-mission-security-question-commission.md`

Operator verification through Step 18 was green: 342 tests and 4,710 assertions on PHP 8.4.14. Steps 19–25 await the next local run.

Next: Step 26 is the exact security Senator's acceptance or refusal of the commission. Acceptance may open only one bounded security-question authorship authority and must stop before cognition or authorship.
