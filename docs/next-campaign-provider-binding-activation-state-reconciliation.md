# Next campaign: Provider Binding Activation State Reconciliation

## Selection

Provider Binding Activation State Reconciliation is the smallest lawful next
campaign after principal-activation resumption closes.

The question is not whether operation-scoped activation evidence exists. It does.
The question is whether that evidence, its revocation winner and the immutable
implementation binding describe one coherent binding lifecycle when the binding
record itself remains BOUND_INACTIVE.

## Preparation question

Preparation Batch 0 must inventory and classify:

- the exact meaning and owner of BOUND_INACTIVE and BOUND_ACTIVE;
- operation-scoped activation evidence versus durable implementation-binding state;
- the exact active executor-principal generation required by binding activation;
- the competent activation and revocation authorities;
- activation, expiry, revocation and effect-start ordering;
- replay and contention roots;
- crash boundaries and reconstruction;
- provider-assurance and provider-contract prerequisites;
- credential and process-local capability non-authority;
- secret exclusion;
- live consumer and command migration gaps; and
- candidate boundary postures, including refusal or campaign division.

## Non-authority

Selection is preparation-only. It does not define a runtime contract or alter
runtime behavior. It does not activate a binding, issue or consume authority,
handle or resolve a credential or capability, invoke a provider, perform
external I/O, start an effect, authorize retry, migrate a live consumer or
command, or open Iron Gate or Lazaretto.

The principal activation already proved by the closed campaign is prerequisite
evidence, not authority to activate a provider binding.
UNKNOWN_REPLAY_PROHIBITED remains binding.
