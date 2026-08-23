# Profile-bound model access attestation

After Conscription seals an authorized model binding into a new immutable Profile version, Clavium may consume the corresponding Mission-Authorization-derived single-use preparation authority to attest access for that exact Profile digest and provider/model/version.

The attestation cites the sealed binding and one canonical Clavium provider-access assertion by identifier and digest. It preserves Locksmith attribution, observation time, expiry, and restrictions. It does not reproduce the opaque credential reference or any secret-bearing, credential-locating, or recoverable credential metadata.

Permitted results are:

- `ACCESS_AVAILABLE`
- `ACCESS_UNAVAILABLE`
- `ACCESS_INDETERMINATE`

Canonical `ACCESS_RESTRICTED` and `ACCESS_UNVERIFIED` provider assertions map to `ACCESS_INDETERMINATE` at the Profile boundary. An unavailable or indeterminate result does not activate a fallback; it returns to Curia under the approved dossier terms.

A successful attestation grants no Profile approval, current/active designation, credential release or use, provider invocation, Manifestation assembly, deployment, or execution authority. The normal checkpoint is `PROFILE_MODEL_ACCESS_ATTESTED_PENDING_APPROVAL_AND_ACTIVATION`.
