# Credential Boundary Batch 16 Complete — Batch 17 Ready

## Closed boundary

Batch 16 migrated La Cortine's isolated `sortie` cognition away from the direct Symfony AI agent and credential-bearing platform.

- the sealed sortie manifest is projected into the separately typed `la-cortine.sortie-cognition/v1` authority;
- the authority digest binds execution, sortie, manifestation, commission, authorization, objective, context, destinations, tool/capability scope, return contract, and expiry;
- a durable pre-I/O journal reservation makes the provider attempt checkpoint-safe and at-most-once across child-process restarts;
- the credential broker issues and consumes exactly one capability scoped to the sortie commission and provider operation;
- provider ambiguity is recorded as terminal unknown outcome with automatic replay prohibited;
- governed tools still run before cognition, with raw evidence quarantined from model interpretation and runtime provenance reserved to La Cortine;
- the one-shot runner still retires the sortie in `finally`;
- `config/packages/sortie/ai.yaml` and `ai.agent.sortie` are removed.

## Executable inventory

The executable direct-agent inventory is now empty. One global credential-bearing platform definition remains in `config/packages/ai.yaml`, so `system_wide_gate_closed` deliberately remains `false`.

## Exact next boundary — Batch 17

Batch 17 may remove the final global credential-bearing platform definition and prove the closed gate. It must not reopen Senate, Guildhall, Curia, or La Cortine behavior.

Required proof:

1. remove the final unused `ai.platform.generic.deepseek` configuration;
2. demonstrate that all provider calls construct authenticated infrastructure only inside claim-bound adapters;
3. scan source and configuration for direct credential injection and direct agent bindings;
4. turn `system_wide_gate_closed` true only when the executable proof is empty;
5. preserve every existing typed authority, durable claim, at-most-once journal, unknown-outcome refusal, and response-envelope invariant.
