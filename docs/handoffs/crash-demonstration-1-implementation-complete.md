# Crash Demonstration 1 implementation complete

## Boundary

Crash Demonstration 1 is an operational-evidence harness for the existing Steps 44–46 recovery corridor. It creates no Delegate Mission Step 70, Runtime Integrity Hardening Step 36, mission authority, or continuing Delegate authority.

## Implemented proof

- all six existing before/after coordinator injection points are exercised in isolated deterministic state;
- the durable Folium and Codex state at every interruption boundary is asserted exactly;
- every case resumes forward to the same ordered generation-three Codex and inert generation-one Seat binding;
- exact replay at each current checkpoint preserves that checkpoint's Codex digest and generation; no historical transition is incorrectly replayed after a later generation becomes current;
- conflicting immutable reuse fails stopped;
- the existing two-process contender proves one writer and one conflict under contention;
- prohibited deployment, custody-transfer, activation, cognition, provider, credential, tool, perimeter, external-action, execution, continuation, and reuse authorities remain absent or false;
- private machine-readable evidence is source-commit-bound and written only to a Git-ignored local destination; and
- a separate sanitized summary omits internal paths, private schemas, authority topology, credentials, model identity, and implementation detail.

## Local verification command

```powershell
php bin/console imperium:demonstrate:operational-construction-recovery --evidence-dir=var/imperium/private-evidence/crash-demonstration-1
```

Focused PHPUnit coverage:

```powershell
php bin/phpunit tests/Imperium/Runtime/OperationalConstructionCrashDemonstrationTest.php
```

The operator owns local PHP execution and private evidence custody. Neither private output file may be committed.

## Next evidence program item

After local verification is clear, proceed separately to Crash Demonstration 2: deployment custody recovery. Do not extend this demonstration into deployment or activation.
