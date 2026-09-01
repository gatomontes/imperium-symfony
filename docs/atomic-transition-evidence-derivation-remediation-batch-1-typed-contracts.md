# Atomic Transition Evidence Derivation Remediation Batch 1 typed contracts

## Result

`BATCH_1_TYPED_CASE_MUTATION_EXPECTED_RESULT_AND_IMMUTABLE_FIXTURE_CONTRACTS_COMPLETE`

Four separately versioned, sealed contract families now define the read-only
inputs that a later deterministic evaluator may consume:

- an immutable evidence fixture with exact instance, replay/contention root,
  fixture kind, ordered source-contract schemas and strict evidence validation;
- a mutation with exact kind, target path, replacement digest and expected
  validator error, without carrying replacement or secret material;
- an expected result with exact classification, directive, comparison,
  validator error and unique finding codes; and
- an adversarial case joining one primary fixture, an optional comparison
  fixture, one mutation and one expected result by exact sealed references.

The seal order is fixture, mutation, expected result, then case. The case cannot
contain future execution or finding digests. Pure validation delegates supplied
journal, winner and receipt evidence to the existing strict transaction
validator and rejects shape, order, root, digest, reference and status drift.

The contracts execute no case and derive no finding. They apply no mutation,
seal no audit receipt, repair no Batch 6 service and remove no campaign
qualification. They import no persistence, lock, mutable state, authority
consumption, execution-admission, adoption or binding-transition service.

The prior closure remains
`CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT`. The provider binding
remains `BOUND_INACTIVE`. Required v3 execution admission remains
`NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.
