# Institutional Decision Integrity canonical contracts

## Batch 1 boundary

Batch 1 defines two separately versioned canonical contracts. It does not assemble, validate, seal, persist, replay, supersede, or adopt either contract. No existing service, lifecycle, checkpoint, actor, or authority consumer changes in this batch.

## Institutional decision surface

Schema: `imperium.institutional-decision-surface/v1`

The decision surface binds the decision owner and question; presented, unavailable, and prohibited options; plain-language option explanations; material consequences, risks, costs, external effects, and reversibility; Curia's separately attributable recommendation; exact evidence identity and relevance; the requested authority and authority not requested; limitations; expiry; the material-facts fingerprint; allowed explicit dispositions; and presentation time.

The surface is presentation evidence only. It cannot recommend by mere presentation, select, approve, authorize, transition a lifecycle, execute, or create continuing authority. Silence, inactivity, familiarity, and prior consent are expressly non-authorizing.

## Defensible decision record

Schema: `imperium.decision-record/v1`

The record binds the seven defensibility elements: decision, decision owner, options considered, risks, evidence relied on, substantive rationale, and the distinct UTC decision time. It additionally binds the instance and proceeding, source surface and requests, prior decisions, limitations, expiry, downstream authority lineage, supersession lineage, sealing state, and canonical digest.

The decision states the disposition, exact decided scope, granted authority, denied authority, resulting state, and everything remaining unauthorized. Each residual risk binds its treatment, competent owner, and acceptance disposition. The record summarizes and binds its underlying proceeding without replacing it, inventing rationale, selecting an option, widening authority, or rewriting a sealed historical artifact.

## Deferred work

Later batches own material-change invalidation and the first bounded lifecycle adoption.

## Batch 2 mechanical enforcement

Batch 2 adds fail-closed validators and one canonical immutable store for the two contracts. Required top-level and nested fields are mechanically enforced. Evidence must be sealed, exactly digest-identified, observed no later than the decision surface or decision, and unexpired at that moment. Context-free authorization prompts fail. Substantive rationale is mandatory. A remaining residual risk requires an explicitly named owner whose competent authority is stated and an explicit acceptance disposition.

The store digest-seals each canonical artifact, returns the exact artifact for identical replay, rejects conflicting reuse of an identity, verifies a decision's exact surface lineage, and verifies any superseded decision by identity, digest, instance, and proceeding. Supersession creates a new immutable record and never rewrites history.

These mechanisms remain unadopted infrastructure. They do not assemble a decision surface, detect an omitted option, change material-fact policy, invoke cognition, select an option, issue authority, consume authority, or move an existing lifecycle checkpoint.

## Batch 3 mechanical assembly

Batch 3 defines two sealed assembly sources: `imperium.decision-surface-option-universe/v1` and `imperium.decision-surface-presentation-directive/v1`. Curia remains responsible for discovering and explaining alternatives, classifying their material relevance and availability, and authoring its recommendation. Machinery verifies both source digests and their common instance, proceeding, and lineage before constructing the canonical surface.

Every materially relevant option in the sealed universe must appear exactly once as presented, unavailable, prohibited, rejected, or unexamined. Duplicate classification, false availability classification, or omission from every category fails stopped. Explanations, consequences, risks, reversibility, authority effects, evidence references, the decision question, and Curia's recommendation are copied from the sealed sources rather than generated or reinterpreted by machinery.

Assembly computes a deterministic source-bound material-facts fingerprint and immutable surface identity, combines only exact sealed unexpired evidence, and persists the surface through the Batch 2 store. The resulting authorization state is always decision-pending, non-inferred, and non-authorizing. Silence, inactivity, familiarity, and prior consent remain explicitly incapable of granting authority.

The assembler is still unadopted infrastructure. It does not present a surface inside an existing lifecycle, receive an Imperator response, create a decision record, grant authority, or change any existing checkpoint or competent actor.
