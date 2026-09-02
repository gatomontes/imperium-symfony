# Frozen Runtime Coverage Tripwire Restoration Batch 3 Blackquill audit

## Verdict

`TERMINAL_ADVERSARIAL_AUDIT_PASSED_TRIPWIRES_RESTORED`

Campaign closure:
`FROZEN_RUNTIME_COVERAGE_TRIPWIRE_RESTORATION_COMPLETE`.

## Claim under audit

The restored suite fails closed when the current frozen runtime grows outside
its explicit inventories, while permitting additions only after path-level
classification.

## Weak point attacked

A scan of the unmodified checkout proves almost nothing about future drift. A
green test can be bureaucratic theater if nobody demonstrates that a forbidden
addition makes it red. The audit therefore drove the actual restored coverage
tests against disposable copies of the runtime and versioned inventories. It
did not substitute a second detector or mutate the real runtime tree.

## Adversarial results

| Synthetic mutation | Expected result | Observed result |
| --- | --- | --- |
| New authority candidate absent from snapshot and successor inventory | Fail | Failed exact candidate equality |
| New `AuthorityConsumptionStore` consumer absent from approved consumer inventory | Fail | Failed exact consumer equality |
| New LaCortine perimeter file explicitly inventoried but importing a forbidden helper without a named helper exception | Fail | Failed complete-perimeter forbidden-helper scan |
| New runtime producer of `RETIRE_CORRIDOR` absent from the role-classified vocabulary inventory | Fail | Failed exact vocabulary-producer equality |
| New detected candidate added through an explicit versioned candidate row | Pass | Passed exact candidate equality |

The tests use `IMPERIUM_FROZEN_COVERAGE_ROOT` only to point the same test logic
at a disposable copy. Every copy is deleted after its case. No mission,
provider, credential, external I/O, private evidence or live runtime state is
touched.

## Batch 2 reinspection

- The legacy separate revocation fact remains `DO_NOT_PRODUCE_SEPARATELY` and
  no longer masquerades as an authorized revocation winner.
- The absent-attestation-principal path still binds the activation target to
  the decision actor's required binding and rejects substitution.
- The disposition-vocabulary exception inventory contains exactly six paths,
  matching the six current runtime files that contain either governed token.

## What this does not prove

Restored tripwires are alarms, not transactional correctness. This audit does
not prove global authority safety, provider execution safety, evidence closure,
private-receipt sufficiency or live operational behavior. It does not repair
the independently verified evidence defect. The controlling posture remains
`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT`.

## Conclusion

The regression is closed. New candidates, store consumers, perimeter helpers
and disposition producers can no longer hide behind old snapshot membership or
an unexplained count. No Frozen Runtime Coverage Tripwire Restoration batch
remains.
