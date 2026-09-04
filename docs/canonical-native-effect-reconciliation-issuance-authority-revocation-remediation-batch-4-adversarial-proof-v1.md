# Canonical reconciliation issuance authority remediation — Batch 4 adversarial proof v1

`BATCH_4_COMPLETE_ADVERSARIAL_RECONSTRUCTION_PROOF`
`NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL`
`BATCH_5_SEPARATELY_SEQUENCED_TERMINAL_AUDIT_NEXT`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Result

The public issuance act requires a resolver-issued, exact-object, process-local
`NativeEffectReconciliationIssuanceCapability`. Durable decisions, source
transitions, service construction and copied capability fields are evidence,
not custody. Issuance and claim publication each repeat present-tense source
validation inside their target lock, before durable consumption/publication.

Receipt reconstruction now joins, without writes:

```text
receipt -> claim consumption -> claim -> reconciliation consumption
        -> reconciliation authority -> issuance evidence
        -> issuance-authority consumption -> issuance authority -> decision
        -> native authority -> native principal -> current Operator Root
```

## Executed matrix disposition

| Cases | Disposition and proof owner |
| --- | --- |
| AUTH01–AUTH07 | Closed: typed issuer, canonical decision/authority resolution, exact object registry; transition/source evidence and issuer identity cannot substitute. |
| AUTH08–AUTH10 | Closed: consumed capability replay refuses; fresh exact retry converges; changed window conflicts; process contenders share one semantic-target consumption. |
| CUR01–CUR04 | Closed in Batch 3: Root/native/source generation and lifecycle changes are re-resolved at issuance-use and claim-use. |
| CUR05–CUR07 | Preserved: equality expiry, digest substitution and post-claim forward inspection refuse. |
| CUR08A | Bounded refusal: current untimestamped Operator Root revocation makes later reconstruction refuse `NIR_ROOT_INELIGIBLE`; no historical Root eligibility claim. |
| CUR08B | Preserved: timestamped native/source lifecycle history remains reconstructible at the historical admission time while current Root is eligible. |
| CUST01–CUST04 | Closed/preserved: clone/serialization/counterfeit/fresh-process rules; fresh process resolves new custody from durable unconsumed evidence. |
| CONS01–CONS03 | Closed/preserved: one claim winner, exact receipt retry, substituted source/consumer conflict. |
| EXP01–EXP04 | Closed: decision, issuance authority and both capabilities refuse at `at >= expires_at`; target expiry cannot exceed source. |
| SUB01–SUB03 | Closed: exact lineage/reference/digest checks and immutable publication. |
| CUT01 | Closed by pre-consumption currentness refusals: no publication or consumption. |
| CUT02–CUT03 | Closed: after durable issuance consumption, only the exact deterministic publication can complete; authority-only interruption is unresolvable until exact retry. |
| CUT04–CUT07 | Closed/preserved: stale capability refuses; pre-claim in-memory loss leaves no durable consumption; post-consumption retry completes only exact receipt; reconstruction is read-only. |
| APP01–APP03 | Closed: corridor, direct construction and worker paths all require explicit shared typed custody. |
| APP04 | Preserved absent: no production command invokes reconciliation issuance. |
| APP05 | Controlling Batch 4 marker supersedes documentary zero-stage statements without rewriting historical evidence. |
| OS01 | Windows identity/locking semantics are covered by the local full suite. |
| OS02 | Linux path identity remains case-sensitive by contract; no Linux execution or CI result is claimed in this local batch. |
| OS03 | Deferred: `flock` behavior on unsupported/distributed filesystems is not proved. |
| GIT01–GIT02 | Closed: Batches 2, 3 and 4 begin from distinct clean local commits on the authorized branch. |
| CI01–CI02 | Preserved: workflow presence is not a result; terminal audit remains Batch 5-only. |
| BND01 | Closed locally by source scan and executed paths: no provider, credential or network edge. |
| BND02 | Deferred: multi-host exclusion is not proved by the local filesystem lock. |
| BND03 | Deferred: a hostile same-process or filesystem administrator remains outside the cooperative trust model. |

Competing claimants and claim/receipt interruption retain the earlier Batch 4
provenance tests. Competing issuers are additionally executed through two fresh
PHP worker processes. Reconstruction is guarded by before/after tree digests and
source scans that prohibit record writes, consumption, provider, credential and
environment access.

No external CI, GitHub review, remote publication, merge, credential access,
provider effect or live trial is claimed or authorized.
