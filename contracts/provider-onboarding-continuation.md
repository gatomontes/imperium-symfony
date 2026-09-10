> Current target-selection amendment: [medium-capacity rule](provider-onboarding-assignment-selection.md)
> fixes assessed-assignment terms and response-to-view derivation for v1.4.

# O0 F1/F2 continuation and authority variants — v1.2 draft

> v1.3 amendment: [bounded retries](provider-onboarding-retries.md) extends this
> unimplemented draft with run_condition, finite assessment_groups and one-use
> attempt slots. It supersedes success-only dependency rules for retry edges only.
> Original v1.2 acceptance remains historical; the amendment needs targeted review.

**PROPOSED, UNAPPROVED, UNIMPLEMENTED.** This document is authoritative for the
proposed progression protocol and effect/authority variants. It replaces the
ambiguous v1/v1.1 draft fields, not any implemented or signed runtime schema.
Read with the [main contract](provider-onboarding.md), [D2 proposal](../docs/provider-onboarding-authority-proposal.md)
and [owner sheet](../docs/provider-onboarding-owner-decisions.md). The
[authority review](../docs/reviews/provider-onboarding-o0-authority-independent-review.md)
is retained unchanged. Selected choices are recorded; genuine evidence and remaining technical approvals stay unresolved.

## F1: three separate identities

`sequence_id` identifies one bounded onboarding journey under one exact admitted
policy and budget. `command_id` identifies one invocation for transport retry.
`step_id` identifies one immutable node in that policy's finite sequence graph.
None identifies a new budget. IDs use `[a-z0-9][a-z0-9._-]{7,79}` except graph
step IDs, which use `[a-z0-9][a-z0-9._-]{0,79}`. Command keys are the tuple
`(instance_id, sequence_id, command_id)`. Step keys are
`(instance_id, policy_digest, step_id)` across all sequences. A new sequence or
command cannot claim a previously reserved, consumed or unknown step again.

The policy binds `budget_ref` and ordered finite `steps`. Each step is exactly
`{step_id, action, effect_slot_id, depends_on, input_refs, run_condition}`. Action is
`RECORD_CONFIGURATION`, `SELECT_BASE`, `EXECUTE_ACCESS`, `EXECUTE_ASSESSMENT`,
`APPLY_ASSIGNMENTS` or `ADMIT_AUTHORIZED_RECORD`. The last action may admit only
an exact F2 registered effect/record, never an arbitrary service callback.
Configuration and selection have null effect_slot_id and confer no authority;
every other step names exactly one F2 slot. Dependencies reference prior step IDs,
form an acyclic graph, and resolve retained successful results. The v1.3
run conditions govern assessment retries and group success; failure edges are
never ordinary depends_on edges. Policy admission
rejects duplicate IDs, cycles, unbounded loops or undefined actions. Each external
call is a distinct step/slot, including each of the four attempts in W1, W2 and W3; no hidden retry/subcall.

Sequence registration binds policy digest, budget ref, instance and initial public
evidence refs permanently. It is mechanical, not a policy approval or grant.
One active sequence owns a policy's graph; another ID for that same policy returns
`SEQUENCE_ALREADY_REGISTERED` and its existing reference. A new policy still
shares the original budget/unknown-effect fences and cannot recover consumed
rights. Material changes require their actual owner act, not a new ID alone.

## Exact proposed commands

`onboard` accepts schema `imperium.provider-onboarding-request/v2` with exactly:

| Field | Definition |
| --- | --- |
| schema | Exact v2 value above |
| sequence_id, command_id | Stable journey and distinct retry IDs |
| mode | `preview` or `advance`; preview is pure and does not reserve either ID |
| instance_id | Exact public identity checked against fixed trusted root |
| policy_ref | Exact `{id,version,digest}` of already admitted policy |
| expected_head | `{generation,digest}`; current aggregate head. Generation 0 permits null digest only for verified empty aggregate, not proof of fresh founding; otherwise SHA-256 ref |
| predecessor_ref | Null only for registration; otherwise the exact current advancing command ref |
| step_id | Null for registration; otherwise one ready, unconsumed step in the admitted policy |
| evidence_refs | Exact sorted unique registered public refs needed by this command; cannot replace frozen inputs or expand policy |

A command ref is `{sequence_id,command_id,request_digest,result_digest}`.
Digests use `sha256:` plus 64 lowercase hex. Expected head and predecessor are
part of semantic identity, along with **every** other envelope field including
mode; hash canonical decoded JSON, retaining raw input digest separately.
Changing the head or a step therefore requires a new command_id, not another
human approval where the original policy still covers the step.

