import { createHash } from 'node:crypto';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const issuedAt = '2026-08-11T00:00:00-04:00';
const definitions = [
  ['guildmaster', 'guildhall.guildmaster', 'offices/guildhall/profile-guildmaster.md'],
  ['committee-disciplinary-fit', 'guildhall.committee.disciplinary-fit', 'offices/guildhall/profile-committee-disciplinary-fit.md'],
  ['committee-composition', 'guildhall.committee.composition', 'offices/guildhall/profile-committee-composition.md'],
  ['committee-boundary-challenge', 'guildhall.committee.boundary-challenge', 'offices/guildhall/profile-committee-boundary-challenge.md'],
];

const canonical = value => JSON.stringify(sort(value));
const sort = value => Array.isArray(value) ? value.map(sort) : value && typeof value === 'object'
  ? Object.fromEntries(Object.keys(value).sort().map(key => [key, sort(value[key])])) : value;
const digest = value => `sha256:${createHash('sha256').update(typeof value === 'string' ? value : canonical(value)).digest('hex')}`;
const seal = (value, field) => ({ ...value, [field]: digest(value) });

for (const [name, seat, source] of definitions) {
  const definitionId = seat;
  const version = '1.0.0';
  const body = {
    contract_version: '1.0.0', definition_id: definitionId, definition_version: version,
    artifact_class: 'officer_profile_definition', steward: { kind: 'office', id: 'guildhall' },
    target: { kind: 'seat', id: seat },
    source: { path: source, content_digest: digest(await readFile(resolve(root, source), 'utf8')) },
    qualification_contract: {
      contract_id: `qualification.${seat}.v1`,
      criteria: ['exact definition and Profile installation', 'declared jurisdiction restraint', 'version and provenance preservation'],
    },
    limitations: ['not a Profile', 'not installable', 'grants no Seat or mission authority'],
    digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] },
  };
  const definition = seal(body, 'content_digest');
  const ref = { definition_id: definitionId, definition_version: version, content_digest: definition.content_digest };
  const correlation = `guildhall-${name}-definition-lifecycle`;
  const approvalId = `${definitionId}.definition.approved.1`;
  const approval = seal({
    contract_version: '1.0.0', attestation_id: approvalId, definition_ref: ref,
    transition: { to: 'approved' }, actor: { kind: 'imperator', id: 'imperator-development-root' },
    issued_at: issuedAt, correlation_id: correlation, reason: 'Initial Guildhall Profile Definition approved for development runtime',
  }, 'record_digest');
  const current = seal({
    contract_version: '1.0.0', attestation_id: `${definitionId}.definition.current.1`, definition_ref: ref,
    transition: { from: 'approved', to: 'current', prior_attestation_id: approvalId },
    actor: { kind: 'office_mechanic', id: 'guildhall.profile-registry' }, issued_at: issuedAt,
    correlation_id: correlation, reason: 'Initial approved Guildhall Profile Definition designated current',
  }, 'record_digest');
  const directory = resolve(root, 'offices/guildhall/profile-definitions');
  await mkdir(directory, { recursive: true });
  await writeFile(resolve(directory, `${name}.json`), `${canonical(definition)}\n`);
  await writeFile(resolve(directory, `${name}.approved.json`), `${canonical(approval)}\n`);
  await writeFile(resolve(directory, `${name}.current.json`), `${canonical(current)}\n`);
}
