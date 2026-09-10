# Provider onboarding contract — proposed O0 v1

> v1.3 amendment: read [bounded retries](provider-onboarding-retries.md) and
> [selected owner decisions](../docs/provider-onboarding-selected-decisions.md).
> DeepSeek/API key, FRESH and D2-A are selected. Up to three separate authorized
> retries per assessment call are permitted by the owner decision, within $1.20.
> Uncertain outcomes remain fenced; no claim or dispatch is replayed.

> Current design revision: v1.3.1 draft, with the retry amendment authoritative
> for group selectors/application terms. The [independent review](../docs/reviews/provider-onboarding-o0-independent-review.md)
> verified v1 preparation, not O0 completion. [D2 integration proposal](../docs/provider-onboarding-authority-proposal.md)
> supplies proposed act/trust/store/currentness/one-use interfaces;
> [owner decision sheet](../docs/provider-onboarding-owner-decisions.md) supplies
> historical proposals; the selected-decision record identifies accepted choices. This interface is not frozen
> for separate implementation. No proposed schema below exists in runtime.

Status: **UNIMPLEMENTED; REVIEW REQUIRED; AUTHORITY CORRIDOR UNRESOLVED**.
This document grants no operational authority and adds no command or adapter.
The [prerequisite matrix](../docs/provider-onboarding-prerequisites.md) is normative
for supported versus missing transitions; the [base-model policy](../docs/provider-onboarding-base-model-policy.md)
defines deterministic selection. Unknown required policy values refuse execution.
O1–O5 and live activity need separate selection after O0 review.

## Institutional and compatibility boundary

Onboarding is operator/infrastructure work. Citadel encloses Courtyard; Courtthane
is the exact `courtyard.courtthane` LEGATE, Oracle/Augur is `oracle.augur`, and
Seneschal governs its legitimate mission Curia. No provisional Curia, new Office,
Castellan oversight, root principal or appointment authority is introduced.

Keep [CY identity compatibility](courtyard-identity-compatibility.md),
[formation runtime](citadel-formation-runtime.md) and [FC custody](citadel-formation-claim-custody.md).
One custody/budget domain must account for related consumption; no provider CLI
alias or bootstrap phase may introduce a bypass namespace. Existing records keep
their original schema, signed bytes, IDs, consumption and reservations. A future
typed extension requires reviewed producers/consumers; it cannot relabel a
formation interview claim as Augur work. The existing three formation phases
remain interview, drafting and receiving/acceptance.

Separate understanding, exact drafting approval, mission approval, legitimate
constitution/appointments, receiving assessment and execution gates remain.
Model selection/assignment does not supply any of them. Historical recognition
does not renew an expired, revoked or consumed grant.

## Adapter and authentication interface

One deployment-selected adapter implements a versioned public description and
pure preparation, plus an infrastructure-only dispatch boundary. Calling code
cannot choose a class, endpoint override or arbitrary callback via input JSON.
This contract is a target, not a claim that the legacy DeepSeek adapter conforms.

| Operation | Required behavior |
| --- | --- |
| describe | Return immutable adapter ID/version/source digest; provider ID; supported exact model IDs/revision limitations/configuration schema; authentication capability matrix; allowed method/protocol/host/port/path tuples; supported limits and usage evidence schema. No account/credential/network access |
| prepare | Given exact public request, policy and legitimate authority references, produce canonical request digest, exact non-secret serialized wire bytes/digest, destination, provider/model/configuration, source/pricing digests, public credential operation and per-call/total bounds. Refuse unsupported serialization/bounds before custody or I/O |
| dispatch | Receive only the genuine one-use retained claim and infrastructure callback authentication; revalidate prepared bytes and fixed destination immediately before one dispatch. No redirects, hidden retries, alternate endpoint/model, SDK fallback or extra billable subcall |
| validate return | Bind actual response ID, original content bytes/digest, request identity, model as reported, integer usage, tariff evidence and adapter provenance. Missing/contradictory/out-of-bound usage cannot settle. A string or a provider-ID label alone is insufficient |
| recover | Read retained validated metadata and matching immutable envelope only; never authenticate, issue a capability or dispatch. Do not reconstruct a response from untrusted caller text |

