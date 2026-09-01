# Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 1

## Result

`BATCH_1_AUTHORITY_EMPTY_EVIDENCE_ORIGIN_AND_EXECUTION_PROVENANCE_CONTRACTS_COMPLETE`

Batch 1 defines two separately versioned, sealed and authority-empty contracts
with one pure fail-closed validator. It creates no origin or provenance record
and implements no producer, executor, store or closure consumer.

## Evidence-origin contract

`imperium.imperator.atomic-transition-evidence-origin/v1` binds:

- experiment, disposable-mission and replay/contention identity;
- the exact disposable-mission authorization and authorized case profile;
- repository, 40-character source commit, source-tree digest and dirty-tree
  refusal;
- build, artifact, dependency-lock, runtime and build-command identity;
- executor principal, implementation digest, entry point and environment class;
- mission dossier, fixture, recovery-plan, mutation, expected-result and case-set
  roots;
- authoritative evidence root, fixture custodian and origin producer;
- issued, not-before and expiry times plus prior-origin disposition;
- the exact limitations and sanitized evidence-package identity; and
- single-use, authority-empty, not-executed and no-receipt state.

The record digest establishes canonical integrity only. Contract existence and
a caller-computed seal do not authenticate the origin producer.

## Execution-provenance contract

`imperium.imperator.atomic-transition-execution-provenance/v1` references the
exact origin and reproduces every result-affecting mission, source, build,
executor, input-root, custody, freshness, limitation and package binding.

The validator requires exact equality with the origin. The contract explicitly
states that the trusted executor is not implemented, no execution occurred, no
caller result was accepted, no result or operational receipt was produced, and
neither the dependency graph nor complete-chain exclusion proof was derived.

## Pure validation

`AtomicTransitionExecutionProvenanceContractValidator` fails closed on:

- field order, schema, seal or digest failure;
- malformed identifiers, references, source commit or digests;
- dirty-tree tolerance, altered limitations or raw-private-evidence inclusion;
- malformed or reversed freshness windows;
- mission, source, build, executor, input, custody, freshness or package
  substitution between the origin and provenance records;
- any false implementation, execution, result, graph, exclusion or receipt
  claim; and
- credential values/references, process-local capability material,
  environment values, callback/object identity, objects, resources or callables.

Validation is syntactic and relational. It does not resolve an authorization,
attest a build, inspect a dependency graph, authenticate a producer or execute
a case.

## Acyclic order

1. A future disposable-mission authorization exists independently.
2. The authority-empty evidence origin references that authorization and all
   predecessor inputs.
3. The authority-empty execution provenance references the sealed origin and
   reproduces its result-affecting bindings.
4. A future Batch 2 trusted executor may consume only a valid origin and emit
   provenance-bound results; it may not accept caller results.

No future execution receipt or result digest appears in either Batch 1 record.

## Closed perimeter

Batch 1 performs no execution and writes no record. It implements no trusted
executor, producer, persistence, dependency traversal, complete-chain scanner,
operational receipt, reconstruction or closure consumer. It does not repair,
disable or subordinate the historical audit and does not remove the closure
qualification.

It mutates no runtime state, persists no live journal, acquires no live lock,
issues or consumes no authority, admits no execution, adopts no successor and
changes no binding state. It handles no live credential or capability, invokes
no provider, performs no external I/O and opens neither Iron Gate nor Lazaretto.

The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`.