The dispatcher first looks up the command key under the owning lock. An identical
fingerprint recognizes its retained command result **before** current-head,
expiry or progression checks. It never re-enters the action. Changed fingerprint
returns `COMMAND_CONFLICT`, without altering the original record. A rejected
well-formed command may retain a refusal receipt; it consumes no step. Retrying
that refused ID returns that same refusal. An amended request uses a new ID.
Malformed input creates no command authority or step reservation.

Only for a new command does the consumer verify current head, exact predecessor,
unchanged policy/evidence, current authority, ready dependencies, unconsumed
slot and remaining shared budget. One aggregate transition reserves the step,
records the command and consumes applicable authority before external work.
No lock spans network/credential I/O. Later custody/response frames belong to the
same step and command; they do not mint a new advancing command.

The advancing command ref becomes the logical `sequence_head` when its admission
commits, including while its effect is pending. A later command must name that
ref and cannot overtake its unfinished effect. Rejected commands, status and
evidence-only resume never become the logical predecessor. A result ref is the
immutable admission result; retained completion is separate linked evidence, so
the predecessor's identity never changes when a response arrives.

The immutable admission result contains exactly `sequence_id,command_id,
request_digest,step_id,predecessor_ref,policy_ref,budget_ref,observed_head,
admission_status`. It contains no claim/result/self reference; result_digest
hashes this object. Claims then reference that digest. Subsequent response,
completion and application records reference the admission, never feed their
digests back into it. All commit_head fields in this proposal identify the
verified predecessor aggregate head, avoiding self-referential frame hashes.

`status <sequence-id>` reads current head, logical predecessor, ready step and
retained facts with no effects. `resume` accepts exactly
`{schema: imperium.provider-onboarding-resume/v2, sequence_id, command_id,
expected_head, recognize_command_ref}`. It has its own command fingerprint/key
and recognizes existing evidence for that exact advancing command. It may publish
verified completion metadata/recognition; it cannot create a new claim, dispatch,
personnel act or uncommitted assignment. It never changes sequence_head. An
unknown or mismatched original response remains fenced. After resume changes the
aggregate head, the next advance uses a new command ID and the newly observed
head but the same logical predecessor.

Output schema is `imperium.provider-onboarding-status/v2`; exact fields are
`schema, sequence_id, command_id, mode, status, reason_codes, head, sequence_head,
result_ref, facts, next_action, evidence_refs, effects, operational_flags`.
`next_action` is `{code,step_id,explanation}`; step_id null when no advance is
permitted. Main-contract fact dimensions and exit codes remain unchanged.
Status has null command_id/result_ref. Duplicate dispatch returns the original
result_ref/status plus a recognition presentation with new_effects_this_command
false; it does not rewrite the original result to report current progress. Use
status for fresh facts. `head` is the observed aggregate head; `sequence_head` is
the immutable admitted advancing command ref. `COMMAND_CONFLICT`, `STALE_HEAD`,
`STALE_PREDECESSOR`, `STEP_NOT_READY` and `STEP_ALREADY_CONSUMED` are REFUSED (1).
Unknown work reports OUTCOME_UNKNOWN (3), preserving maximum exposure.

| Command/state | Exact outcome |
| --- | --- |
| Initial advance: new sequence/command, null predecessor/step, current head | Register existing policy/graph/budget binding; no provider call or new spend rights |
| Identical command after aggregate advances or policy expires | Recognize original result_ref before currentness checks; no action, new claim or new authority |
| Same command ID with updated head, different step or inputs | COMMAND_CONFLICT; original result preserved |
| Next authorized step, new command ID, current head and exact predecessor | Consume only its existing ready step/slot; remaining original policy permits progress without repeated approval |
| Known completed call with later authorized calls remaining | Status/resume establishes retained completion; new advance targets next W-step under same sequence/policy/budget |
| New command with stale head/predecessor | Refuse without step/budget use; read status and submit new command ID |
| Policy expired/revoked or current actor changed | Refuse new dependent action; prior completed results remain recognizable |
| Reserved/pending predecessor | No overtaking. Evidence-only resume may recognize its original result; it cannot dispatch it again |
| Unknown predecessor or disputed completion | Fence later steps in the sequence, preserve original exposure; new sequence/policy/command IDs cannot bypass the shared unknown-effect fence |
| Applied receipt already committed | Recognize exact whole-set application; never reapply or reassess |

## F2: one authoritative effect registry

This is the **complete proposed effect registry**, replacing the earlier claim
that the transition matrix alone was complete. S means a separately signed
original; B means a transport batch of such originals, each independently signed
and verified, with no batch-derived competence; P means the exact admitted
policy-derived effect described below. B is not a different authority variant.