Authentication capabilities are declared separately:

| Capability | First implementation scope | Current disposition |
| --- | --- | --- |
| API key through custody callback | One owner-selected provider, exact supported credential operation | DeepSeek API-key route SELECTED; [public appendix](../docs/provider-onboarding-deepseek-appendix.md) retained; exact live evidence missing; route unimplemented |
| Credential presence | Local custody observation only | Distinct from account authentication and model access |
| Account authentication/verification | Exact separately authorized request, destination, limits and retained evidence; some providers may have no non-billable probe | UNIMPLEMENTED; no implicit probe during configure/status |
| OAuth authorization-code/browser or device flow | Explicit future adapter capability with scopes, state/PKCE/redirect/token custody contract | UNSUPPORTED in first scope |
| Token acquisition/refresh/revocation | Infrastructure only, separately bounded; cannot change model/provider/assignment policy | OAuth extension DEFERRED; generic bearer-token support proves none of these |

Secrets, refresh tokens and private signing keys never appear in prompts, command
arguments, public input files, prepared operations, status, manifests or logs.
Credential material remains behind infrastructure custody. Public references
are opaque identifiers, not encoded secrets or filesystem paths. Resolve them
through a reviewed fixed mapping. Legacy `env:`, `clavium://` and FC-safe identifiers
are not interchangeable; their mapping is an implementation prerequisite.
Error output uses stable reason codes and redacted public facts, not adapter or
credential exception text. Do not claim general secret detection from a denylist.

O0 uses no credentials or private state. A future credential rotation updates
custody through its authorized interface, invalidates relevant access observation
and preserves assignment scope. Cross-process recovery must not serialize or
re-create an already-issued process-local capability. The current environment
broker explicitly lacks cross-process custody support.

## Proposed command surface

The exact **unimplemented v2** envelopes, progression/replay identities and result
presentation are defined in [F1/F2 continuation](provider-onboarding-continuation.md).
That document replaces the earlier v1 draft's operation_id identity rule; no
implemented command or signed schema is migrated. The proposed spellings remain:

```text
imperium:provider:onboard <public-request-file>
imperium:provider:status <sequence-id>
imperium:provider:resume <public-request-file>
```

Sequence identity, per-command retry identity and one-use step identity are
distinct. Expected head belongs to the semantic request fingerprint. An identical
command recognizes its original result even after the head advances; a new
authorized step uses a new command ID, exact predecessor and current head under
the same policy and budget. Known remaining work needs no repeated human approval;
unknown outcomes remain fenced. Resume remains evidence-only.

Fixed deployment root/clock/adapter/trust; no private-key, credential value,
force, retry-unknown, reset-window, activate or enroll option. Input is UTF-8 JSON,
unique keys, no BOM, at most 1 MiB, depth at most 32. Unknown fields/versions refuse.
Reference IDs/versions use `[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}` and must resolve in
their registered domain. Digest refs use `sha256:` plus 64 lowercase hex.
Provider model IDs remain exact policy values, not filesystem references.
Status writes nothing and has no credential dependency. Preview cannot reserve
an ID or fabricate authority. F1 defines exact command/result fields; the status
dimensions and exit codes below remain common to both interfaces.

`facts` has `configuration, credential, authentication, base_selection,
augur_authority, assessment, assignment` dimensions. Each is `{state, refs}`;
refs are retained public `{id,digest}` pairs. Legal state sets respectively:
`absent|configured`; `unknown|missing|present`;
`unverified|pending|verified|failed|unknown`;
`unselected|selected|ineligible`; `missing|current|stale`;
`not_authorized|authorized|pending|retained|unknown`;
`absent|proposed|applied|blocked`.
No dimension's state implies another. `effects` reports retained original
operation facts, not effects of a read-only status call. Its exact fields are
`claim_refs, exposure, assignment_receipt_refs, new_effects_this_command`.
Refs are lists of `{id,digest}`. Exposure has nonnegative integer
`calls,input_tokens,output_tokens,cost_microusd,milliseconds`, or is null when
unverifiable; null never means zero. The last field is a boolean, false for
status/preview and receipt-only recognition.
Operational flags `deployment_approved, enrollment_authorized, live_ready,
activation, execution_authority` are all false for this preparation contract.

