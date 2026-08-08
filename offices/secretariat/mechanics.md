---
inherits: [./doctrine.md]
---
# Secretariat Mechanics

These functions preserve and transport operator-facing material. They do not interpret answers or decide what to ask.

## register-intake
Preserve exact authenticated submission, source, time, and correlation; output an intake receipt and Mission Dossier reference.

## present-question
Expose one exact competent-Seat question unchanged; output a presentation receipt and active-question cursor.

## record-answer
Preserve the raw response and bind it to the exact question and dossier; output an answer receipt and return envelope.

## relay-disposition
Transmit an exact competent-Office disposition unchanged; output a relay receipt.

## package-delivery
Bind the exact authorized artifact, provenance, format, recipient, and authority into an immutable Delivery Package.

## deliver-package
Transmit a valid Delivery Package and record delivery or bounded failure.

Every function fails closed on missing authority, source, identity, integrity, recipient, cursor, or correlation.
