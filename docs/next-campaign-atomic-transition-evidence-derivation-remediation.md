# Next campaign: Atomic Transition Evidence Derivation Remediation

## Selection

Atomic Transition Evidence Derivation Remediation is separately selected after
the Blackquill review qualified the closed Provider Binding Successor Atomic
Live Transition campaign as
`CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT`.

The prior campaign remains closed at
`PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION_CAMPAIGN_COMPLETE_PRE_EXECUTION_ONLY`.
Its contracts, validators, classifiers and read-only reconstruction remain
canonical where independently proved. Its Batch 6 adversarial disposition and
Batch 7 terminal evidence-completeness claim are not sufficient evidence for a
future campaign until this remediation closes.

Only Atomic Transition Evidence Derivation Remediation Preparation Batch 0 is
authorized.

## Defect statement

The current adversarial audit accepts caller-supplied boolean proof claims. It
can return `PASSED` from empty evidence while claiming replay, contention and
partial-write attacks were refused. Its false action flags are constructed
rather than derived. Recursive secret exclusion inspects suspicious field names
but does not establish value-aware exclusion. The terminal audit checks prose
retention rather than recomputing the executable evidence chain.

This is a proof-layer defect. It does not establish that the underlying Batch
1 through Batch 5 contracts are defective, and it grants no authority to reopen
or implement the live transition.

## Preparation Batch 0 inventory

Preparation Batch 0 must inventory and classify:

- every boolean proof claim currently accepted by the Batch 6 audit;
- the concrete typed adversarial case and expected deterministic result needed
  to replace each boolean;
- the evidence required for `ABSENT`, `PREPARED`, `COMMITTING`, `COMMITTED` and
  `INCOMPLETE`, including which findings each classification may lawfully prove;
- exact replay, changed-evidence and same-root contention evidence pairs;
- partial-write and tamper cases for journal, winner, receipt, plan, references,
  digests, classifications and directives;
- the derivation path from case execution to finding, case digest, aggregate
  audit disposition and immutable read-only audit receipt;
- whether false action flags can be derived from typed dependency boundaries or
  must be narrowed to explicit non-authority declarations;
- secret exclusion coverage for suspicious keys, nested values, encoded values,
  generic containers and process-local capability identities;
- every current documentation-only terminal assertion and the executable or
  deterministic recomputation required to support it;
- reusable fixture custody, mutation isolation and exact-root joining;
- failure identifiers, replay identity, contention identity, result sealing and
  evidence-digest requirements;
- the final closed boundary before persistence, locking, mutation, live
  authority, execution admission, binding transition, credential resolution,
  provider invocation, external I/O or provider effect; and
- threat-model assumptions, false-positive limits, refusal conditions and
  deferred boundaries.

Every finding must be classified as `EXISTS_CANONICALLY`,
`EXISTS_FRAGMENTED`, `ABSENT` or `DEFERRED_BOUNDARY`.

## Batch estimate

The planning estimate is six batches including Preparation Batch 0:

0. proof-claim, fixture, derivation, secret-exclusion and terminal-audit inventory;
1. typed adversarial case, mutation and expected-result contracts;
2. deterministic case execution and finding derivation without proof booleans;
3. evidence-bound read-only audit receipt and value-aware secret-exclusion proof;
4. terminal audit recomputation across the canonical evidence chain; and
5. adversarial remediation audit, qualification removal and campaign closure.

A refusal or correction batch expands this estimate. No batch may infer
authority from a later planned batch.

## Closed perimeter

Selection and Preparation Batch 0 may not change runtime behavior. They may not
repair the audit service, define executable transition behavior or produce a
runtime decision. They may not persist a journal, acquire a live lock, write or
repair state, issue or consume authority, admit execution, adopt a successor,
change binding state or create a durable transition winner or receipt.

They may not handle or resolve a credential or capability, invoke a provider,
perform external I/O, start a provider effect, authorize retry, migrate a live
command or open Iron Gate or Lazaretto.

The provider binding remains `BOUND_INACTIVE`. Required v3 execution admission
remains `NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.
