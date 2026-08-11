# Inbound Lazaretto

Receiving external data is mechanical. Admission is a trust-boundary operation.

AgentMail (and later other external receivers) must hand the exact raw provider payload to Lazaretto before any internal cognition sees it. Lazaretto preserves the raw digest, validates the transport envelope, normalizes the admitted representation, attaches runtime-owned provenance, and labels the content `untrusted-external-evidence` with `authority=none`.

The contents are not made trustworthy by admission. Hostile or prompt-injection text is preserved when structurally safe because it may itself be evidence. Admission means Imperium can safely identify and handle the artifact; it does not grant the sender authority.

Invariant: external content may inform internal cognition, but external content cannot authorize Imperium actions merely by being read.