# Canonical consumer correction - terminal inventory v2

Audited implementation: `7f1b634a37eb8f9d70058f4b18bcefc34e4ff22e`. Preparation v1 remains the historical baseline.
Classifications concern the exact bounded mechanism, not permission to execute a
provider. This inventory supersedes the implementation's intermediate A02 row.

## Final finding dispositions

| ID | Classification | Finding and evidence |
| --- | --- | --- |
| C00 | EXISTS_CANONICALLY | Campaign marker and all required readings preserved; operator authorized complete campaign. Historical prep restrictions remain historical. |
| C01 | EXISTS_CANONICALLY | Original binding producer and descriptor retain BOUND_INACTIVE, immutable semantics and canonical tool definition. |
| C02 | EXISTS_FRAGMENTED | The native command remains substrate-only evidence; it is not counted as downstream integration. |
| C03 | EXISTS_CANONICALLY | NativeBindingReader derives the exact instance/binding/operation root and verifies current stored transition proof. |
| C04 | EXISTS_CANONICALLY | forClaim derives the operation from the sealed stored claim and joins unique descriptor authorization id/digest; interpret compares descriptor scope operation. |
| C05 | EXISTS_CANONICALLY | Existing journal broker, journal start and direct adapter require native interpretation; ten competing effect readers are guarded. Archive-only readers are bounded separately. |
| C06 | EXISTS_CANONICALLY | D01-D10 cannot reuse native attempted/current/noncurrent roots. D11 remains explicit historical reconstruction. |
| C07 | EXISTS_CANONICALLY | D06/D07 input and cached admission guards precede return under shared native exclusion. Old winner identities remain unchanged. |
| C08 | EXISTS_CANONICALLY | D08/D09 input and cached proof guards precede current credential resolution or historical return. Native states cannot use the old proof path. |
| C09 | DEFERRED_BOUNDARY | agentmail-api-token and agentmail.api-key.v1 remain distinct legacy families; no relabeling or native credential rollout. Exact native binding is excluded before those paths. |
| C10 | EXISTS_CANONICALLY | Existing AgentMail command injects the established broker for read-only inspection; normal send remains refused. |
| C11 | EXISTS_CANONICALLY | Generic executor rejects email.send before IronGate dispatch and credentials because OutboundRequest has no binding root. |
| C12 | EXISTS_CANONICALLY | Direct AgentMail transport always refuses valid email before network construction; its network body was removed. |
| C13 | EXISTS_CANONICALLY | Claim retains historical pre-I/O semantics; effect-start journal now requires authoritative claim interpretation before publication or cached return. |
| C14 | EXISTS_CANONICALLY | Established journal broker invokes inspectClaim before admission, credential checkpoint, consumption and callback. Bound native outcomes never enter those cuts. |
| C15 | EXISTS_CANONICALLY | Direct callback adapter checks the stored journal/claim; request encoder checks the exact stored descriptor without schema-dispatch escape. |
| C16 | DEFERRED_BOUNDARY | Separate archival response/evidence chains retain original joins and no execution or retry authority; positive D11 container reconstruction preserved. |
| C17 | ABSENT | OutboundRequest still has no authoritative native root. Its email execution route is explicitly refused; no invented mapping or optional native flag is added. |
| C18 | DEFERRED_BOUNDARY | Generic IronGate/Lazaretto semantics remain separate and unavailable to email through the executor. No native effect grant or gate opening is introduced. |
| C19 | EXISTS_CANONICALLY | Production services.yaml registers NativeState and NativeBindingReader; the existing broker, command and adapter require their real dependencies. Other native producers remain excluded. |
| C20 | EXISTS_CANONICALLY | Real Kernel/container and Console Application prove current/noncurrent/inactive/corrupt/incomplete states, actual broker invocation refusal and all guarded readers. |
| C21 | EXISTS_CANONICALLY | Independent read-only NativeReconstructor verifies stored edges and separates historical receipts from currentness. No reconstruction issues permission. |
| C22 | EXISTS_CANONICALLY | Applicable legacy paths hold the existing native outer lock before old winner/immutable scopes; separate PHP process contention proves exclusion. |
| C23 | EXISTS_CANONICALLY | Pinned-grant migration remains its existing isolated retirement protocol. Native state and migration directories preclude unbound fallback; no old journal/store conversion occurs. |
| C24 | DEFERRED_BOUNDARY | Mission, Legate and Delegate cognition retain exact deepseek.model.invoke claim/broker/platform call graphs from E08. |
| C25 | DEFERRED_BOUNDARY | Sortie retains manifest/CAS and sortie.deepseek.model.invoke/http.get graph from E09; no native email interpretation or capability transfer is claimed. |
| C26 | DEFERRED_BOUNDARY | http.post.json transport and inbound verifier/InboundLazaretto/persistOnce remain distinct demonstrated paths, unchanged. |
| C27 | EXISTS_CANONICALLY | Stored claim/issuance -> unique original descriptor -> native stored proof -> established pre-effect decision is acyclic and mandatory. No new producer or mutable projection. |
| C28 | EXISTS_CANONICALLY | Application and service negatives cover substitution, ambiguity, corruption, cached-result bypass, expiry/revocation and interruption; separate processes cover native-versus-legacy contention. |
| C29 | DEFERRED_BOUNDARY | Live provisioning, authority/state changes, credentials, provider effects, retry, Iron Gate and Lazaretto remain excluded. All mutating proof used disposable fixtures. |
| C30 | DEFERRED_BOUNDARY | Evidence is bounded to cooperative single-host PHP/flock/filesystem operation; distributed locks, hostile replacement, physical power loss and cross-process capabilities remain unproved. |

