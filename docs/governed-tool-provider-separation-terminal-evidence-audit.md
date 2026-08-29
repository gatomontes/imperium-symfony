# Governed Tool and Provider Separation terminal evidence audit

## Disposition

`SEPARATION_CANONICAL_EXECUTION_NOT_AUTHORIZED`

The nine-batch campaign successfully separates governed tool identity, external-effect authority,
provider implementation, credential eligibility, request encoding, raw evidence, provider-specific
decoding, normalized result admission and read-only reconstruction. The former live command can no
longer manufacture the identities required to send email.

Provider Execution Assurance may **not** resume. The separated route is deliberately inactive and
contains no lawful transition that can activate its provider binding or transfer an already-issued
opaque credential capability into a later command process.

## Terminal proof matrix

| Threat | Evidence | Disposition |
| --- | --- | --- |
| Provider, adapter or decoder substitution | Exact binding references, digests and decoder support checks fail before normalization. | `REFUSED_PRE_IO` |
| Credential/provider mismatch | Clavium eligibility binds provider, family, reference digest, target, operation, expiry and single use. | `REFUSED_PRE_RESOLUTION` |
| Deterministic identity collision | Identical immutable inputs converge; changed raw content produces a different evidence and result identity. | `CONVERGENT_NOT_COLLIDING` |
| Record or byte tamper | Every binding, raw-evidence, normalized-result and admission digest is revalidated. | `REFUSED` |
| Unknown provider outcome | `UNKNOWN_REPLAY_PROHIBITED` grants neither automatic replay nor provider reinvocation. | `FORWARD_RECOVERY_ONLY` |
| Secret disclosure | Persisted eligibility, encoding evidence, raw evidence, normalized results and reconstruction contain no credential secret. | `EXCLUDED` |
| Provider replaceability | A sterile second decoder normalizes and admits provider-neutral `email.send` evidence without changing tool authority or Lazaretto policy. | `PROVED_INACTIVE` |
| Self-authorizing command | The retired command has no authority inputs, credential broker, provider transport or I/O path and always refuses. | `REMOVED` |

## Unclosed execution facts

Two facts remain absent, not defective implementations hidden behind prose:

1. a separately issued, single-use authority to activate one exact provider binding for one exact
   execution; and
2. a cross-process opaque capability custodian that can deliver the exact already-issued capability
   without persisting secret material, reconstructing authority or issuing a replacement.

These facts require a separately selected campaign. They may not be smuggled into a command,
provider registry, adapter, credential broker or configuration shortcut.

## Preserved perimeter

No provider was invoked, no credential was issued or resolved, and no external I/O occurred during
the audit. Iron Gate, Lazaretto, inbound webhook, sortie, credential-platform, revocation,
propagation, telemetry, reassessment, containment and incident behavior remain unchanged.