| Effect | Competent issuer | Allowed supply | Conditions |
| --- | --- | --- | --- |
| AUTHORIZE_BOOTSTRAP_POLICY | Human Operator with bootstrap-policy competence | S/B only | Initial policy cannot authorize itself |
| ADMIT_BOOTSTRAP_EVIDENCE | Operator, exact bounded evidence scope | S/B/P | Original public bytes and permitted admission predicates fixed by policy |
| AUTHORIZE_BOOTSTRAP_ACCESS | Operator, bounded infrastructure scope | S/B/P | Exact destination/credential operation/limits; no Augur or Locksmith fiction |
| CONSTITUTE_FOUNDING_AUGUR | Human Operator's existing fresh founding competence | S/B/P | Verified open window and exact supplied root artifacts; P only finite approved candidate/Profile tuples; never existing cutover |
| APPROVE_STANDING_AUGUR_PROFILE | Competent Imperator/Operator over exact governed evidence | S/B only | Genuine complete approval chain; no implicit approval from model output |
| DESIGNATE_STANDING_AUGUR_PROFILE | Current competent Laboratorium steward | S/B only | Independent steward act over exact approved Profile |
| QUALIFY_BIND_AUGUR | Current competent Recruiter | S/B only | Actual qualification/binding evidence; no approval inferred from policy |
| AUTHORIZE_AUGUR_CUTOVER | Competent Operator | S/B only | Exact predecessor/replacement; never automatic Augur self-replacement |
| AUTHORIZE_BOOTSTRAP_ASSESSMENT | Operator over exact current Augur/bounded purpose | S/B/P | Each call still has its own predeclared one-use step/slot/resource reservation |
| APPLY_BOOTSTRAP_ASSIGNMENTS | Operator over exact allowed role/model/Profile set | S/B/P | P only when application mode A expressly authorizes the conditional slot; B mode requires S/B |
| APPROVE_RUNTIME_BINDING_MAP | Operator, infrastructure mapping scope | S/B/P | Exact mapping or finite enumerated mappings; no new capability/access evidence |
| REVOKE_BOOTSTRAP | Competent Operator | S/B only | Exact act/policy/issuer target; no self-derived revocation or deletion |

Each signed act uses the D2 envelope and independently admitted public trust.
Batch items retain their own nonce, signature, object, expected head and expiry.
Admission is ordered; where heads must change, prepare/sign against those actual
heads. A batch is never permission to skip head checks or predict nonexistent
receipt hashes. P exists precisely for work already covered by bounded consent;
it does not invoke a signer at runtime or synthesize signed originals.

The policy additionally contains exact `effect_slots`, a unique-ID list. Each
slot is `{slot_id,effect,authority_mode,terms_rule,depends_on,max_uses,expires_at}`;
max_uses is 1, expiry no later than policy expiry, and authority_mode is
`signed_act` or `policy_effect`. Only the latter requires registry permission P.
Signed mode requires the genuine original matching the slot's exact terms; a
slot reservation cannot sign it or supply missing institutional competence.
`terms_rule` is one of the following tagged closed objects:

- `{kind: exact, object_ref}`;
- `{kind: eligible_binding, permitted_object_refs, base_step_id}`: choose exactly
  one pre-enumerated complete object whose binding equals the valid base result;
- `{kind: assessed_assignment_set, permitted_tuple_set_ref, result_group_ids,
  required_predicates_ref, expected_assignments_ref, selection_rule_ref}`: construct the exact whole
  set only from permitted tuples after every required predicate passes.

No arbitrary expression, executable callback, wildcard, unbounded role set or
natural-language condition is allowed. For assessed sets, each role appears once,
all required roles appear, no Augur replacement appears, and exact approved
Profile/configuration refs match. Required predicates are enumerated policy
IDs with PASS required; missing/UNKNOWN fails. Derived terms and their evidence
refs are retained before consumption and deterministically recomputable.

Every record field that expresses owner authority uses tagged `authority`:

```text
{kind: signed_act, act_ref, admission_ref}
{kind: policy_effect, policy_ref, policy_admission_ref, slot_id, slot_digest,
 terms_ref, derivation_input_refs}
```

All refs use the D2 `{schema,id,digest}` shape. Exactly the fields of the chosen
variant are permitted. There is no null act placeholder, fake signature or
manufactured Operator act in the policy_effect case. The consumer verifies the
original competent policy admission, registry permission, exact rule/terms,
dependencies, issuer currentness/revocation and globally unconsumed slot key.
Signed-act consumption keys are `(instance_id, trust_fingerprint, nonce)`;
P keys are `(instance_id, policy_digest, slot_id)`. Neither command nor sequence
IDs are authority-consumption keys.

