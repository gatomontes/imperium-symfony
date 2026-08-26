# Crash Demonstration 4 — terminal retirement recovery

This repeatable demonstration reuses the production terminal-transition coordinator, custody and binding compare-and-swap stores, immutable terminal Folium store, replay fingerprint, and five existing fault checkpoints.

| Injection point | Custody | Binding | Terminal Folium |
| --- | --- | --- | --- |
| `PREPARED` | Deployed and unavailable | Bound | Absent |
| `CUSTODY_RESTORED` | `ADMITTED_HELD` and available | Bound | Absent |
| `BINDING_RETIRED` | Restored | Retired and unbound | Absent |
| `TERMINAL_RECORDED` | Restored | Retired and unbound | Exactly one |
| `COMPLETE` | Restored | Retired and unbound | Exactly one |

Every case resumes to one terminal Folium, restored available Persona custody, retired unbound Delegate binding, exact completed replay, conflicting-input rejection, and zero surviving operational authority. A separate two-process divergent submission proves one winner and one conflict.

```powershell
php bin/console imperium:demonstrate:terminal-retirement-recovery --evidence-dir=var/imperium/private-evidence/crash-demonstration-4
```

Private evidence remains Git-ignored and excludes credentials and environment dumps. The sanitized summary discloses only the source commit, case count, property names, terminal state class, false continuing-authority flag, disposition, and digest.

Operator-local execution against source commit `598cbcdf749fc804b979a2ddfb310bf025b385b2` returned `PROVED`: all five terminal checkpoints recovered with zero failed assertions, two divergent contenders produced exactly one winner and one conflict, the sanitized-summary digest remained bound, and continuing operational authority remained false. Private evidence remains outside Git.