| Status | Meaning / next permissible action | Exit |
| --- | --- | --- |
| CONFIGURED | Public configuration validated; satisfy remaining credential/access/authority prerequisites | 2 |
| MISSING_CREDENTIAL | Missing custody reference/material observation; use separately authorized custody interface | 2 |
| AUTHENTICATION_PENDING | No verified account access; supply exact authorization/evidence, never auto-test | 2 |
| BASE_SELECTED | Pure explanation/proposed binding retained; establish exact legitimate Augur chain | 2 |
| MISSING_AUGUR_AUTHORITY | P05–P08 chain absent/stale or incompatible; identify precise missing transition | 2 |
| ASSESSMENT_AUTHORIZED | Exact institutional and resource scope current; later authorized advance may perform bounded work | 2 |
| RESULT_PENDING | Claimed/dispatched work awaiting retained evidence; status or evidence-only resume | 2 |
| ASSIGNMENT_APPLIED | Exact committed authorized application receipt exists | 0 |
| REFUSED | Invalid input, unsupported capability, policy failure, conflict or terminal refusal; report exact reason | 1 |
| OUTCOME_UNKNOWN | Effect may have occurred, no sufficient completion evidence; retain full exposure, no automatic retry/refund | 3 |

Successful preview/status does not mean onboarding complete. Status precedence:
integrity/conflict/terminal refusal first, then any unresolved effect as
OUTCOME_UNKNOWN, then a complete valid application receipt, then the first unmet
prerequisite in the table order. Historical applied assignment remains in facts
when fresh use is blocked. More precisely: unknown credential observation after
configuration reports CONFIGURED; established absence reports MISSING_CREDENTIAL;
unverified/pending account authentication reports AUTHENTICATION_PENDING. A pure
base proposal reports BASE_SELECTED only when the next prerequisite has not yet
been inspected; established missing/stale Augur chain reports
MISSING_AUGUR_AUTHORITY. Invalid required evidence or no eligible candidate
reports REFUSED with the exact selection reason. A known pending retained response is RESULT_PENDING;
lost/uncertain dispatch is OUTCOME_UNKNOWN. An absent sequence returns REFUSED
with `SEQUENCE_NOT_FOUND`; an absent root cannot be treated as a fresh founding
window. CLI surfaces underlying bounded reasons without recommending broad activation.

## Policy, consent and authority binding

The owner policy references an exact instance, provider/auth adapter version,
target role set, finite allowed model/configuration sets, proposed base algorithm,
workload/evidence digests, dates, per-call/total limits, maximum calls, expected
assignment generations and permitted transition effects. It also binds the
institutional authority references for each effect. Schema/issuer/signature
ingress and trust/currentness bridge are **UNRESOLVED D2**, so no executable policy
artifact is manufactured in O0. A document named policy is not authority.

The v1.2 design proposes that ingress in the D2 document's common signed envelope
and `operator-bootstrap-policy/v1` record. These are new proposed typed objects,
not accepted formation/native schemas. D2 remains UNAPPROVED pending competence
decision, exact public evidence and review. A conditional policy covers only its
enumerated permitted tuples; it never authorizes a missing Profile or appointment.

One initial bounded decision may permit the approved sequence without repeated
prompts, including one mechanical application of an attributable Augur result
within exact permitted roles/candidates/configurations. All institutional,
resource, custody and currentness prerequisites must independently pass.
Outside provider/scope, changed limits/configuration, expired/revoked policy,
unsupported revision pinning, different assignment head, additional paid work or
Augur self-replacement requires an explicit new owner decision and the relevant
competent act. Silence and assessment text supply no approval.

No automatic reassignment/fallback. Settings persist until explicit operator
change. Credential refresh does not change them. An unavailable model stops the
affected operation while retaining its binding and original history.

## Persistence, concurrency and interruption target

