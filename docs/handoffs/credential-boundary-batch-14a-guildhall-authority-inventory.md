# Credential boundary Batch 14A — Guildhall authority inventory

## Boundary

Batch 13 remains closed. This checkpoint maps the existing Guildhall deliberation route before any cognition gateway, direct agent definition, or credential-bearing platform changes.

In scope:

- `guildhall_disciplinary_fit`;
- `guildhall_composition`;
- `guildhall_boundary_challenge`; and
- `guildmaster`.

Out of scope:

- every Senate route and record;
- Curia cognition and `seneschal`;
- La Cortine and `sortie`;
- both global credential-bearing platforms; and
- any claim that the system-wide credential gate is closed.

The executable inventory remains **6**.

## Current caller and durable lineage

`GuildhallDeliberationService::deliberate()` is the only caller of the four-stage `GuildhallCognitionGateway::deliberate()` route.

Before cognition, it rereads and validates:

1. one sealed `imperium.guildhall-commission-acceptance/v1` record at
   `var/imperium/offices/guildhall/acceptances/{acceptance_id}.json`;
2. the exact accepted `imperium.planning-commission/v1` and its mission-plan turn;
3. one atomic `imperium.guildhall-seat-binding-cohort/v1` at
   `var/imperium/offices/guildhall/occupancy/{binding_id}.json`; and
4. an optional `imperium.guildhall-deliberation-checkpoint/v1` at
   `var/imperium/offices/guildhall/deliberation-checkpoints/{acceptance_id}.json`.

The acceptance binds the commission, delivery, summons, atomic binding cohort, Guildmaster actor, authorized scope, deliberation authority, and personnel-disposition authority. It grants no spawning, Seat-binding, or execution authority.

The accepted commission remains planning-only, carries no execution authority, and fixes the capability-to-profession translation boundary under `guildhall.guildmaster`. Its mission-plan digest is the immutable plan input for all four stages.

The binding cohort must contain exactly these generation-1 occupants:

- `guildhall.committee.disciplinary-fit`;
- `guildhall.committee.composition`;
- `guildhall.committee.boundary-challenge`; and
- `guildhall.guildmaster`.

The checkpoint binds the exact acceptance identity and digest plus the exact mission-plan digest. Its `decision` member currently carries the completed committee records and, after the last stage, the Guildmaster synthesis.

## Separately typed native authorities

The common governance cognition substrate must receive four native Guildhall authority families. They must not be represented by one omnibus Guildhall deliberation grant.

| Stage | Exact occupied Seat | Required predecessor | Immutable stage input | Consumption record | Success opens |
|---|---|---|---|---|---|
| Disciplinary fit | `guildhall.committee.disciplinary-fit` | Valid accepted commission, atomic four-Seat binding, exact mission plan; no committee checkpoint | Acceptance/commission/plan/binding digests; authorized scope; exact Seat occupant and generation; disciplinary-fit purpose | Disciplinary-fit governance cognition claim plus sealed checkpoint entry | Composition authority only |
| Composition | `guildhall.committee.composition` | Valid disciplinary-fit checkpoint from the same lineage | All common digests; exact Composition occupant; sealed disciplinary-fit record and digest; composition purpose | Composition governance cognition claim plus sealed checkpoint entry | Boundary-challenge authority only |
| Boundary challenge | `guildhall.committee.boundary-challenge` | Valid disciplinary-fit and composition checkpoints from the same lineage | All common digests; exact Boundary-Challenge occupant; both prior committee records/digests; boundary-challenge purpose | Boundary-challenge governance cognition claim plus sealed checkpoint entry | Guildmaster synthesis authority only |
| Guildmaster synthesis | `guildhall.guildmaster` | All three valid committee checkpoints from the same lineage | All common digests; exact Guildmaster occupant; ordered committee records/digests; synthesis purpose | Guildmaster governance cognition claim plus full checkpoint, then `imperium.guildhall-profession-determination/v1` | Garrison inventory inquiry authority only |

Each native authority must bind:

- its own schema, identity, purpose, and authority type;
- acceptance identity and digest;
- planning commission identity and digest;
- mission-plan turn identity and digest;
- atomic binding identity and digest;
- exact Seat, manifestation identity, and occupancy generation;
- stage-specific input digest;
- exact predecessor checkpoint identity/digest set;
- single-use cognition authority;
- no credential disclosure, transferable network authority, execution continuation, spawning, or Seat-binding authority; and
- the exact next authority that may be opened by successful consumption.

## Checkpoint-safe at-most-once rule

The current gateway skips a stage when its output is present in the deliberation checkpoint. That is resume-safe for ordinary completed writes, but the output checkpoint is not itself a durable pre-I/O cognition claim.

Batch 14 must preserve the existing checkpoint behavior while adding a separate pre-I/O claim for every stage:

1. lock and reread the stage's native authority and exact predecessor checkpoint lineage;
2. atomically consume that authority into one claim before credential resolution or provider I/O;
3. bind one stable provider idempotency identity to the claim;
4. reserve the provider invocation journal before the broker resolves a credential;
5. seal the parsed stage output into the checkpoint only after a known provider response; and
6. on resume, accept an existing sealed output only when its claim and full lineage remain intact.

An unknown provider outcome must remain non-reinvokable. A missing output after a consumed claim must stop for evidence-led recovery; it must not be treated as an unstarted stage.

## Required refusal proof

Focused proof must reject, before credential resolution or provider I/O:

- replay after a completed stage;
- divergent reuse of the same authority;
- substitution of another Guildhall Seat or Manifestation;
- substitution of a committee claim at another committee stage;
- use of a Guildmaster claim for committee cognition, or the reverse;
- changed acceptance, commission, mission-plan, binding, or occupancy lineage;
- missing, stale, reordered, or altered predecessor checkpoints;
- a later-stage request before its exact predecessor is sealed;
- a consumed claim whose provider outcome is unknown;
- a malformed or superseded governance claim; and
- credentials, credential references, authorization headers, environment values, or secret-bearing diagnostics in persisted records, exceptions, logs, and serialized output.

## Migration order

1. Add the four immutable Guildhall native authority records and one resolver per authority family.
2. Open only disciplinary-fit authority from the accepted commission and exact cohort.
3. Migrate and prove disciplinary-fit cognition through the shared claim-bound broker.
4. Open composition authority only from the sealed disciplinary-fit result; migrate and prove it.
5. Open boundary-challenge authority only from both sealed prior committee results; migrate and prove it.
6. Open Guildmaster synthesis authority only from all three sealed committee results; migrate and prove it.
7. Preserve the existing final profession-determination contract and its Garrison-only next authority.
8. Remove the four direct Guildhall injections and definitions only after the full route and hostile proof are claim-bound.
9. Update the executable inventory from **6** to **2** without changing either credential-bearing platform.

## Batch 14A checkpoint

The current Guildhall route is inventoried without changing runtime behavior. The next bounded implementation is the disciplinary-fit native authority and resolver only. Composition, boundary challenge, Guildmaster synthesis, Curia, La Cortine, and global platform removal remain unopened.
