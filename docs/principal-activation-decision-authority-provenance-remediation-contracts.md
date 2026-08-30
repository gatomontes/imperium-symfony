# Principal Activation Decision Authority Provenance Remediation — Batch 1 contracts

## Result

BATCH_1_AUTHORITY_EMPTY_SCOPE_SUCCESSOR_AND_DECISION_ISSUANCE_CONTRACTS_COMPLETE

Three separately versioned contracts define the missing jurisdiction without
producing scope, a successor generation, authority, decision or activation.

1. ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract
   describes one expiring, revocable, single-use Operator Root grant for an
   exact next generation and only
   provider_executor_principal_activation_decision_authority.
2. ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract
   describes the mechanical consume-to-commit result: an immutable next
   generation held at PENDING_ACTIVATION with identity, binding and every
   existing scope field preserved.
3. ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract
   describes one later authorization bound to the effectively active successor,
   its lifecycle activation disposition, the exact attestation, admitted
   assurance, execution boundary, decision identity and activation-authority
   identity.

## Exact separation

| Contract | Producer posture | Consumer posture | Authority-empty invariant |
| --- | --- | --- | --- |
| Scope grant | Operator Root narrow-scope grant issuer | Future MasterMason successor committer | It identifies no live Operator Root, issues no grant and cannot widen the source generation |
| Scope successor | Future MasterMason successor committer | Separate lifecycle authority and later issuance authorizer | It starts pending and neither activates itself nor supersedes the source merely by existing |
| Decision-issuance authorization | Future effectively active Imperator authorizer | Future canonical decision producer | It neither produces the decision nor issues or consumes the embedded activation authority |

The scope delta contains only
provider_executor_principal_activation_decision_authority. The existing
provider-binding, outbound-email, credential, provider-execution and
corridor-disposition scope values must be preserved exactly. The new scope
cannot be inferred from any preserved value.

The successor requires generation continuity, identity preservation, binding
preservation, separate activation authority and PENDING_ACTIVATION. Its commit
does not make it effective. A distinct lifecycle transition must establish the
unique active generation and supersede the prior generation without rewriting
history.

The issuance authorization binds the effective active successor and exact
lifecycle activation disposition to the same attestation, assurance and
execution boundary required by the existing activation-decision contract. It
also pre-binds the exact decision and activation-authority identifiers. Its only
permitted transition is
PRODUCE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_AND_AUTHORITY.

## Expiry, revocation and contention

The grant and issuance authorization are single-use, expiring, revocable,
non-continuing and require one issuance and consumption winner. Batch 2
validation must refuse absent, expired, revoked, consumed, mismatched,
wrong-generation, changed-scope or competing fixtures.

Contract existence proves no instance-specific Operator Root, principal,
lifecycle state, authority, attestation, assurance or boundary.

## Preserved perimeter

Every NON_AUTHORITIES value is false. No validator, store, producer, issuer,
consumer, lifecycle transition, current-state index or reconstruction behavior
is introduced.

No scope is granted, no principal generation is created or activated, no
authority or decision is issued or consumed, and the Batch 1 combined activation
winner is unchanged. No credential or capability is handled, no provider is
invoked, no external I/O occurs, and Iron Gate and Lazaretto remain closed.

Provider Effect Principal and Binding Activation remains paused.
UNKNOWN_REPLAY_PROHIBITED remains binding.
