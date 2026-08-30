# Principal Activation Decision Authority Provenance Remediation Batch 5A contracts

## Result

BATCH_5A_AUTHORITY_EMPTY_SUCCESSOR_PRINCIPAL_AND_DECISION_ENVELOPE_CONTRACTS_COMPLETE

Batch 5A defines the previously absent canonical v3 Imperator principal schema
and the complete decision-production envelope. The v3 schema preserves the v2
identity, binding, five existing authority-scope fields, lifecycle separation
and secret exclusion, and adds only
`provider_executor_principal_activation_decision_authority`.

The production envelope carries the complete actor, scope, disposition,
rationale, limitations, validity and single-use activation-authority shapes
required by the activation-decision contract. It also names the fields that
must be bound to the existing issuance authorization. A later batch must
validate those bindings before production is considered.

## Authority-empty posture

Contract existence creates no principal, scope, lifecycle disposition, decision, activation authority or consumption.
It grants no continuing authority and performs no provider-boundary action.

No credential or process-local capability is handled. No provider is invoked,
no external I/O or live command is performed, no retry is authorized, and Iron
Gate and Lazaretto remain closed.

## Next gate

Only Batch 5B validation may next be considered. It may define pure validators
and segregated immutable caller-supplied offline fixture stores for these two
contracts. It may not create a v3 principal, consume the Operator Root scope
grant, consume decision-issuance authorization, produce a decision or
activation authority, or activate a principal or binding.
