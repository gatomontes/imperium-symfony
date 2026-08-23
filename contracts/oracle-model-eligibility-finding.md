# Oracle model eligibility finding

Augur consumes one case-bound, single-use authority to issue an evidence-bound `ELIGIBLE`, `INELIGIBLE`, or `INDETERMINATE` finding for one exact provider/model/version. Every rubric criterion is answered and cites only claims and sources frozen into the evaluation case. Missing or conflicting evidence may not be interpreted favorably.

Findings grant judgment authority only: they do not rank, recommend, select, assign, mutate a Profile, invoke a provider, deploy, or execute. When all candidate authorities are consumed, Oracle seals an eligibility-phase result. At least one eligible candidate proceeds to comparative assessment; none returns `NO_ELIGIBLE_MODEL` to Curia.

Curia may then issue an explicit `DEFAULT_MODEL_FALLBACK_ORDER` naming an exact candidate and acknowledging its failed or unproven criteria. The order never changes the Oracle finding and grants only fallback-verification authority. An inaccessible, inadmissible, or forbidden model cannot be ordered through this route.
