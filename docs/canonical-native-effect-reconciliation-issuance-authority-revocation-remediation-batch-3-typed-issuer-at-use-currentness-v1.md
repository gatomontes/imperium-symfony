# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 3

`BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS`
`UNGOVERNED_PUBLIC_ISSUANCE_REMOVED`
`NO_PROVIDER_NO_CREDENTIAL_NO_EXTERNAL_IO`

## Public issuer enforcement

The public issuer now accepts only
`NativeEffectReconciliationIssuanceCapability` plus the present time. It has no
admission-ID/timestamp write signature. The capability must be recognized by
the exact `NativeEffectReconciliationIssuanceAuthorityResolver` that delivered
it. Direct construction of the service therefore supplies no authority.

`CanonicalNativeEffectCorridor` exposes the rooted authorization producer and
issuance resolver separately, and requires that resolver when constructing the
issuer. The support worker and every prior test fixture caller now traverse the
same authorization, custody and consumption path.

## Present-tense currentness

The issuance resolver repeats the complete admission, callback, response,
committed native transition, native principal, source Imperator principal and
Operator Root resolution inside the semantic-target publication exclusion,
immediately before durable issuance-authority consumption.

The authority-to-claim derivation repeats the same current source resolution
inside the reconciliation-authority exclusion, immediately before exact
capability consumption and immutable claim publication. A later forward-use
refusal is therefore defense in depth rather than the first revocation check.

At-use refusal vocabulary is exact:

| Change | Refusal |
| --- | --- |
| current untimestamped Operator Root revocation | `REFUSED_OPERATOR_ROOT_REVOKED` |
| native-principal revocation | `REFUSED_NATIVE_PRINCIPAL_REVOKED` |
| source generation advance or `SUPERSEDE` | `REFUSED_SOURCE_SUPERSEDED` |
| `SUSPEND` | `REFUSED_SOURCE_SUSPENDED` |
| `REVOKE` | `REFUSED_SOURCE_REVOKED` |
| `EXPIRE` | `REFUSED_SOURCE_EXPIRED` |
| `RETIRE` | `REFUSED_SOURCE_RETIRED` |
| v3 lifecycle introduced after resolution | `REFUSED_SOURCE_MIGRATION_REQUIRED` |

RR02, RR05 and RR11 remain expiry-preservation cases. Both issuance and
reconciliation capabilities reject at `at >= expiresAt`; no new historical
Root-time claim is made.

## Boundary

All operations remain cooperative single-host filesystem operations. No
credential, provider, transport, environment-secret, HTTP/network, mission,
email, Iron Gate, Lazaretto or Batch 7 behavior is reachable. Multi-host and
hostile-direct-writer guarantees remain unclaimed.

