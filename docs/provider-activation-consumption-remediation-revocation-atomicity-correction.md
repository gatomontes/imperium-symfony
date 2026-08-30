# Provider Activation-Consumption Remediation — Batch 4 atomicity correction

## Result

BATCH_4_REVOCATION_DUAL_WRITE_REFUSED_ATOMIC_WINNER_CONTRACT_DEFINED

Implementation review refused the planned two-record revocation producer before runtime mutation.

## Refused dual-write designs

Writing revocation-authority consumption first can crash after authority is spent but before the
activation is blocked. Admission could then win despite a consumed revocation authority.

Writing the revocation fact first can crash after admission is blocked but before complete
authority-consumption evidence exists. The revocation would have effect without its exact consumed
authority record.

An outer activation lock serializes concurrent processes but does not make two filesystem record
writes transactional across process death. Replay can repair some states, but the first durable fact
would already make an incomplete governance claim.

Therefore the separate ProviderBindingActivationRevocationContract and
ProviderBindingActivationRevocationAuthorityConsumptionContract are marked
DO_NOT_PRODUCE_SEPARATELY.

## Selected correction

ProviderBindingActivationRevocationWinnerContract defines one immutable activation-keyed record
containing:

- exact activation reference;
- exact revocation-authority reference;
- single-use, consumed, non-continuing authority consumption;
- admitted revocation reason;
- activation-keyed winner scope; and
- revocation time.

The one record is simultaneously the append-only revocation fact and the revocation-authority
consumption winner. Its deterministic ID and lock derive from the activation identity.

Admission and revocation production must share the same activation-keyed lock. Under that lock,
exactly one of these can become the first winner:

1. combined execution admission; or
2. authorized revocation winner.

## Preserved contracts

The Batch 3 revocation-authority artifact and decision/issuance contracts remain valid. They supply
the lawful authority that the future atomic winner consumes.

The earlier separate fact and consumption contracts remain historical design evidence. They are not
deleted or silently reinterpreted, but runtime production is explicitly prohibited.

## Preserved perimeter

Batch 4 defines no producer and changes no execution behavior. No authority is issued or consumed;
no activation, principal or binding is activated or consumed; no credential or capability is
handled; no provider is invoked; no external I/O occurs; no byte is sent; and Iron Gate and
Lazaretto remain closed.

## Next gate

Only remediation Batch 5 may next be considered: issue exact revocation authority and produce the
one-record authorized revocation winner under the shared activation-keyed lock. Stationary
credential-resolution migration remains unauthorized.
