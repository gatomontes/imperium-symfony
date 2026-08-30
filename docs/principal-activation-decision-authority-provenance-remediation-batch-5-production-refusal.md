# Principal Activation Decision Authority Provenance Remediation — Batch 5 production readiness refusal

## Result

BATCH_5_PRODUCTION_REFUSED_SUCCESSOR_PRINCIPAL_AND_DECISION_LINEAGE_CONTRACTS_ABSENT

Batch 5 production is refused before any authority consumption or durable
runtime mutation.

## Missing successor-principal contract

ImperatorRuntimePrincipalVersionContract v2 fixes authority_scope to exactly
five fields:

- provider_binding_activation_authority;
- outbound_email_authority;
- credential_authority;
- provider_execution_authority; and
- corridor_disposition_authority.

The required
provider_executor_principal_activation_decision_authority field is absent.
The v2 contract also explicitly forbids self-widening.

Batch 1 defined an immutable scope-successor transition whose
successor_principal is only a reference. It did not define the full canonical
successor-principal record or a v3 principal contract. The Batch 2 offline test
uses the placeholder schema name imperium.imperator-runtime-principal/v3, but no
canonical contract with that schema exists.

A production service therefore cannot validate or persist the proposed
successor principal without inventing its schema, exact fields, scope order,
lifecycle meaning and secret-exclusion rules.

## Missing decision-production lineage

ProviderExecutorPrincipalActivationDecisionContract requires a complete actor,
scope, disposition, rationale, limitations, validity and activation-authority
record.

ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract binds
the successor reference, activation disposition, attestation, assurance,
execution boundary, decision identifier and activation-authority identifier.
It does not bind the complete decision actor or the exact decision scope,
disposition, rationale, limitations and validity values.

The actor contract specifically requires binding_id. The authorization's
issuer_principal reference contains only id, digest, schema and generation.
Inventing the missing binding or decision fields inside a producer would make
the producer an unauthorized decision maker.

## Fail-closed consequence

No scope grant was consumed. No successor record or lifecycle disposition was
committed. No decision-issuance authorization was consumed. No activation
decision or activation authority was created.

No credential or capability was handled, no provider was invoked, no external
I/O occurred, and Iron Gate and Lazaretto remain closed. Provider Effect
Principal and Binding Activation remains paused.
