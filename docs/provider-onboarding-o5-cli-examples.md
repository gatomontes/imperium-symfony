# O5 proposed terminal sessions

These are illustrative human-output designs, not executed commands, real receipts, runnable request files or acceptance evidence. Named request files below are future synthetic fixture placeholders. Their contents must use exact canonical v2 envelopes and real fixture-produced references. Do not run these commands now: O5 is not implemented and O4 is deferred.

## Preview with a missing prerequisite

```text
PS> php bin/console imperium:provider:onboard .\public\preview-access.json
AUTHENTICATION_PENDING
Configuration is retained. Account access has not been verified.
Next: supply the required access authorization and evidence through the existing admission interface.
This preview made no provider call and reserved no command or step.
Exit: 2
```

The public file has mode `preview`. The CLI does not ask for an API key or silently probe account access. A later advance is a separate exact request with its own identity; changing mode under an already retained command ID is not replay.

## Ready work, then interruption

```text
PS> php bin/console imperium:provider:status sequence-demo-01
ASSESSMENT_AUTHORIZED
The next assessment step is ready under the retained policy and remaining budget.
Next: submit its exact advance request.
New effects: none
Exit: 2

PS> php bin/console imperium:provider:onboard .\public\advance-assessment.json
OUTCOME_UNKNOWN
The effect may have occurred. Sufficient completion evidence is not retained.
Exposure remains reserved. No automatic retry is permitted.
Next: inspect and reconcile original evidence for this command.
Exit: 3

PS> php bin/console imperium:provider:resume .\public\recognize-assessment.json
OUTCOME_UNKNOWN
The original evidence still does not establish completion.
No provider call was made. Exposure remains reserved.
Exit: 3
```

When matching retained evidence later supports recognition, resume may record that recognition. It must not dispatch again. The following status determines the next permissible action; the example intentionally does not invent a success receipt or promise an uncertain effect can be recovered.

## Assignment proposal and later completed offline application

```text
PS> php bin/console imperium:provider:onboard .\public\preview-assignment.json
Assignment proposal
Courtthane: exact provider/model/configuration from the validated assessment set
Formation Locksmith: exact provider/model/configuration from the same set
Persistent settings have not changed.
Next: satisfy the exact application authority and submit the application advance.
```

This is a presentation excerpt. The full result must derive its public status and exit from actual prerequisites; there is no new ASSIGNMENT_PROPOSED top-level status. The assignment fact may have state `proposed`. Unsupported or absent evidence must not produce a model recommendation.

After O4 integration and successful application in a synthetic offline journey:

```text
PS> php bin/console imperium:provider:status sequence-demo-01
ASSIGNMENT_APPLIED
The complete Courtthane/formation Locksmith settings set has a retained application receipt.
Settings persist until an explicit authorized change.
Live activation: false
New effects: none
Exit: 0
```

A later stale authority or unresolved effect can change the current top-level status while preserving the historical application fact. Settings application never appoints personnel or grants invocation authority.

## Conflicting replay

```text
PS> php bin/console imperium:provider:onboard .\public\changed-existing-command.json
REFUSED — COMMAND_CONFLICT
This command ID already identifies a different request.
The original result is unchanged. No step was consumed.
Next: inspect current status; use a new command ID for a new request.
Exit: 1
```

Future scripts should consume `--format=json` and the exit code. They must not scrape these illustrative sentences or interpret exit 2 as a failed provider call.
