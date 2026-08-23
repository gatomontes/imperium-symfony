# Handoff: model-bound Profile Imperator approval complete

## Repository checkpoint

- Starting branch: `main`
- Implemented transition: model-bound step 31 only
- Approving checkpoint: `IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION`
- Non-approving checkpoint: `NON_APPROVING_IMPERATOR_PROFILE_DISPOSITION_RECORDED`

## Step 31 contract

`ModelBoundProfileApprovalDecisionService` consumes one exact sealed model-bound Senate disposition and independently revalidates its digest, exact reconciliation, subject Profile, and unchanged on-disk Trust, Security, and Usability findings.

Only an explicit Imperator `APPROVED` decision against an exact Senate `APPROVED` disposition opens one single-use `REQUEST_ONE_EXACT_OPERATIONAL_PROFILE_QUALIFICATION` authority addressed to `conscription.recruiter`. The decision does not qualify or install the Profile and grants no operational authority.

`REFUSED`, `RETURNED_FOR_REVISION`, `ALTERNATIVE_PROPOSED`, `CLARIFICATION_REQUIRED`, and `DEFERRED` are sealed at `NON_APPROVING_IMPERATOR_PROFILE_DISPOSITION_RECORDED` with no qualification-request authority. A mandatory Security block mechanically prevents Imperator approval even if a malformed upstream artifact claims Senate `APPROVED`.

Every branch explicitly leaves operational qualification, Profile installation and activation, Manifestation assembly, Seat binding, custody transfer, tools, credentials, provider invocation, external action, deployment, and execution unauthorized.

## Verification boundary

Dedicated tests cover exact replay and conflict behavior, the approving route, all five non-approving branches, rejection of approval after a non-approved Senate disposition, the mandatory Security block, and changed on-disk finding rejection.

The current container has no PHP executable, so PHPUnit could not be run locally. Run the full suite in CI or a PHP-enabled local environment.

## Next transition

Step 32 is Conscription operational qualification. Do not infer qualification from the bounded request authority. Stop before installation, Manifestation assembly, Seat binding, deployment, custody transfer, tools, credentials, external action, or execution unless Step 32 is separately authorized.
