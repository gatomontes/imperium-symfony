import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const check = process.argv.includes('--check');
const sort = value => Array.isArray(value) ? value.map(sort) : value && typeof value === 'object' ? Object.fromEntries(Object.keys(value).sort().map(key => [key, sort(value[key])])) : value;
const canonical = value => JSON.stringify(sort(value));
const digest = value => `sha256:${createHash('sha256').update(typeof value === 'string' ? value : canonical(value)).digest('hex')}`;
const seal = (value, field) => ({ ...value, [field]: digest(value) });
const sourcePath = 'offices/garrison/personas/constable.md';
const definitionPath = 'offices/garrison/profile-definitions/constable.json';
const directory = 'offices/garrison/canonical-staff';
const fileDigest = async path => digest(await readFile(resolve(root, path), 'utf8'));
const emit = async (path, value) => {
  const content = `${canonical(value)}\n`;
  if (check) {
    if (await readFile(resolve(root, path), 'utf8') !== content) throw new Error(`STALE_GARRISON_CONSTABLE_ARTIFACT ${path}`);
  } else await writeFile(resolve(root, path), content);
};

await mkdir(resolve(root, directory), { recursive: true });
const definitionBase = JSON.parse(await readFile(resolve(root, definitionPath), 'utf8'));
delete definitionBase.content_digest;
delete definitionBase.digest_spec;
const definition = seal({ ...definitionBase, digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] } }, 'content_digest');
await emit(definitionPath, definition);
const definitionRef = { definition_id: definition.definition_id, definition_version: definition.definition_version, content_digest: definition.content_digest };
const definitionApproval = seal({ schema: 'imperium.profile-definition-attestation/v1', attestation_id: 'garrison.constable.definition.approved.1', definition_ref: definitionRef, transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' }, issued_at: '2026-08-12T00:00:00-04:00' }, 'record_digest');
const definitionCurrent = seal({ schema: 'imperium.profile-definition-attestation/v1', attestation_id: 'garrison.constable.definition.current.1', definition_ref: definitionRef, transition: { from: 'approved', to: 'current', prior_attestation_id: definitionApproval.attestation_id }, actor: { kind: 'office_mechanic', id: 'garrison.profile-registry' }, issued_at: '2026-08-12T00:00:00-04:00' }, 'record_digest');
await emit('offices/garrison/profile-definitions/constable.approved.json', definitionApproval);
await emit('offices/garrison/profile-definitions/constable.current.json', definitionCurrent);

const persona = { schema: 'imperium.persona/v1', persona_id: 'garrison.canonical.constable', persona_version: '1.0.0', source: { path: sourcePath, content_digest: await fileDigest(sourcePath) }, admission: { state: 'admitted', authority: 'garrison.constable', evidence_record: 'garrison-canonical-constable-admission-1' } };
const personaPath = `${directory}/constable.persona.json`;
await emit(personaPath, persona);
const profile = seal({ contract_version: '1.0.0', profile_id: 'garrison.constable', profile_version: '1.0.0', artifact_class: 'officer', source_persona: { persona_id: persona.persona_id, persona_version: persona.persona_version, persona_digest: await fileDigest(personaPath), admission_state: 'admitted', evidence_record: persona.admission.evidence_record }, steward: { kind: 'office', id: 'garrison' }, target: { kind: 'seat', id: 'garrison.constable' }, transformation: { case_id: 'garrison-canonical-constable-profile', specification_version: '1.0.0', alchemist_disposition_id: 'laboratorium.garrison.constable.favorable.1' }, cognitive_payload: { persona_source: persona.source, profile_definition: definitionRef }, qualification_contract: definition.qualification_contract, limitations: ['canonical resident Constable only', 'no authority without valid Seat occupancy'], lineage: { derived_from: 'garrison.canonical.constable@1.0.0' }, digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] } }, 'content_digest');
const profilePath = `${directory}/constable.profile.json`;
await emit(profilePath, profile);
const profileRef = { profile_id: profile.profile_id, profile_version: profile.profile_version, content_digest: profile.content_digest };
const approval = seal({ contract_version: '1.0.0', attestation_id: 'garrison.constable.approved.1', profile_ref: profileRef, transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' }, issued_at: '2026-08-12T00:00:00-04:00', examination_disposition_id: 'garrison-canonical-constable-examination-favorable-1' }, 'record_digest');
const current = seal({ contract_version: '1.0.0', attestation_id: 'garrison.constable.current_active.1', profile_ref: profileRef, transition: { from: 'approved', to: 'current_active', prior_attestation_id: approval.attestation_id }, actor: { kind: 'office_mechanic', id: 'garrison.profile-registry' }, issued_at: '2026-08-12T00:00:00-04:00' }, 'record_digest');
await emit(`${directory}/constable.profile-approved.json`, approval);
await emit(`${directory}/constable.profile-current-active.json`, current);
const packageRecord = seal({ schema: 'imperium.garrison-canonical-constable-package/v1', package_id: 'garrison.canonical-constable', package_version: '1.0.0', steward: 'garrison', seat: 'garrison.constable', persona: { path: personaPath, content_digest: await fileDigest(personaPath) }, profile: { path: profilePath, content_digest: await fileDigest(profilePath) }, approval: { path: `${directory}/constable.profile-approved.json`, content_digest: await fileDigest(`${directory}/constable.profile-approved.json`) }, current_active: { path: `${directory}/constable.profile-current-active.json`, content_digest: await fileDigest(`${directory}/constable.profile-current-active.json`) }, definition: definitionRef, qualification_contract: definition.qualification_contract, limitations: ['not a manifestation', 'grants no spawning, Seat, inventory-response, or execution authority'] }, 'record_digest');
await emit(`${directory}/package.json`, packageRecord);
