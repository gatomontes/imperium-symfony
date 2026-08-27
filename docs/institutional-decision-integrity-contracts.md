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

Batch 2 owns mechanical validation, digest sealing, immutable persistence, replay, conflicting-reuse refusal, and supersession enforcement. Later batches own assembly, omission detection, material-change invalidation, and the first bounded lifecycle adoption.
