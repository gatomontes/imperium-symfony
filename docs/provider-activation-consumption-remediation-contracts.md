# Provider Activation-Consumption Remediation — Batch 1 contracts

## Result

BATCH_1_V2_COMBINED_ADMISSION_AND_REVOCATION_CONTRACTS_DEFINED

Batch 1 defines two authority-empty contracts:

1. GovernedProviderExecutionCombinedAdmissionContract, schema v2; and
2. ProviderBindingActivationRevocationContract, schema v1.

No producer or consumer is implemented.

## Combined admission v2

The v2 admission is separately versioned because v1 cannot gain required activation-consumption
truth without breaking its canonical field set. It adds activation_consumption beside the existing
authority_consumption and commits both before credential resolution and external I/O.

The activation consumption binds the exact activation ID and digest, single-operation truth,
consumed and non-continuing posture, activation-keyed winner scope, absence of an append-only
revocation record at admission, and the exact time that absence was checked.

The v2 checkpoint is
ACTIVATION_AND_AUTHORITY_CONSUMED_EFFECT_START_PRE_RESOLUTION_PRE_IO.

## Append-only revocation fact

The activation record is immutable, so remediation does not pretend its status can be mutated to
REVOKED. ProviderBindingActivationRevocationContract defines one append-only revocation fact keyed
to the exact activation reference.

Admission and a future revocation producer must share the activation-keyed lock prefix
governed-provider-execution-admission:. Under that scope, either the revocation fact exists and first
admission refuses, or the combined admission winner exists and later revocation cannot erase the
already committed local effect-start.

The contract defines structure only. It does not create revocation authority or a producer.

## Existing evidence

GovernedProviderExecutionAdmissionContract v1 is unchanged. Existing v1 records remain immutable
historical evidence and are not valid corrected combined winners.

## Non-authorities

Neither contract issues or consumes an activation or execution authority, activates a principal or
binding, handles credentials, invokes a provider, performs external I/O, authorizes retry, opens Iron
Gate or Lazaretto, or grants continuing authority.

## Batch 2 gate

Only Batch 2 may next be considered: implement the activation-keyed v2 admission producer and the
minimum activation-revocation fact producer under the shared lock. No credential-resolution
consumer migration or provider effect is authorized in Batch 2.