Every progressing command also consumes its policy step/slot key, in addition
to the S/P authority key, in the same transaction. Action/effect matching is
closed: EXECUTE_ACCESS uses AUTHORIZE_BOOTSTRAP_ACCESS; EXECUTE_ASSESSMENT uses
AUTHORIZE_BOOTSTRAP_ASSESSMENT; APPLY_ASSIGNMENTS uses APPLY_BOOTSTRAP_ASSIGNMENTS.
ADMIT_AUTHORIZED_RECORD is only for the evidence, founding, standing approval,
designation, qualification/binding, cutover and mapping rows. Policy authorization
and revocation use their separately competent admission interfaces, not this
progression action. Access/assessment grant materialization may precede dispatch,
but does not consume an additional right: the one advancing EXECUTE command
atomically binds that original grant and consumes its exact one-call slot before
I/O. It cannot be split into two advancing commands which consume the same right.
Twelve possible attempts require twelve predeclared one-call slots/grants, not reuse of one
consumed commission. Each grant record's original authority remains referential
evidence after consumption, never continuing permission.

For governed Augur changes, Recruiter qualification and Operator cutover remain
distinct DAG steps with distinct S-only slots. Qualification retains its genuine
result but does not install a successor; the later cutover verifies that result
and its original consumption, then commits the binding. It cannot reconsume the
Recruiter nonce or substitute policy-derived qualification. Similar dependent
records recognize completed prerequisite acts without treating them as reusable
permission to repeat their original effects.

The application receipt body is exactly
`{policy_ref,result_ref,authority,prior_assignments,next_assignments,
consumed_effect_ids,commit_head}`. `authority` is either variant above, never
application_act_ref. Mode A consumes its exact policy slot; mode B consumes the
original APPLY act. Both atomically publish the same whole-set assignment and
receipt. Prior/next generations and predicate rules are identical in both modes.

## Tagged access and assessment claims

The proposed claim body is exactly
`{sequence_ref,command_ref,policy_ref,authority_source,prepared_operation_ref,
budget_ref,authority_consumption,lease_consumption,custody_checkpoint,response_ref}`.
Response ref alone may be null until retained. No other field is silently nullable.
Sequence ref is `{instance_id,sequence_id,policy_digest}`; command ref is the F1
immutable admission ref. `authority_source` is exactly one closed variant:

```text
{kind: access, grant_ref, authority,
 executor: {kind: infrastructure, adapter_ref, deployment_custody_ref,
            credential_binding_ref}}
{kind: assessment, commission_ref, authority, holder_ref}
```

Access has no commission_ref or Augur holder. Its real executor is fixed,
deployment-approved infrastructure bound to original custody evidence and the
exact credential operation. Adapter identity is not sovereign competence; the
access grant's Operator authority supplies bounded permission. Assessment has
an exact current Augur holder and commission; no infrastructure identity can
replace that holder. Both consume genuine authority and share budget accounting.

`authority_consumption` is `{key,consumed:true,commit_head}` referencing the exact
S/P key above. Lease consumption is tagged too:

```text
{kind: access, grant_ref, executor_digest, expires_at, consumed:true, commit_head}
{kind: assessment, commission_ref, holder_ref, expires_at, consumed:true, commit_head}
```

These are proposed derived lease records, not existing Locksmith-issued formation
leases. Only the matched source-specific verifier can produce/consume them.
Validate policy/source/terms/executor or holder, budget, prepared operation and
expiry at each existing custody checkpoint. Foreign/mixed fields refuse before
credential release. Historical FC claim/lease schemas remain untouched.

## Freeze checks and continuing limits

Before accepting implementation, prove: identical command recognition after later
heads/expiry; changed-head same-ID conflict; new-ID next-step progression without
new approval; no overtaking or cross-sequence budget reset; all S/B/P registry
combinations including rejected variants; conditional application without an act;
access without an Augur; exact schema/nullability refusal; revocation before fresh
dispatch/application; atomic command/slot/application commits and crash recovery.
These are specification acceptance cases, not tests run in O0.
DeepSeek/API key, FRESH, D2-A and the retry/cost choices are recorded. This
clarification enrolls no trust and changes no runtime. See the current
[provider closure](../docs/handoffs/provider-onboarding-o0-provider-report.md)
for exact remaining evidence and approvals; O1–O5 remain deferred.
