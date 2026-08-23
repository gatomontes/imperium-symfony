# AgentMail deterministic email adapter

AgentMail is the first real provider bound to Imperium's mechanical outbound lane.

`email.send` remains deterministic: no sortie, no external cognition, and no model access to provider credentials. Iron Gate binds the exact AgentMail inbox endpoint and exact serialized message payload. `CredentialBroker` resolves `AGENTMAIL_API_KEY` only while the transport performs the one authenticated HTTPS POST. The provider's raw JSON receipt returns through Lazaretto and becomes an admitted external artifact.

Provider contract used by the adapter:

- `POST https://api.agentmail.to/v0/inboxes/{inbox_id}/messages/send`
- `Authorization: Bearer <AGENTMAIL_API_KEY>`
- JSON message payload with exact `to`, `subject`, `text`/`html`, and optional attachments
- attachments are base64 content with filename and content type
- successful receipt must contain `message_id` and `thread_id`

The live CLI command is intentionally explicit and side-effecting:

```powershell
$env:AGENTMAIL_API_KEY="..."
$env:AGENTMAIL_INBOX_ID="..."
php bin/console imperium:email:send-agentmail --to="recipient@example.com" --subject="Report" --text="Attached." --attachment="C:\path\report.pdf"
```

A successful send is admitted through Lazaretto and reports `sortie=NONE`, the admitted artifact ID, the receipt digest, and the raw AgentMail receipt.
