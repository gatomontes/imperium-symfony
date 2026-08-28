# Iron Gate evidence authenticity adversarial proof

## Verdict

The deterministic outbound-email corridor now fails closed for caller-authority substitution,
transition substitution, target substitution, expiry, stale principal generation, changed-consumer
replay and unsealed mutation. Exact same-consumer replay returns the one immutable consumption
receipt and is the sole forward-recovery path after consumption committed but the separately locked
target write did not.

## Proof matrix

| Requirement | Classification | Proof and limit |
| --- | --- | --- |
| Exact caller and transition | `EXISTS_CANONICALLY` | Three non-interchangeable transitions require a native Seneschal or Imperator authority. Wrong transition fails before consumption. |
| Exact target | `EXISTS_CANONICALLY` | Request binds the canonical input-intent digest; decision and issuance bind sealed record identity and digest. Substitution fails. |
| Expiry and source freshness | `EXISTS_CANONICALLY` | Issued-at/expiry, instance, binding, active status, generation and current source digest are revalidated at consumption. |
| Single-use and replay | `EXISTS_CANONICALLY` | `AuthorityConsumptionStore` provides one receipt per authority. Exact source/consumer replay is recovery; changed source or consumer conflicts. |
| Crash recovery | `EXISTS_CANONICALLY` | Consumption precedes target commit. The exact replay can finish a missing target; no rollback fiction or continuing authority is claimed. |
| Concurrency | `EXISTS_CANONICALLY` | Authority identity is serialized before the immutable consumption-directory lock. One exact tuple wins; a competing tuple fails. This is single-root proof only. |
| Local idempotency identity | `EXISTS_FRAGMENTED` | The authorized request binds provider, endpoint, key digest and request fingerprint, and the adapter forwards that exact key. There is no durable global key registry. |
| Provider deduplication | `DEFERRED_BOUNDARY` | Provider retention, collision domain and duplicate-response behavior require provider contract evidence and cannot be proved offline. Live adoption must stop without it. |
| Response provenance | `EXISTS_CANONICALLY` | Accepted response bytes can arise only inside the admitted callback and are sealed into its invocation-bound envelope. This proves callback lineage, not remote cryptographic authorship. |
| Hostile-writer resistance | `DEFERRED_BOUNDARY` | Unkeyed canonical digests detect unrecomputed mutation under the trusted-writer model. A hostile writer able to replace records and recompute digests is not defeated. |
| Secret exclusion | `EXISTS_CANONICALLY` | Caller-authority, consumption and target records contain references/digests only; credential material remains broker-local. |
| Multi-host atomicity | `DEFERRED_BOUNDARY` | Locks and rename durability cover one authoritative filesystem root, not split brain or eventual consistency. |

## Boundary verdict

The proof authorizes no live provider invocation. Provider-side idempotency evidence and any stronger
hostile-writer or distributed-storage guarantee remain mandatory stop conditions. Iron Gate,
Lazaretto, sortie, credential-platform, revocation, propagation, telemetry, reassessment,
containment and incident boundaries remain closed.

