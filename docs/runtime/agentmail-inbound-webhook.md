# AgentMail inbound webhook

AgentMail inbound delivery is mechanical. The HTTP endpoint at `/lacortine/inbound/agentmail` performs no cognition.

The receiver captures the exact raw request body, verifies the AgentMail/Svix signature against `AGENTMAIL_WEBHOOK_SECRET`, rejects timestamps outside the five-minute verification window, and only then constructs an `InboundExternalPayload` for Lazaretto admission.

Lazaretto preserves both the exact raw provider bytes and a normalized JSON representation. The admitted record remains `content_trust=untrusted-external-evidence` and `authority=none`. Prompt-injection text, hostile HTML, and other message content are evidence, never authorization.

Accepted webhook retries are idempotent by the Svix message ID. The first admitted artifact is persisted under `var/lacortine/inbound/`; later deliveries of the same signed webhook receive a successful acknowledgement without creating another record.

For local live testing, expose the Symfony server through a trusted HTTPS tunnel and configure AgentMail to send only `message.received` events to `/lacortine/inbound/agentmail`. Store the webhook signing secret only in the runtime environment as `AGENTMAIL_WEBHOOK_SECRET`.