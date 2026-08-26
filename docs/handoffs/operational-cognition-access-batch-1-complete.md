# Operational Cognition Access Batch 1 complete

## Completed boundary

The Curia request and independent Imperator provider-resource decision are implemented. This is Batch 1 of the separately bounded Operational Cognition Access lifecycle. It creates neither Delegate Mission Step 70 nor Runtime Integrity Hardening Step 36.

Curia now seals one exact, expiring cognition request bound to the existing bounded-execution authorization, Manifestation, Seat/binding, custody lineage, input digest, Profile/model requirements, iteration `1`, and stop conditions. That request grants only single-use cognition authority. It grants no credential, network, provider-invocation, or continuation authority.

Imperator independently authorizes or refuses the exact provider, model, normalized configuration, and integer token/cost ceiling. Authorization opens only one expiring, single-use authority addressed to Clavium for issuance of the exact lease. Refusal opens no authority. No lease or credential exists at this checkpoint.

Both records are immutable, digest-bound, exact-replay safe, and reject divergent reuse of the same source.

## Verification

The focused test is:

```bash
php bin/phpunit tests/Imperium/Runtime/OperationalCognitionAccessRequestDecisionTest.php
```

The user runs local PHP verification. PHP is unavailable in the implementation workspace, so no local-runtime result is claimed here.

## Next batch

Implement **Operational Cognition Access Batch 2: opaque Clavium lease**.

Clavium must validate the exact, intact, unexpired `AUTHORIZED` decision and its bound cognition request, then issue one opaque, expiring, single-use lease. The lease must bind the request and decision identities/digests, Manifestation, provider/model/configuration, input digest, iteration, issuer, issue time, and expiry. It must contain no credential reference, credential material, secret-bearing endpoint, or transferable network authority.

Batch 2 must not resolve credentials, create or consume the durable invocation claim, construct a provider adapter, or perform provider I/O.
