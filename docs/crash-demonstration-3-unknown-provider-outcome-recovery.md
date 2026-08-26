# Crash Demonstration 3 — unknown provider-outcome recovery

This repeatable demonstration reuses the durable invocation claim, provider journal, recovery assessment, immutable response envelope, single-use authority consumption, and provider-free turn recovery services.

| Boundary | Required proof |
| --- | --- |
| After claim | Governed resolution required; automatic replay false |
| After external-I/O start | Outcome classified unknown; duplicate start rejected |
| After unknown classification | Unknown state remains terminally non-replayable |
| After response-envelope seal | Captured response becomes eligible for governed forward recovery |
| After recovery-authority consumption | Exactly one authority consumption and one turn |
| After turn persistence | Exact replay returns the same turn; alternate authorization fails stopped |

The unknown-outcome case and sealed-response case are deliberately separate. An unknown outcome without a response is never converted into success. Forward recovery occurs only from an already sealed exact response under an exact unexpired single-use authorization, and records `provider_reinvoked=false`.

```powershell
php bin/console imperium:demonstrate:unknown-provider-outcome-recovery --evidence-dir=var/imperium/private-evidence/crash-demonstration-3
```

Private evidence remains Git-ignored and excludes credentials and environment dumps. The sanitized summary discloses only the properties proved, source commit, boundary count, false reinvocation/authority flags, disposition, and digest.

Operator-local execution against source commit `bd3620ccd32e1511c96d53caacb60806348cf995` returned `PROVED`: both the unknown-outcome and sealed-response cases proved with zero failed assertions, only one of two provider starts was accepted, and recovery recorded `provider_reinvoked=false`. Private evidence remains outside Git.