The following are future implementation obligations. Existing legacy `save` or
`binding_atomic` labels are not proof of this protocol. O0 creates no runtime
store. Final storage integration must use the one authoritative custody/budget
domain with typed source-specific authority, preserving FC frames and lock order.
An onboarding projection may cache public status; it cannot authorize dispatch,
reset consumption or settle exposure independently of that domain.

Every transition binds operation/request/policy/evidence digests, instance,
expected head, current actor/assignment generations, exact effect and retained
receipt. Validate trusted chain and currentness under the owning lock, then
compare-and-swap to generation+1. Concurrent stale writers refuse. Publication
of a complete assignment set and consumption of its one-use authority must have
one commit point; no partial role set can appear applied. Never hold the aggregate
lock over credential I/O, network dispatch or another store's lock.

| Interruption boundary | Required retained result / resume behavior |
| --- | --- |
| Pure preview/selection before publication | No external effect; recompute from identical frozen inputs |
| Policy/configuration/selection publication | Immutable exact replay or conflict; selected is not assigned |
| Before legitimate authority closure | MISSING_AUGUR_AUTHORITY; no fixture acts or founding-window reset |
| Reservation and claim commit | Maximum exposure and exact one-use authority retained atomically; no second claim via fresh operation ID |
| Credential delivery/consume checkpoints | Preserve uncertainty before effect; never reissue a capability after crash |
| Dispatch checkpoint | One entry per genuine claim; no internal SDK/application retry. A v1.3 retry uses a separate eligible slot; crash before physical I/O may forfeit the attempt |
| Response metadata/envelope | Recover only matching validated metadata and immutable original bytes; forged/relabelled response cannot settle |
| Assignment application before commit | Entire set unapplied; revalidate current policy/head to complete same legitimate effect |
| Assignment commit before acknowledgement | Recognize exact original receipt once, without repeating assessment or consuming authority again |
| Expiry/revocation/succession | Prevent fresh dependent effect; recognize already completed evidence without renewing authority |

Unknown calls retain full maximum exposure without retry/refund, even if a local
timeout or lost acknowledgement suggests no result. At-most-once local entry is
not exactly-once remote delivery. Local timeout is not remote cancellation,
zero billing or provider completion evidence. B1 remains unresolved. A separate
future custody investigation cannot rewrite history or silently free exposure.
Trusted storage/cooperating locks remain assumptions; administrator rollback,
power loss and remote billing guarantees are not proven by this protocol text.

## Future conformance and division

Before implementation acceptance, prove: pure status/preview with zero credential
or transport calls; exact input/version refusals; absent/forged/stale authority;
fresh versus closed founding window; deterministic eligibility/cost ties and
unknown prices/access; API-key capability and explicit OAuth refusal; exact wire
and destination/usage binding; changed model/configuration refusal; persistent
assignments across restart; same-ID conflict; cross-ID budget contention;
multi-process one-use delivery/dispatch; crash at every listed checkpoint;
unknown-outcome retention; receipt-only recovery; atomic assignment set; no
Courtthane/Curia authority substitution; unchanged CY/FC recovery and native fences.
These are acceptance requirements, not passing O0 tests.

For v1.2, `resume` is strictly evidence-only reconciliation: it cannot advance an
unstarted access/assessment dispatch, produce personnel acts or apply an uncommitted
assignment. A later authorized `onboard` advance may consume remaining policy
rights only if no unknown predecessor effect is bypassed. Recognition of an
already committed receipt remains available after expiry under original evidence.

After explicit implementation selection, a runtime/custody agent may own the
selected adapter, typed runtime integration and runtime tests; a CLI agent may
own the three proposed commands, presentation and CLI tests in another worktree.
Both consume this frozen contract. Shared schemas/authority design are owned by
one integration owner; neither agent independently changes competence or limits.
Integration verifies the combined commit and gates. No agents are launched in O0.

The implemented `imperium:courtyard:intake`, `:formation` and `:prepare` and their
Citadel aliases remain the [separate formation interfaces](../docs/courtyard-identity-runbook.md).
They are reused for later legitimate formation, not as onboarding aliases.
`DEFER_ENROLLMENT`, CF01/CF02/IR01 and native corrections remain preserved.
