# Clavium provider-access assertions

Clavium owns credential custody and access policy. Locksmith is the Officer attributable for provider-access assertions. The underlying check is deterministic machinery and consumes no LLM cognition.

An assertion records only whether one opaque `clavium://` credential reference appears usable for one provider and exact scope under one observation method. It preserves the occupied Locksmith identity, observation evidence, status, restrictions, validity window, expiry, and revalidation triggers without returning or transferring secret material.

Permitted statuses are `ACCESS_AVAILABLE`, `ACCESS_UNAVAILABLE`, `ACCESS_UNVERIFIED`, and `ACCESS_RESTRICTED`. Availability is not credential-use authority, provider-invocation authority, model admissibility, model eligibility, model selection, assignment, deployment, or execution authority.

The terminal boundary is `CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY` as represented by a sealed assertion whose every use-bearing authority is false. Oracle may cite the artifact as accessibility evidence; it may not exercise the credential.
