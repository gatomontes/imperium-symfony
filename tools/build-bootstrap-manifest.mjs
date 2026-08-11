import { createHash, generateKeyPairSync, sign } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const digest = path => createHash('sha256').update(readFileSync(resolve(root, path))).digest('hex');
const record = (artifact, version = '1.0.0') => ({ artifact: `/${artifact}`, version, digest: digest(artifact) });
const canonical = value => {
  if (Array.isArray(value)) return `[${value.map(canonical).join(',')}]`;
  if (value && typeof value === 'object') return `{${Object.keys(value).sort().map(key => `${JSON.stringify(key)}:${canonical(value[key])}`).join(',')}}`;
  return JSON.stringify(value);
};

const profileDefinitions = {
  'provisional-recruiter': { persona: 'recruiter', id: 'conscription.recruiter.provisional', steward: 'conscription', seat: 'conscription.recruiter', source: 'offices/conscription/profile-recruiter.md', limitations: ['succession-only', 'one ordinary Recruiter successor'] },
  'ordinary-recruiter': { persona: 'recruiter', id: 'conscription.recruiter.ordinary', steward: 'conscription', seat: 'conscription.recruiter', source: 'offices/conscription/profile-recruiter.md', limitations: [] },
  seneschal: { persona: 'seneschal', id: 'curia.seneschal', steward: 'curia', seat: 'curia.seneschal', source: 'offices/curia/profile-seneschal.md', limitations: [] },
  chamberlain: { persona: 'chamberlain', id: 'curia.chamberlain', steward: 'curia', seat: 'curia.chamberlain', source: 'offices/curia/profile-chamberlain.md', limitations: [] },
  secretary: { persona: 'isolde', id: 'curia.secretary', steward: 'curia', seat: 'curia.secretary', source: 'offices/curia/profile-secretary.md', limitations: ['provisional-curial-assignment'] },
};
for (const [name, definition] of Object.entries(profileDefinitions)) {
  const personaPath = `bootstrap/artifacts/${definition.persona}-persona.json`;
  const profile = {
    contract_version: '1.0.0', profile_id: definition.id, profile_version: '1.0.0', artifact_class: 'officer',
    source_persona: { persona_id: `primordial.${definition.persona}`, persona_version: '1.0.0', persona_digest: `sha256:${digest(personaPath)}`, admission_state: 'admitted', evidence_record: 'primordial-charter-declaration-1' },
    steward: { kind: 'office', id: definition.steward }, target: { kind: 'seat', id: definition.seat },
    transformation: { case_id: `primordial-${name}-profile`, specification_version: '1.0.0', alchemist_disposition_id: 'primordial-charter-mechanical-transformation-1' },
    cognitive_payload: { source: `/${definition.source}`, source_digest: `sha256:${digest(definition.source)}` },
    qualification_contract: { contract_id: `qualification.${definition.id}.v1`, criteria: ['exact Profile installation', 'declared authority restraint', 'version and provenance preservation'] },
    limitations: definition.limitations, lineage: { derived_from: `primordial.${definition.persona}@1.0.0` },
    digest_spec: { algorithm: 'sha256', canonicalization: 'rfc8785', omitted_fields: ['content_digest'] }, content_digest: '',
  };
  const digestibleProfile = { ...profile };
  delete digestibleProfile.content_digest;
  profile.content_digest = `sha256:${createHash('sha256').update(canonical(digestibleProfile)).digest('hex')}`;
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile.json`), `${canonical(profile)}\n`);
  const makeAttestation = (transition, prior = undefined) => {
    const body = { contract_version: '1.0.0', attestation_id: `${definition.id}.${transition}.1`, profile_ref: { profile_id: definition.id, profile_version: '1.0.0', content_digest: profile.content_digest }, transition: { ...(prior ? { from: prior, prior_attestation_id: `${definition.id}.${prior}.1` } : {}), to: transition }, actor: { kind: transition === 'approved' ? 'imperator' : 'seat', id: transition === 'approved' ? 'imperator-development-root' : definition.seat }, issued_at: '2026-08-08T00:00:00-04:00', correlation_id: `primordial-${name}-lifecycle`, reason: 'Primordial development composition', record_digest: '' };
    const digestibleAttestation = { ...body };
    delete digestibleAttestation.record_digest;
    body.record_digest = `sha256:${createHash('sha256').update(canonical(digestibleAttestation)).digest('hex')}`;
    return body;
  };
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile-approved.json`), `${canonical(makeAttestation('approved'))}\n`);
  writeFileSync(resolve(root, `bootstrap/artifacts/${name}-profile-current-active.json`), `${canonical(makeAttestation('current_active', 'approved'))}\n`);
}

