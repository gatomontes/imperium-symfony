# Citadel Officer model assignments

## Status

Planned.

## Core rule

Citadel Officers are standing, mission-independent residents of Imperium. They perform their office functions across Citadels and missions. They are not created as mission-specific operatives.

Their model assignment belongs to the Officer's standing office Profile and must be selected according to the Officer's jurisdiction and permanent job criteria.

## Model assignment flow

1. Oracle maintains the available-model catalogue.
2. Augur evaluates candidate models against the Officer's standing office criteria.
3. The appropriate authority assigns an approved model to the Officer Profile.
4. The model binding, version, configuration, limits, fallback policy and provenance are sealed.
5. The Officer uses that assignment while performing the office function.

Examples:

- Seneschal: high-capability orchestration and reasoning model.
- Augur: research and evaluation model.
- Notary: structured extraction and evidence model.
- Artificer: coding and implementation model.
- Chronicler: documentation and summarization model.
- Bailiff: primarily deterministic security controls, with cognition only where required.
- Sortie Officer: narrowly selected task model.

## Mission boundary

A Citadel mission may impose additional requirements on an Officer, but it may not silently redefine the Officer's identity, jurisdiction or model.

If the standing assignment is insufficient, the permitted responses are:

- invoke an alternate model already authorized by the Officer Profile;
- create a bounded temporary Profile variant through proper authorization;
- request an authorized Officer or model reassignment.

Curia may request a mission-specific reassessment, but does not casually replace the Officer's standing model.

## Doctrine

> Citadel Officers are mission-independent. Their model assignments belong to their standing office Profiles. Missions may impose additional requirements, but may not silently redefine the Officer.

Oracle therefore maintains Imperium's cognitive staffing table:

`Officer → Office function → Required capability → Approved model → Constraints`

Model capability does not confer operational authority, and mission context does not by itself confer authority to substitute a model.
