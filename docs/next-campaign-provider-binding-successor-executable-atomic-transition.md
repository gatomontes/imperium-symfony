# Next campaign: Provider Binding Successor Executable Atomic Transition

## Selection

`PROVIDER_BINDING_SUCCESSOR_EXECUTABLE_ATOMIC_TRANSITION_SELECTED`

The bounded Atomic Transition independently verifiable reproof is accepted at
`CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF`. The earlier
Provider Binding Successor Atomic Live Transition campaign remains complete at
`PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION_CAMPAIGN_COMPLETE_PRE_EXECUTION_ONLY`.
It proved contracts, inert interruption semantics and read-only reconstruction;
it did not implement an executable atomic transition.

This separately selected campaign owns that still-closed boundary. Its objective
is a locally executable, authority-consuming transition that either atomically
admits v3 execution, adopts one successor, changes the exact provider binding and
emits a durable reconstructable receipt, or refuses without an ambiguous retry.

Only Preparation Batch 0 may next be considered. Selection grants no runtime,
transition, provider, credential, capability or external-effect authority.

## Campaign sequence

Campaign countdown at selection: nine stages including Preparation Batch 0.

0. **Preparation Batch 0 — executable boundary inventory.** Classify the exact entry point, principals, authority chain, stores, lock and transaction primitives, irreversible cuts, recovery obligations, receipt consumers, proof gaps and the smallest safe implementation sequence.
1. **Batch 1 — v3 admission and consumption contracts.** Define authority-empty execution-admission, consumption, outcome, refusal and receipt contracts.
2. **Batch 2 — durable journal and lock boundary.** Implement the local journal, exact-root lock discipline and crash-visible state machine without adopting a successor or changing the binding.
3. **Batch 3 — atomic transition consumer.** Implement single-use authority consumption, v3 admission, successor adoption and binding transition as one governed local operation.
4. **Batch 4 — real local contention proof.** Exercise separate-process same-root contention and prove one durable winner with losing-path refusal.
5. **Batch 5 — interruption and recovery proof.** Exercise authorized crash cuts, partial writes, replay, expiry and revocation; preserve `UNKNOWN_REPLAY_PROHIBITED` wherever outcome cannot be proved.
6. **Batch 6 — durable receipt and reconstruction.** Produce the exact receipt lineage and prove read-only reconstruction of success, refusal, incomplete and indeterminate states.
7. **Batch 7 — adversarial evidence audit.** Challenge counterfeit authority, changed roots, substituted successors, lock bypasses, secret leakage and unproved durability claims.
8. **Batch 8 — separately sequenced terminal Blackquill audit.** Start from clean merged Batch 7 main and decide the bounded executable-transition claim.

A refusal or correction batch expands the estimate. No planned later batch authorizes an earlier one.

## Preparation Batch 0 questions

Preparation must classify:

- the exact callable runtime entry point and competent executor principal;
- decision, issuance-target and single-use transition-authority lineage;
- current `BOUND_INACTIVE` binding and exact eligible successor source;
- required v3 execution-admission producer and consumer;
- every store, root, lock, filesystem and transaction primitive involved;
- the combined write set for consumption, admission, adoption, binding and receipt;
- the first irreversible operation and every observable interruption cut;
- real separate-process contention support versus snapshot-only evidence;
- replay identity, expiry, revocation and indeterminate-outcome policy;
- durable receipt, audit lineage and read-only reconstruction consumers;
- credential, secret and process-local capability exclusion;
- platform assumptions for atomic rename, file locking and durability;
- the boundary before credential resolution, provider invocation, Iron Gate, Lazaretto and any external effect; and
- the smallest ordered implementation and proof sequence.

Every finding must be classified as `EXISTS_CANONICALLY`, `EXISTS_FRAGMENTED`,
`ABSENT` or `DEFERRED_BOUNDARY`.

## Hard boundary

Preparation Batch 0 is documentary and read-only with respect to runtime state.
Do not implement an executable contract, persist a live journal, acquire a live
transition lock, issue or consume authority, admit v3 execution, adopt a
successor, change provider binding, create a winner or receipt, resolve or handle
a credential or capability, invoke a provider, perform external I/O, start an
effect, authorize retry, or open Iron Gate or Lazaretto.

Provider binding remains `BOUND_INACTIVE`. Required v3 execution admission
remains `NOT_IMPLEMENTED`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.

## Local continuation

The canonical entrypoint is
`docs/handoffs/provider-binding-successor-executable-atomic-transition-preparation-batch-0-local-ready.md`.
It authorizes Preparation Batch 0 only.
