# Native Integration Remediation Batch 4

`NATIVE_INTEGRATION_BATCH_4_ATOMIC_NATIVE_CONSUMER_IMPLEMENTED`.

NativeConsumer::execute accepts a durable native authority id, resolves current
principal/successor sources beneath shared native/source locks, persists intent,
retires registered empty legacy stores under their own locks, revalidates sources
and time after pending bytes are flushed, then publishes all seven outcomes in one
commit. It returns the actual NativeBindingReader result. Journal intent grants
no authority; interrupted/pending intent never authorizes repair or retry.

Migration requires an operator-owned complete inventory in the trust directory.
Only provisioned directories below var/imperium/runtime/legacy-provider-transitions
are supported. Existing grants, attempts, outcomes or unknown files refuse
migration. Empty registered stores get an irreversible retirement marker; legacy
issuance/consumption then refuse. This proves exclusion only for the registered
single-host deployment, not unregistered stores, copied roots or other hosts.
No live inventory, retirement, grant or transition was provisioned in this campaign.

Full PHPUnit: **1883 tests, 44414 assertions passed**. Focused Batch 4:
11 tests, 47 assertions. Three stages remain: process proof, independent
reconstruction/adversarial audit, then the separate terminal audit.
BOUND_INACTIVE stays immutable; native v3 is effective only through the complete
pre-effect commit. Credentials/providers/I/O/effects/retry stay closed.
