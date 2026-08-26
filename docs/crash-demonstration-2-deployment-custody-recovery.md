# Crash Demonstration 2 — deployment custody recovery

This repeatable demonstration reuses the production deployment-custody coordinator, mutable custody compare-and-swap, immutable transition store, replay fingerprint, and four existing fault checkpoints. It creates no new lifecycle step or authority.

| Injection point | Durable interruption state | Recovery target |
| --- | --- | --- |
| `PREPARED` | Authorization-bound transaction prepared; custody held | Advance custody, record transition, complete |
| `CUSTODY_DEPLOYED` | Custody deployed and unavailable | Record exactly one transition, complete |
| `TRANSITION_RECORDED` | Custody and immutable transition durable | Complete without duplicate mutation |
| `COMPLETE` | Transaction complete | Exact completed replay |

Every case proves one deployed-and-bound inactive custody state, one immutable transition, exact replay, rejected conflicting reuse, and absence of runtime activation or operational authority. A separate two-process divergent submission proves one winner and one conflict.

```powershell
php bin/console imperium:demonstrate:deployment-custody-recovery --evidence-dir=var/imperium/private-evidence/crash-demonstration-2
```

Private evidence contains source commit, runtime and fixture digests, interruption snapshots, recovery/replay/conflict observations, assertions, sanitized-summary binding, and an evidence digest. It excludes credentials, environment dumps, model identity, and raw production evidence. Never commit it.

The sanitized summary exposes only demonstration identity, source commit, case count, property names, inactive final-state class, false activation/continuing-authority flags, disposition, and digest. It omits internal paths, schemas, authority topology, credentials, model identity, and implementation detail.
