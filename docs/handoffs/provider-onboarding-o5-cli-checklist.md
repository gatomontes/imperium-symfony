# O5-B0 implementation checklist — blocked, not a run prompt

Preparation is complete. Do not start implementation, local benchmarks or CI from this packet. O4 performance work remains deferred. Resume O4 only when the operator selects it; require accepted O4 integration before selecting O5 implementation.

## Entry gate for a later implementation campaign

- Record the accepted O4 main commit/tree and full-gate evidence. Do not use the current draft or checkpoint as accepted integration.
- Recheck the [CLI specification](../provider-onboarding-o5-cli-preparation.md) against integrated owners. Preserve canonical contracts, identities, scopes and originals.
- Agree a bounded implementation/validation budget before launching sustained work. This document does not authorize an open-ended optimization or test loop.

## Implementation work

- Add the three console commands with exact v2 file ingress; keep preview in the request mode. Add human and `--format=json` rendering that share one semantic result and exit mapping.
- Build the public seven-dimension fact projection from retained originals and current checks. Inventory each fact's owner/reference and how unknown is represented. Resolve internal RETAINED/pending_claims and missing-sequence error mapping explicitly.
- Resolve exact ID-domain constraints against the existing ingress. Preserve command fingerprints, predecessor identity, head conversion and duplicate recognition. Do not widen validation silently.
- Audit status/preview for zero provider and credential activity, no ID reservation, migration or domain-state mutation. Specify synchronization behavior and refuse absent/uninitialized roots before any automatic creation.
- Route advance and recovery through existing owners. Do not mint new approval, authority, budget or retry semantics in a console command. Keep root/clock/adapter/trust fixed and secrets outside arguments/output.
- Implement the full offline journey with real integrated producers and synthetic ports; no fabricated success records. Verify the final complete assignment set, persistence and explicit authorized changes.

## Meaningful acceptance scenarios for that future campaign

| Scenario | Required observation |
| --- | --- |
| Preview/status, including absent root and sequence | No provider/credential call, registration, migration or state creation; accurate bounded refusal |
| Missing credential observation, access evidence or Augur authority | Correct independent facts, actionable missing prerequisite, exit 2; no implicit probe |
| Ready step and exhausted budget | Correct owner admission or refusal; no later-step auto-loop |
| Identical replay and changed command fingerprint | Original result without effects, or COMMAND_CONFLICT without rewriting history |
| Interrupted custody / uncertain dispatch | OUTCOME_UNKNOWN, full exposure preserved, zero redispatch/reissue on resume |
| Original response reconciliation | At-most-once recognition; sequence_head unchanged; fresh aggregate head for next advance |
| Valid application followed by stale prerequisites | Historical application retained without claiming current usability |
| Human/JSON and malformed JSON | Consistent semantic facts/exits; exact closed schema and bounds; no secret or traceback leakage |
| Full offline journey in a fresh process | Actual accepted O1–O4 paths and persistent whole-set receipt; all operational flags false |

At future acceptance, retain source identity, complete required validation and logs. Selected examples or fixture-only output do not replace the complete gate. No acceptance run is selected now.

## Current pause record

- Main baseline inspected: `6fbc625b50e411b654ffcc7423502d1ec90cc3cb` (tree `862fbfbf51294ad4a175381ded7a6f7b64dad09c`).
- O4 V2 local tested source: `c2dc4cb647877e4057214355f6c4d33d9f365c5f`, tree `c7f9f5b66698f7450ddcb1661fd8a0595da7b598`.
- Preserved GitHub equivalent: `dc9c815ae3e903c016809a604ff538c86aafbc22` on `codex/provider-onboarding-o4-b0-ci-v2-checkpoint`.
- Independent selected validation: 45 tests / 6,415 assertions. Latest recorded full local gate: timeout after 1,800.219 seconds, exit 124. O4 remains unaccepted.
- Earlier prompts authorizing repeated O4 profiling and full runs are paused by the operator's subsequent deferral. This preparation does not restart them.
