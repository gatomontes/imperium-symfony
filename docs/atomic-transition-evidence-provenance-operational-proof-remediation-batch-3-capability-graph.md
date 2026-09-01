# Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 3

## Result

`BATCH_3_ACTUAL_RECURSIVE_EXECUTOR_DEPENDENCY_CAPABILITY_GRAPH_DERIVED`

Batch 3 replaces the former hard-coded evaluator declaration with a graph
derived from the actual resolved Batch 2 executor object graph and exact loaded
implementation sources.

## Derivation boundary

`AtomicTransitionExecutorDependencyCapabilityGraphDeriver`:

1. validates the exact Batch 1 origin and execution provenance;
2. recursively walks every initialized object dependency reachable from the
   actual `AtomicTransitionTrustedCaseExecutionCorridor` instance;
3. rejects any class outside the exact reviewed executor policy;
4. rejects non-readonly state unless every instance property is readonly;
5. hashes each loaded implementation source and rejects a root digest that does
   not equal the provenance-bound executor implementation digest;
6. scans every loaded implementation for network, filesystem-write, process,
   environment, credential-resolution, provider-invocation and runtime-state
   mutation capabilities; and
7. orders the actual nodes and dependencies canonically before deriving the
   graph digest.

The allowlist is an admission policy, not the graph. Node membership and edges
come from reflection over the resolved object instances. A newly injected or
substituted dependency therefore appears in traversal and fails closed rather
than disappearing behind a declared class list.

## Derived graph contract

`imperium.imperator.atomic-transition-executor-dependency-capability-graph/v1`
binds the exact origin/provenance, source commit/tree, build artifact/dependency
lock, root executor class and implementation digest, canonical nodes, graph
digest and explicit empty refusal sets.

Every node records:

- exact runtime class and loaded-source digest;
- final and readonly-or-stateless posture;
- actual resolved object dependencies; and
- the seven derived effect-capability flags.

Successful derivation requires all capability flags false and the unknown,
substituted, mutable and effect-capable dependency sets empty.

## Exact current graph

The current resolved graph contains the trusted corridor, provenance validator,
deterministic case executor, typed-case validator, read-only reconstructor,
recovery-plan validator, disposable classifier and transaction-contract
validator. Repeated instances of the same exact implementation collapse to one
canonical class node while their dependency edges remain represented.

Static helpers are not object nodes. Their use remains visible to loaded-source
capability inspection, and complete-chain content exclusion remains reserved
for Batch 4.

## Closed perimeter

Derivation reads loaded source only to hash and classify it. It writes no
runtime state, persists no journal, acquires no live lock, issues or consumes no
authority, executes no case or mission, admits no transition, and creates no
operational receipt.

Batch 3 claims no complete-chain secret or process-local capability exclusion,
runs no disposable real mission, repairs no historical audit and removes no
closure qualification. It handles no live credential or capability, invokes no
provider, performs no external I/O and opens neither Iron Gate nor Lazaretto.

The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`.
