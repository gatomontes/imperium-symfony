# Officer taxonomy

`Officer` is the umbrella term for an Imperium persona instantiated on the neutral `generic-officer` substrate. It does not, by itself, state whether an appointment is permanent or temporary.

| Class | Meaning | Lifecycle |
| --- | --- | --- |
| `LEGATE` | A permanent, Office-bound Officer | Persists in its Office and Seat across individual commissions until lawfully superseded, retired, or removed |
| `DELEGATE` | A non-permanent Officer | Exists for a bounded examination, mission, proceeding, or commission and returns, retires, or terminates when that authority ends |

Every Legate is an Officer. Every Delegate is an Officer. `Officer` alone must never be interpreted as either class.

## Record convention

New qualification, assembly, binding, activation, commission, and cognition records must carry an explicit `officer_class` value once tenure is known. Permanent Office routes use `LEGATE`; examination-only and mission-operational routes use `DELEGATE`.

The legacy-compatible `artifact_class: officer` and `generic-officer` substrate identifiers remain canonical. They describe the shared Officer substrate, not appointment class, and therefore must not be renamed to either Legate or Delegate.

Authority remains record-bound. Classification as a Legate or Delegate grants no approval, occupancy, deployment, cognition, provider, credential, tool, external-action, or execution authority.
