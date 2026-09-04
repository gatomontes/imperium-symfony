# Canonical native-effect reconciliation authority provenance remediation — Batch 3

Status: `BATCH_3_COMPLETE_TYPED_ADMISSION_AND_CORRIDOR_INTEGRATION`

The caller-authored reconciliation-authority ingress is removed. The only public
claim-admission signature is now:

```php
admit(NativeEffectReconciliationAuthorityCapability $capability, int $at): array
```

The admission service delegates to the atomic derivation established in Batch 2.
An array carrying copied schema, issuer, holder, act and a fresh valid digest is a
PHP type error before domain admission. The four formerly accepted test fixture
families now obtain immutable authority/issuance evidence from the canonical
issuer, resolve process-local custody, and pass that exact capability.

`CanonicalNativeEffectCorridor` now exposes the reconciliation issuer, resolver,
typed claim admission and forward-recovery services. The resolver instance must
be passed into claim admission so exact-object custody remains in one registry;
creating a fresh resolver does not recognize a capability minted elsewhere.

Forward completion validates the v2 claim, its embedded authority-consumption,
the immutable authority and issuance references, and the current Root/native
source chain. It then consumes the exact claim/digest for the deterministic
receipt through the canonical authority-consumption store before binding. An
identical retry converges on that consumption and receipt; a different receipt
consumer conflicts. Receipt reconstruction remains read-only and is not a new
authorization.

Lock order remains admission-continuation, reconciliation authority, exact claim,
then receipt/immutable storage. The existing provider-double fixture is used only
to create sealed local response evidence. Recovery exposes no callback, payload,
idempotency key, provider, credential or network input.

Batch 4 owns broad adversarial, application-container, concurrency, fresh-process,
expiry/revocation/substitution and interruption-cut proof. Batch 5 remains a
separate clean-main terminal audit. Batch 7 remains suspended.

