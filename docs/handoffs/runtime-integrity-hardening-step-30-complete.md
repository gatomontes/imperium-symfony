# Runtime Integrity Hardening Step 30 Complete

## Scope

The provider-invocation claim and governed turn-recovery boundary now has explicit canonical tamper coverage from activation claim through recovered turn persistence.

## Claim boundary

- Activation and competing-claim reads delegate to `RecordReferenceValidator`.
- Activation integrity remains a prerequisite for consuming the lease and turn authority.
- A stored claim must remain canonically intact before its replay fingerprint may authorize exact replay.
- A tampered claim fails as `CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT`; it cannot become a second provider invocation.
- The atomic single-winner claim and immutable claim record remain unchanged.

## Recovery boundary

- Recovery authorization and evidence continue through digest-validating immutable and mutable stores.
- A tampered authorization fails as `CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID` before authority consumption or transition.
- Recovery still processes only a sealed response identity and never reinvokes the provider.
- A different authorization cannot claim replay of an already recovered turn.

## Verification

- `ProviderInvocationClaimServiceTest` now covers tampered stored-claim rejection.
- `DelegateMissionTurnRecoveryServiceTest` now covers tampered authorization rejection.
- `ProviderInvocationClaimReferenceMigrationTest` guards canonical validation at the claim boundary.
- Existing contention, replay-fingerprint, malformed-payload, expiry, and no-reinvocation tests remain in force.

The full PHPUnit suite remains the local/CI PHP 8.4 gate.
