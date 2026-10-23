# Minimum Working Sample — implementation stages

**MWS-001 (this PR):** a provider-neutral synchronous Coordinator → Worker → Coordinator cooperation contract, with bounded input/output, attributed transcript, explicit failure and unit tests. No model, tools, DB state, or browser UI is asserted operational by this change.

**MWS-002:** implement a real Symfony AI / DeepSeek responder adapter and bounded test doubles. Preserve provider failure details in server logs only, never public responses.

**MWS-003:** persist sessions and ordered turns in PostgreSQL with Doctrine migrations; explicit retry state and replay-safe submission.

**MWS-004:** Twig browser interface displaying the full transcript, actual status, and failures. Authenticate or bind strictly to trusted local use before allowing access to stored conversation content. Test with WebTestCase.

**MWS-005:** one independently verified live run, then compare observed behavior with acceptance criteria.

No old institutional Office/Seat/Recruiter or authorization effects are imported. The design principles and evidence categories are in [Imperium Knowledge](https://github.com/gatomontes/imperium-knowledge).
