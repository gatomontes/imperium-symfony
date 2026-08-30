# Provider Activation-Consumption Remediation Batch 2 complete

## Result

BATCH_2_ACTIVATION_KEYED_COMBINED_ADMISSION_PRODUCED_REVOCATION_WRITER_REFUSED

One activation-keyed immutable v2 admission now commits activation consumption, durable-authority
consumption and local effect-start together. Exact replay reconstructs. A second authority for the
same activation refuses under the same winner before credential resolution.

A deterministic activation-revocation fact blocks first admission. No revocation writer was added:
the revocation contract names a source authority, but no canonical contract yet defines or consumes
that authority. Self-authorizing revocation production is refused.

## Next gate

Only remediation Batch 3 may next be considered: define the exact activation-revocation authority
decision/issuance and consumption contracts. No revocation producer or stationary-resolution
migration is authorized by this result.

No credential or capability was handled; no principal or binding was activated; no provider was
invoked; no external I/O occurred; no outbound byte was sent; and Iron Gate and Lazaretto remain
closed.

Estimated remediation countdown is revised to four batches because the revocation-authority gap
must be closed before lawful revocation production.