## Every inventoried reader and corridor

Exact file paths and callers remain in the preparation inventory/reading ledger.
The final treatment below preserves those identities.

| ID | Existing boundary and final treatment | Classification |
| --- | --- | --- |
| D01 | ProviderBindingActivationDecisionService::decide guards claim/descriptor reads before caller consumption, under native outer lock. | EXISTS_CANONICALLY |
| D02 | ProviderBindingActivationIssuanceService::issue guards the initial decision's nested binding before caller consumption. | EXISTS_CANONICALLY |
| D03 | SingleExecutionProviderBindingActivationService::activate guards authority binding before legacy winner logic. | EXISTS_CANONICALLY |
| D04 | SingleOperationProviderBindingActivationIssuanceService::activate guards candidate and stored references before consumption/publication. | EXISTS_CANONICALLY |
| D05 | DurableProviderExecutionAuthorityIssuanceService::issue guards candidate and references before consumption/publication. | EXISTS_CANONICALLY |
| D06 | GovernedProviderExecutionAdmissionService::admit guards initial authority before cached admission lookup. Cached results are also guarded before return (Batch 3A). | EXISTS_CANONICALLY |
| D07 | GovernedProviderExecutionCombinedAdmissionService::admit guards initial authority before cached activation-root winner lookup. Cached results are also guarded before return (Batch 3A). | EXISTS_CANONICALLY |
| D08 | GovernedStationaryCredentialResolutionService::prove guards initial admission before cached proof or environment access. Cached results are also guarded before return (Batch 3A). | EXISTS_CANONICALLY |
| D09 | GovernedStationaryCredentialResolutionV2Service::prove does the same for v2. No credential-family relabeling. Cached results are also guarded before return (Batch 3A). | EXISTS_CANONICALLY |
| D10 | ProviderBindingActivationRevocationAuthorityIssuanceService::issue guards candidate/references. Native revocation retains its separate Root-act route. | EXISTS_CANONICALLY |
| D11 | GovernedToolResultReconstructionService::reconstruct remains archival: normalized result to binding/raw/eligibility/tool; read_only=true, continuing_authority=false, no outgoing execution caller. | DEFERRED_BOUNDARY |
| A01 | ProviderBoundCredentialEligibilityService::assess guards supplied sealed binding inside native outer lock before publication. | EXISTS_CANONICALLY |
| A02 | AgentMailProviderRequestEncoder::encode requires exact stored descriptor validation when native state exists, under the shared lock; schema substitution cannot skip it. | EXISTS_CANONICALLY |
| A03 | ProviderNeutralRawEvidenceService::preserve stores archival bytes/joins; its product feeds A04, not execution admission. | DEFERRED_BOUNDARY |
| A04 | ProviderBoundEvidenceNormalizationService::normalize calls A05 and stores an archival result feeding normalized admission/D11. | DEFERRED_BOUNDARY |
| A05 | AgentMailProviderEvidenceDecoder::decode is a pure historical byte decoder called by A04; no effect edge. | DEFERRED_BOUNDARY |
| E01 | Native transition command remains substrate and is never counted as downstream proof. | EXISTS_FRAGMENTED |
| E02 | Existing retired AgentMail command calls established broker inspection, which reaches native interpretation; default send refusal preserved. | EXISTS_CANONICALLY |
| E03 | DeterministicBoundaryExecutor refuses email.send before dispatch/credentials. Direct AgentMailEmailTransport refuses a valid request before network construction; its old network body is removed. http.post.json is unchanged. | EXISTS_CANONICALLY |
| E04 | Stored claim mapping is mandatory before effect-start journal publication/replay, broker admission and direct adapter callback. Native state cannot enter the legacy callback path. | EXISTS_CANONICALLY |
| E05 | Deterministic stored response/result/receipt reconstruction retains historical joins, not native execution/retry authority. | DEFERRED_BOUNDARY |
| E06 | A03 to A04/A05 to normalized admission to D11 remains archival, with no outgoing execution edge. | DEFERRED_BOUNDARY |
| E07 | D01–D10 old descriptor activation/admission routes now guard exact native roots before cached returns, consumption or credentials. | EXISTS_CANONICALLY |
| E08 | Mission/Legate/Delegate cognition uses occupancy, dedicated claims/brokers and deepseek.model.invoke, with no email descriptor reader. | DEFERRED_BOUNDARY |
| E09 | Sortie uses manifest claims/journal, sortie.deepseek.model.invoke and http.get registry; no email interpretation. | DEFERRED_BOUNDARY |
| E10 | Profile smoke is a cognition fixture; generic http.post.json is separate; inbound webhook calls verifier/InboundLazaretto/persistOnce, with no outbound edge. | DEFERRED_BOUNDARY |
