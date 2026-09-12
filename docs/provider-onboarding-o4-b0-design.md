# O4-B0 internal design

The existing FormationJournal remains the only persistence and locking owner.
CommandLedger remains the only producer of command, step and slot consumption.
There is no operational service registration and no provider call from assessment
resolution, selection, application, replay or revalidation.

The first implementation seam is an original-backed assessment resolver. Its
public entry accepts an AuthorityStore and an exact policy reference, reads only
through that store's journal inspect boundary, and returns the three ordered
current assessments. It accepts no caller-supplied state, response, outcome,
success flag or selection. Its internal projection contains exactly policy,
groups and lineage; each group contains outcome, claim, retained checkpoint,
frozen input and a typed ParsedResponse. It is transient evidence, not an H
assessment view and never application or invocation authority. Bounds are the
existing 3 groups, 4 attempts per group, 256 evidence refs, 1 MiB operation and
response limits, and existing bounded state/source validation.

AugurAdapter will expose a narrow typed validation method using the same current
context, exact operation, tariff/token evidence, provider response mapping and
semantic evidence verifier as its existing response classification method. The
classification method retains its terminal-failure behavior for semantic refusal.
No parsing result caches authority or currentness. An application must resolve
all W1/W2/W3 originals and cannot infer verified fitness from ParsedResponse alone.

The intended persistent extension is explicit authority-state/v4, with a retained
v3 migration predecessor, exact original-map preservation and bounded immutable
applications and assessment_views. Existing v1/v2/v3 behavior remains unchanged
until that extension and its structural validator are complete. A partially
implemented writer must remain absent; accepting an unsupported state version or
loosening StateValidation to admit unverified application records is forbidden.

The planned assessment view and application receipt use exactly the closed H
bodies in the assignment-application contract. A complete transition must retain
both tuples, old/new generations, exact predecessor head, view, receipt and
command/step/slot consumption together. Exact replay must precede live checks.
The selected policy controls A versus B; a derived view cannot create a signed
act. Explicit replacement needs a fresh whole-pair authority and unchanged or
independently reassessed scope. The formation consumer must check persisted
settings alongside its existing personnel, session and lease checks, and refuse
unavailability without substitution. These writer/consumer seams are not
implemented by the transient resolver alone.
