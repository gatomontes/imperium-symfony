# Runtime-integrity hardening Step 16 complete

Trust, Security, and Usability question-dispatch authorization now share `DelegateMissionQuestionDispatchAuthorizationEngine`.

- Existing root-path constructors and public method signatures remain stable.
- Lord Speaker and Bailiff authority checks remain exact.
- Jurisdiction-specific error families, sequence numbers, statuses, Senator evidence, and testimony lineage remain unchanged.
- Authorization creates one bounded Bailiff dispatch authority; refusal creates none.
- Unknown jurisdictions fail before filesystem access.

Only identical decision mechanics were consolidated. No jurisdiction or governance authority was merged.
