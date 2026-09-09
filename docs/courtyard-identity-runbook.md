> Current status: [CY/FC acceptance and integration](courtyard-fc-acceptance.md)
> supersede pending-review wording below. [Provider onboarding O0](next-campaign-provider-onboarding-o0.md)
> is selected for contract/prerequisite preparation only. O1–O5 and live activity
> remain deferred. Existing commands do not grant missing authority or readiness.

# Courtyard current source and future public workflow

Local review only. Citadel encloses Courtyard and its mission-specific Curiae.
Courtthane holds the exact reception Seat; Castellan oversight is deferred.
The [compatibility contract](../contracts/courtyard-identity-compatibility.md),
[source inventory](courtyard-identity-inventory.tsv) and
[report](courtyard-identity-report.md) govern this implementation.
FC0–FC3 remains independently unaccepted; DEFER_ENROLLMENT remains selected.

These are implemented public interfaces for a separately authorized future
workflow, not instructions to commission the installed application now:

```text
php bin/console imperium:courtyard:intake <submission-id> <request-file>
php bin/console imperium:courtyard:formation <operation-file>
php bin/console imperium:courtyard:prepare inspect <public-file>
php bin/console imperium:courtyard:prepare decision <public-request-file>
php bin/console imperium:courtyard:prepare assemble <public-signature-file>
php bin/console imperium:citadel:public-institutions
```

The three `imperium:citadel:intake|formation|prepare` spellings are aliases to the
same implementation/root, including refusal. Public-institutions remains Citadel
jurisdiction. No command accepts a root, clock, private key or adapter override.
Preparation is pure. Inspection returns 2 while readiness is blocked.

The [command schema](../contracts/citadel-formation-command.schema.json) retains
its path and strict envelope. Fresh appointment uses operation `appoint-courtthane`
with `candidate` and independently signed `decision`. The exact effect is
`APPOINT_COURTTHANE`; terms are candidate, original Citadel scope,
`seat: courtyard.courtthane`, next Courtthane generation. Obtain legitimate
Garrison → Guildhall → Laboratorium → four Senate findings → Lord Speaker →
exact Profile approval → Conscription evidence first. No legacy appointment or
prepared byte packet establishes qualification.

Receive → authorized interview/overlap discussion → attributable understanding
→ exact drafting request (“I understand. I am ready to draft a proposal. Do you
approve?”) → separate signed drafting approval → numbered proposal → separate
mission approval → legitimate constitution and appointments → full handoff →
Seneschal's independently authorized assessment → non-executing Step 1 validation.
Reception creates no Curia. No stage derives execution permission.

Use original session/attempt IDs for `recover-response`, original intake ID for
`deliver-handoff`. Already admitted responses and exact retained completed child
effects remain recognizable, preserving original bytes and attribution. Old active
reception grants cannot continue through Courtthane; unsupported fresh use refuses
without deleting evidence, resetting attempts or refunding exposure. No automatic
migration or installed-state rewrite is implemented.

Independent review is the next gate. Later prerequisites are genuine formation
public trust/custody, all nine institutional witnesses and competence, actual
personnel judgments and separately qualified Courtthane/Locksmith appointments,
selected and reviewed provider/model/destination/public credential binding,
supportable B1 cost/time/cancellation and usage guarantees, public preflight and
separate owner commissioning authorization. Native enrollment, provider onboarding
and Castellan oversight remain separate future decisions. All deployment,
enrollment, readiness, activation and execution flags remain false.
