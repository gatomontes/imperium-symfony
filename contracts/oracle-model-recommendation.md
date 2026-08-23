# Oracle model recommendation

Augur consumes exactly one assessment-bound recommendation authority to issue `RECOMMEND_MODEL`, `NO_UNIQUE_RECOMMENDATION`, or `RECOMMEND_NONE`. A recommendation names one exact eligible model only when applicable, answers every unchanged commission criterion, records each candidate's advantages, disadvantages, limitations, contradictions, and recommendation role, and explains why alternatives were not recommended.

Recommendation expresses evidence-bound preference, not selection. It may not create an ordinal ranking, assign a model, mutate a Profile, invoke a provider, deploy, or execute. Sealing consumes Augur's recommendation authority and opens one single-use Curia selection-decision authority permitting Curia to select an eligible model, reject all, or return a new commission with recorded reasons.

The normal checkpoint is `ORACLE_MODEL_RECOMMENDATION_SEALED_PENDING_CURIA_SELECTION_DECISION`.