const { publicKey, privateKey } = generateKeyPairSync('ed25519');
const rawPublicKey = publicKey.export({ format: 'der', type: 'spki' }).subarray(-32);
const keyId = 'imperator-development-root-1';
const payload = {
  charter_generation: 'charter-development-1',
  instance_class: 'development',
  issued_at: '2026-08-08T00:00:00-04:00',
  expires_at: null,
  trust: {
    signature_policy: 'imperium.launch-policy/development-v1',
    accepted_signers: [{ key_id: keyId, public_key: rawPublicKey.toString('base64'), public_key_digest: createHash('sha256').update(rawPublicKey).digest('hex') }],
    revocation_snapshot: { source_id: 'charter-development-root', generation: '1', digest: createHash('sha256').update('charter-development-root|1|valid').digest('hex'), valid_at: '2026-08-08T00:00:00-04:00' },
  },
  launcher: record('src/Bootstrap/Launcher.php'),
  mastermason: { ...record('src/Bootstrap/MasterMason.php'), compatible_charter_generation: 'charter-development-1' },
  runtime: {
    bootstrap_state: record('src/Bootstrap/BootstrapState.php'),
    canonical_json: record('src/Bootstrap/CanonicalJson.php'),
    manifest_validator: record('src/Bootstrap/ManifestValidator.php'),
    state_store: record('src/Bootstrap/StateStore.php'),
    validation_exception: record('src/Bootstrap/ValidationException.php'),
    validation_receipt: record('src/Bootstrap/ValidationReceipt.php'),
  },
  primordial: {
    constitutional_doctrine: record('imperium-doctrine.md'),
    mission_planning_contract: record('contracts/mission-planning.md'),
    charter: record('bootstrap/artifacts/charter.json'),
    personas: {
      recruiter: record('bootstrap/artifacts/recruiter-persona.json'),
      seneschal: record('bootstrap/artifacts/seneschal-persona.json'),
      chamberlain: record('bootstrap/artifacts/chamberlain-persona.json'),
      isolde: record('bootstrap/artifacts/isolde-persona.json'),
    },
    offices: {
      conscription: record('offices/conscription/doctrine.md'),
      curia: record('offices/curia/doctrine.md'),
    },
    seats: {
      provisional_recruiter: record('bootstrap/artifacts/provisional-recruiter-seat.json'), ordinary_recruiter: record('bootstrap/artifacts/ordinary-recruiter-seat.json'),
      seneschal: record('bootstrap/artifacts/seneschal-seat.json'),
      chamberlain: record('bootstrap/artifacts/chamberlain-seat.json'),
      secretary: record('bootstrap/artifacts/secretary-seat.json'),
    },
    profiles: {
      provisional_recruiter: { ...record('bootstrap/artifacts/provisional-recruiter-profile.json'), attestations: { approval: record('bootstrap/artifacts/provisional-recruiter-profile-approved.json'), current_active: record('bootstrap/artifacts/provisional-recruiter-profile-current-active.json') } },
      ordinary_recruiter: { ...record('bootstrap/artifacts/ordinary-recruiter-profile.json'), attestations: { approval: record('bootstrap/artifacts/ordinary-recruiter-profile-approved.json'), current_active: record('bootstrap/artifacts/ordinary-recruiter-profile-current-active.json') } },
      seneschal: { ...record('bootstrap/artifacts/seneschal-profile.json'), attestations: { approval: record('bootstrap/artifacts/seneschal-profile-approved.json'), current_active: record('bootstrap/artifacts/seneschal-profile-current-active.json') } },
      chamberlain: { ...record('bootstrap/artifacts/chamberlain-profile.json'), attestations: { approval: record('bootstrap/artifacts/chamberlain-profile-approved.json'), current_active: record('bootstrap/artifacts/chamberlain-profile-current-active.json') } },
      secretary: { ...record('bootstrap/artifacts/secretary-profile.json'), attestations: { approval: record('bootstrap/artifacts/secretary-profile-approved.json'), current_active: record('bootstrap/artifacts/secretary-profile-current-active.json') } },
    },
    substrates: {
      provisional_recruiter: record('bootstrap/artifacts/provisional-recruiter-substrate.json'), ordinary_recruiter: record('bootstrap/artifacts/ordinary-recruiter-substrate.json'),
      seneschal: record('bootstrap/artifacts/seneschal-substrate.json'),
      chamberlain: record('bootstrap/artifacts/chamberlain-substrate.json'),
      secretary: record('bootstrap/artifacts/secretary-substrate.json'),
    },
    succession_commission: record('bootstrap/artifacts/recruiter-succession-commission.json'),
    assembly_commissions: {
      seneschal: record('bootstrap/artifacts/seneschal-assembly-commission.json'),
      chamberlain: record('bootstrap/artifacts/chamberlain-assembly-commission.json'),
      secretary: record('bootstrap/artifacts/secretary-assembly-commission.json'),
    },
    routes: record('bootstrap/artifacts/routes.json'),
    bootstrap_machine: record('bootstrap/artifacts/bootstrap-machine.json'),
    bootstrap_recovery_machine: record('bootstrap/artifacts/bootstrap-recovery-machine.json'),
    runtime_concurrency_replay: record('bootstrap/artifacts/runtime-concurrency-replay.json'),
  },
  compatibility: record('bootstrap/artifacts/compatibility.json'),
};
const manifestId = createHash('sha256').update(canonical(payload)).digest('hex');
const signature = sign(null, Buffer.from(manifestId), privateKey).toString('base64');
const manifest = { schema: 'imperium.bootstrap-manifest/v1', manifest_id: manifestId, unsigned_payload: payload, signatures: [{ key_id: keyId, algorithm: 'ed25519', signed_payload_digest: manifestId, signature }] };
writeFileSync(resolve(root, 'bootstrap/manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
