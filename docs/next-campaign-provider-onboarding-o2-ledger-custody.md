# Completed campaign — O2-B1

O2 is accepted and integrated through PR #790, merge `9b50d53362e44af1d628034ad6153985b9a24d1c`. Its exact tree passed fresh full CI: 3,536 tests / 56,244 assertions / four skips.

The O2 correction is closed in offline scope. Proceed to [O3-B0](next-campaign-provider-onboarding-o3-deepseek-adapter.md) using its [handoff](handoffs/provider-onboarding-o3-deepseek-adapter-ready.md). The following original campaign is retained history, not the next local task.

---

# O2-B1 — ledger and custody implementation campaign

Status: PREPARED_FOR_SOURCE_REVIEW; implementation must wait for disposition of this preparation. Five implementation batches remain across O2-O5. O2-B0 is accepted offline via PR #788, merge cafa93f9665f0e5734f26491a092b28995e1cf14, tree e2070c7a2184c7481882f27297c976d248b2c2be. See [integration identity](provider-onboarding/o2-b0-reviewed-integration.json) and [attributed review](reviews/provider-onboarding-o2-b0-correction-source-evidence-review.md). Original author/HOLD reports remain unchanged history.

Start from the reviewed preparation commit in a separate codex/ worktree. Record actual entry commit/tree and preserve existing checkouts. Read applicable AGENTS.md, the [ledger/custody contract](../contracts/provider-onboarding-ledger-custody.md), [surface/test matrix](provider-onboarding-ledger-custody-surfaces.md), [roadmap](provider-onboarding-implementation-roadmap.md), B0 campaign/contract/implemented shapes, and all governing D2/F1/F2/retry/P1-P9/medium-capacity sources linked there.

Implement only the contract's dormant fixed-root B1 mechanisms, explicit v2 migration, internal no-lock authority verifier, shared FC/onboarding exposure enforcement, monotone custody checkpoints and evidence-only recovery. Inspect actual code before reusing it. Keep one journal/owning lock; no imported current-authority boolean, nested lock or second claim ledger. Do not claim a shared budget until the actual FC reservation and custody paths participate in the same calculation and a real source association verifies. Pure results, retained originals and completed effects remain distinct.

Follow the exact surfaces and adversarial matrix. Preserve original B0 reviewer tests and R1-R4 fixes, all CY/FC/O1 acceptance, original policy/workload bytes and source pins. Add honest runtime inventory entries for new candidates; do not rewrite frozen snapshots. Test new code with real signed synthetic producers, fixed offline ports and temporary roots. Never request live trust/credentials to make offline tests pass.

No subagents, provider requests, credentials, installed-state access, genuine enrollment, appointment, founding, assignment, activation, service wiring, O3/O4/O5 implementation, push or merge. Later producers remain typed refusals. Keep DeepSeek/API-key, FRESH, D2-A, P1-P9, medium target capacity, least-cost eligible initial base, persistent settings, empty actual retry allowlist, DEFER_ENROLLMENT, unresolved B1 remote guarantees and all five false operational flags.

Complete the matrix's actual validation; distinguish passing, skipped, interrupted and inherited results. Commit implementation before final PHP testing and retain exact tested checkout copies/hashes with canonical Git comparison. If documentation follows testing, supply tested and final commit/tree and exact diff. Full CI/source integration remains review work after local completion.

Return provider-onboarding-o2-b1-all-deliverables.zip with README, individual report/instructions, every changed source/test/document, public commands/logs, source and protected identities, binary patch and Git bundle, complete manifest and checksummed inner review ZIP. No outer checksum. Retain necessary provenance without recursively duplicating old convenience archives. Stop for source/integration review within B1.
