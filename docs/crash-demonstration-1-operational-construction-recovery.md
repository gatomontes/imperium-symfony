# Crash Demonstration 1 — operational construction recovery

## Claim

This repeatable local demonstration interrupts the existing Steps 44–46 operational-construction coordinator immediately before and after each Codex checkpoint. Every isolated case must resume forward to one ordered generation-three Codex, reject conflicting immutable reuse, replay exactly without mutation, and stop at the inert binding boundary.

It creates no Delegate Mission Step 70, Runtime Integrity Hardening Step 36, deployment, custody transfer, runtime activation, cognition, provider, credential, tool, perimeter, external-action, execution, continuation, or reuse authority.

## Crash matrix

| Case | Injection point | State retained at interruption | Required recovery |
| --- | --- | --- | --- |
| Q-pre | `BEFORE_QUALIFICATION_INDEXED` | Qualification Folium stored; Codex absent | Index sequence 1 at generation 1 |
| Q-post | `QUALIFICATION_INDEXED` | Qualification and generation-1 Codex stored | Exact replay at generation 1 |
| A-pre | `BEFORE_ASSEMBLY_INDEXED` | Qualification indexed; Assembly Folium stored | Index sequence 2 at generation 2 |
| A-post | `ASSEMBLY_INDEXED` | Assembly and generation-2 Codex stored | Exact replay at generation 2 |
| B-pre | `BEFORE_BINDING_INDEXED` | Assembly indexed; Binding Folium stored | Index sequence 3 at generation 3 |
| B-post | `BINDING_INDEXED` | Binding and generation-3 Codex stored | Exact replay at generation 3 |

Each case continues through the remaining checkpoints, replays the complete exact input, attempts one conflicting immutable qualification, and verifies the final inert boundary.

After the six recovery cases, the harness also reuses the existing two-process Folium contender. Exactly one writer must store the qualification and exactly one must fail with the immutable-record conflict; any other outcome fails the demonstration.

## PowerShell-friendly command

From the repository root:

```powershell
php bin/console imperium:demonstrate:operational-construction-recovery --evidence-dir=var/imperium/private-evidence/crash-demonstration-1
```

The command uses deterministic fixtures in isolated temporary state. It writes one private evidence JSON file and one separately sanitized summary JSON file. The default destination is ignored by Git.

## Private retained-evidence schema

The private record contains:

- schema, demonstration ID, run ID, UTC timestamps, source commit, and PHP runtime identity;
- deterministic fixture identity and digest, without embedding the Folium payloads;
- one case per injection point containing pre-crash, post-crash, recovery, replay, conflict, and invariant observations;
- a two-process contention observation with exact winner and conflict counts;
- final Codex digest, generation, checkpoint, and ordered Folium identities, digests, and sequences;
- the sanitized summary and its digest;
- overall disposition and an evidence-record digest.

Private evidence excludes credentials, environment dumps, model identity, absolute internal storage paths, credential-adjacent material, and raw authoritative Folium payloads. Never commit retained private evidence.

## Sanitized external summary shape

Repository documentation may disclose only this shape; values below are illustrative:

```json
{
  "schema": "imperium.sanitized-operational-construction-crash-demonstration-summary/v1",
  "demonstration": "operational-construction-recovery",
  "source_commit": "<tested-commit>",
  "cases_executed": 6,
  "crash_boundaries_covered": ["before-durable-index", "after-durable-index"],
  "properties_proved": [
    "ordered_immutable_records",
    "monotonic_generation",
    "deterministic_forward_recovery",
    "exact_replay_without_mutation",
    "single_winner_conflict_rejection",
    "inert_construction_boundary"
  ],
  "final_checkpoint_class": "inert-operational-construction-complete",
  "continuing_operational_authority": false,
  "disposition": "PROVED",
  "summary_digest": "<sha256>"
}
```

The sanitized summary omits internal paths, exact private schemas, authority topology, credentials, model identity, and proprietary implementation detail.
