# PPC10 additive contract and owner inventory

This candidate is a component implementation with the v5 shared-exposure extension held for source review. It does not admit a staged scope or implement application/use. See `provider-staged-assignment-source-incompatibility.md` and the final handoff report for validation/disposition.

## Executable contracts

- `StagedAct` authenticates the separate `IMPERIUM_OPERATOR_STAGED_ASSIGNMENT_V1` domain against the current original enrolled Operator and founding policy. Only exact scope authorization and application effects are recognized. It verifies canonical Ed25519 payloads, full object hash, identity, policy/source currentness, competence and intervals. It does not consume authority or check a mutation head; an eventual admission owner must do that atomically. Existing `Act` and `Admission` are unchanged and reject the new domain.
- `StagedScope` performs pure closed body/tuple/commission decoding against the original founding policy, with generation 1–4, original candidates/workload/requirements/mode/selection, two ordered Seats, seven fitness references, non-expanded bindings and bounded exact pairs. It does not verify live designation, appointments, current fitness, source closure, migration or budget accounting. It must not be used as an admission verifier on its own.
- `StagedApplicationContract` checks separate terms and receipt shapes, exact reference domains, complete ordered setting pairs and consecutive setting generations. It supplies no assessment selection or application owner. Assessment/input/outcome decoders and producers remain open.
- `ProfileFitnessContract` is the closed C2 decoder: exact seven obligation IDs/roles/meaning strings copied from the approved proposal, exact eight ordered exercise duties, scoped delegations, signed judgments, finite references/rationale/record bounds. Native model-seal references retain their original unprefixed digest inside the correspondence tuple. An H source reference to that original uses a `sha256:` digest; the original tuple is not rewritten.

## Native C2 owner

`FormationProfileFitness` is explicitly constructed with one AuthorityStore and the existing Formation signatures, current institutional actor resolver and personnel chain owner (which verifies the complete model candidate). No production service registration or Composition change is made.

Preparation verifies the complete current PPC5 candidate chain and model correspondence, and links the exact founding requirements/workload, native Profile, complete qualification and seal/configuration/binding originals. Preparation grants no authority. The separately signed `DELEGATE_PROFILE_FITNESS` decision permits exactly one actor/role/Seat/Profile/contract/key/time scope. Personnel delegation alone is not fitness power. Current R2/R4 actor resolution runs under the live same-root Formation owner.

The owner retains contracts, delegations, judgment envelopes, public exercises and terminal revocations in a new `profile_fitness` journal subtree. All mutations use the unchanged FormationJournal publication point; no external I/O/cognition occurs in a mutation callback. There is no second incumbent registry. Contract and judgment ingress retain raw JSON and verify raw-to-decoded equality on reads. Array-valued native decisions and delegation terms retain their exact signed canonical objects. Private signing keys are never accepted or retained by the runtime.

Every judgment independently verifies its role, exact scope, Ed25519 signature, current delegation/actor, original candidate chain, native model seal and cited dependencies. All seven obligations must be present exactly once for verification; FAIL/UNKNOWN blocks verification. A practice PASS requires all eight exercise results PASS, correct exact Profile/binding/workload, nonempty returns and exact allowed evidence. These are institutional judgments; code does not establish the truth of prose or synthetic semantic results.

`REVOKE_PROFILE_FITNESS_DELEGATION` names the exact retained delegation digest and expected head. It is signed by the current Formation trust owner. Withdrawal is terminal and retained. Exact completed record/revocation replay returns historical recognition without current authority or another journal publication. A different key/delegation cannot create a latest-wins second obligation for the same contract; a new exact contract is needed for a new set of judgments.

Each new map is bounded at 256 originals, each contract/judgment/delegation/decision/exercise at 256 KiB, rationale at 8,192 UTF-8 bytes, judgment references at 32. Exercise coverage equals the fixed eight; the 64-duty upper bound cannot authorize a shortened or extra list. Combined fitness/personnel/model/designation storage is bounded at 32 MiB. Complete integration with all existing referenced-history accounting and saturation proofs remains an open integrated obligation; no C2-wide completion is claimed.

## Exercise mapping

The unchanged public workload's W2/W3 instruction, system and result contracts require the first six exercise duties: exact binding, mandatory predicate evidence, contradictions/unknowns, capacity tiers, frozen-input attribution, and no appointment/invocation. `seat_authority_boundary` and `bounded_return` are explicit C2 additions. The compiled obligation text is byte-checked against the immutable proposal in the tests. Exercises exported by this campaign use deliberately synthetic observed returns and limitations. They prove record/signature/owner mechanics, not measured capability or real workload execution.

## Unchanged boundaries

Every pre-existing tracked file is intended to remain byte-identical. Generic Profile schemas, all historical fixtures, original founding/R4 owners, the selection algorithm, persistence writers, Composition, Composer configuration/lock, CI and guards are untouched. No new public authority interface silently accepts staged meaning. No installed state, account/provider access, enrollment, commissioning or live execution is performed.
