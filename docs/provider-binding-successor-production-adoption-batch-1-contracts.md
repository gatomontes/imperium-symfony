# Provider Binding Successor Production Adoption Batch 1 contracts

## Result

BATCH_1_AUTHORITY_EMPTY_PRODUCTION_DECISION_CREATION_AUTHORITY_AND_ADOPTION_TARGET_CONTRACTS_COMPLETE

Batch 1 defines three separately versioned, authority-empty shapes:

- the exact competent Imperator production decision;
- the decision-bound, single-use successor-creation authority;
- the explicit execution-adoption target for a completed reconciled successor.

The production decision binds the competent actor, source decision authority,
reconciled target and decision input, exact transition, disposition, limitations,
validity and a reference to the separately shaped creation authority.

The creation-authority contract binds one production decision, one competent
actor, one exact successor target and one replay/contention root. Its invariant
is single-use, initially unconsumed and without continuing authority. Contract
existence neither issues nor stores an authority.

The adoption target binds only a completed successor and its exact ACTIVE
principal activation, original BOUND_INACTIVE implementation descriptor,
assurance admission, execution boundary, operation scope and replay/contention
root. It forbids legacy-activation substitution, successor synthesis, original
binding mutation, global BOUND_ACTIVE assertion, credential resolution,
provider invocation, external I/O, effect-start and live adoption.

## Separation from the existing corridor

The current v2 combined admission is unchanged. These contracts do not make it
accept the reconciled successor, do not revise effect-start ordering and do not
create a v3 admission. Production successor creation remains a separate future
atomic transition; explicit adoption remains a later boundary after creation
proof.

There is no producer, validator, fixture, store, consumption record,
reconstructor, production decision, issued authority, created successor or live
consumer. The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.

## Secret exclusion

No contract field carries credential bytes, environment-variable names,
provider tokens, authentication material, callback identity, object identity or
process-local capability identity.

## Non-authorities

Batch 1 may not activate a principal or provider binding.
Batch 1 may not issue or consume authority.
Batch 1 may not handle or resolve a credential or capability.
Batch 1 may not invoke a provider.
Batch 1 may not perform external I/O.
Batch 1 may not migrate a live command.
Batch 1 may not open Iron Gate or Lazaretto.
