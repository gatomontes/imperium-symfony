# Provider Execution Effect Readiness — Batch 3 interruption proof

## Result

`BATCH_3_OFFLINE_ASSURANCE_FIXTURE_INTERRUPTION_PROVED`

All three offline fixture paths now have explicit pre-commit and post-commit
interruption proof.

| Cut | Durable truth | Recovery |
| --- | --- | --- |
| `BEFORE_IMMUTABLE_COMMIT` | No fixture exists | Exact caller-supplied fixture may be validated and stored later |
| `AFTER_IMMUTABLE_COMMIT` | One immutable fixture exists | Exact replay returns the winner; changed content under the same ID refuses |

Two independent store instances on the same authoritative root converge on the
same immutable source fixture. Changed evidence under the winning identity
fails closed through immutable conflict detection.

## Exact scope

The proof covers:

- provider-assurance evidence-source fixtures;
- exact AgentMail direct-send assurance-profile fixtures; and
- non-authorizing provider-assurance admission fixtures.

Recovery reads or re-submits caller-supplied fixtures only. It does not fetch
provider documentation, contact AgentMail, reconstruct a credential, activate a
principal or binding, consume provider-execution authority, authorize retry or
promote an offline fixture into live provider truth.

## Threat model

The proof is limited to one authoritative filesystem root and trusted-writer
canonical integrity. Two service instances demonstrate shared-root immutable
winner convergence. No hostile-writer, multi-host, distributed lock,
split-brain or consensus claim is made.

## Closed perimeter

The proof service depends only on the Batch 2 fixture store. It imports no
credential, transport, provider adapter, combined execution admission, Iron
Gate or Lazaretto component. No provider effect or external I/O occurs.

## Batch 4 gate

Only Batch 4 may next be considered: read-only aggregate reconstruction and
classification of the exact source/profile/admission fixture chain as
`ELIGIBLE_OFFLINE_EVIDENCE`, `INCOMPLETE`, `CONFLICTED` or `REFUSED`.

Batch 4 may not create, repair or promote fixtures; admit live provider truth;
activate a principal or binding; define a live-call runtime; handle
credentials; invoke a provider; authorize retry; migrate a consumer; or open
Iron Gate or Lazaretto.

Estimated campaign countdown after Batch 3: approximately seven batches,
excluding any separately selected sterile provider-conformance campaign.
