# Canonical mission schema and authority provenance model

The executable schema is `MissionDossier::REQUIRED_FIELDS` under
`imperium.operator.mission-dossier/v1`. Canonical JSON recursively sorts object keys and preserves
list order. The dossier identity is `mission-dossier-` plus the SHA-256 digest of that canonical
form. An exact forty-hex commit SHA is mandatory; a branch name or moving reference is invalid.

## Authority provenance

```text
explicit Operator mission order
  -> OperatorMissionBoundary
    -> signed exact MissionCapability (mission, dossier, action, actor, target, time, nonce)
      -> MissionCapabilityConsumer::consume (single use)
        -> authorized domain transition
          -> mission-bound evidence and receipt
```

The consumer interface exposes no issue, renew, widen, or replace operation. Capability
signatures cover all authority bindings. Consumption checks signature, mission and dossier
identity, action, actor, target, validity interval, revocation, and prior consumption before the
authorized service proceeds.

For native-effect reconciliation, mission consumption now precedes source resolution and target
construction. The mission ID and dossier identity propagate through the issuance decision,
issuance authority, issuance custody, reconciliation authority, issuance record, reconciliation
custody, authority consumption, and forward-recovery claim. The former unauthenticated corridor
factory for the authorization service is absent.

Native-principal/root-act lineage remains readable provenance. It is retained for currentness and
audit joins but cannot satisfy the mission-capability parameter or signature check.

