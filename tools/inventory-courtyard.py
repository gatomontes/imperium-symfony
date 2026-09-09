"""Source-bound CY0 inventory and reproducible residual-name audit; no runtime access."""
import csv
import hashlib
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ENTRY = 'b1a8378668ecd9f4e4c1502411a2713111915535'
CHAIN = {
 'FormationPersonnel.php': ('FormationInstitution; FormationSignatures; Garrison/Guildhall/Laboratorium/Senate/Conscription', 'FormationSessionAuthority; FormationCognition; CuriaFormationService; ChildCuriaFormationService; FormationSessionLeaseService'),
 'FormationCognition.php': ('CitadelIntakeService; FormationPersonnel; exact owner decision; FormationSessionAuthority', 'GovernanceProviderResourceDecisionService; FormationSessionLeaseService; FormationClaimCustodyBroker; ReceivingFormationHandoffService'),
 'FormationSessionAuthority.php': ('Retained intake/drafting request/handoff; exact Courtthane or Seneschal; FormationSignatures', 'FormationCognition; FormationClaimCustodyBroker'),
 'FormationJournal.php': ('Immutable hash-linked aggregate frames; AtomicTransition', 'All formation commands; custody and child publication/reconciliation'),
 'FormationPreparation.php': ('Explicit public input and Clock; no runtime custody', 'External signing exchange; exact production appointment/grant consumers'),
 'FormationProfileContract.php': ('Signed Laboratorium Officer Profile with exact Persona/Seat/version', 'FormationPersonnel::candidate; FormationOfficerAssemblyService'),
 'FormationOfficerAssemblyService.php': ('FormationPersonnel complete authenticated qualification chain', 'Exact appointment binding; currentness; claim/lease holder'),
 'FormationClaimCustodyBroker.php': ('Retained exact session/claim/operation and shared current authority', 'CredentialBroker issue/consume; FormationWireAdapter dispatch; response envelope'),
 'FormationSessionLeaseService.php': ('Current holder/Locksmith; exact authentic session; aggregate reservation', 'FormationCognition durable start; FormationClaimCustodyBroker complete derivation comparison'),
 'ChildCuriaFormationService.php': ('Exact approved reservation and current Courtthane; qualified child candidates', 'Immutable child receipt; FormationPublicationEvidence; CuriaFormationService reconciliation'),
 'FormationPublicationEvidence.php': ('Original retained frame/fence/publication and institutional signatures', 'ChildCuriaFormationService::reconcile; no new-effect authorization'),
 'CuriaFormationService.php': ('Courtthane dossier; distinct exact mission approval and child qualifications', 'MasterMason publication; original handoff; Seneschal receiving; non-executing Step 1'),
 'ReceivingFormationHandoffService.php': ('Exact child Seneschal approved handoff and admitted assessment', 'Child receipt store; CuriaFormationService::validateStepOne'),
}

def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT)

def category(path, line):
    if path.startswith(('bootstrap/', 'docs/')) and any(x in path for x in ('report','acceptance','decisions','batch','snapshot','handoffs','next-')):
        return 'historical/sealed evidence', 'retain original; current pointer where appropriate', 'Historical provenance is not renewed authority'
    if path.startswith('bootstrap/'):
        return 'historical/sealed evidence', 'retain original bytes', 'Sealed bootstrap is not Courtthane qualification'
    if re.search(r'castellan|CASTELLAN|Castellan', line):
        if path.startswith(('src/', 'tests/', 'tools/')):
            return 'interview Seat', 'exact Courtthane successor or explicit old refusal/recovery', 'No signed target/effect translation'
        return 'proposed future oversight / prior interview allocation', 'current doctrine successor; retain history', 'Castellan oversight deferred; no powers'
    if re.search(r"schema|citadel_id|CITADEL_|REPLY_TO_CITADEL|citadel-formation|citadel/|citadel-call|citadel-session|citadel-planning|citadel-drafting", line):
        return 'persisted/signed/wire identity', 'retain exact format and one domain', 'No identity, lock, registry, budget or replay split'
    if path.startswith('src/Command/Citadel') and any(x in path for x in ('Formation','Intake','Preparation')):
        return 'reception/formation function', 'canonical Courtyard CLI; retain source class and old alias', 'Same production service graph'
    if '/Formation/' in path or any(x in path for x in ('FormationTest','FormationFixture','formation.php','formation-custody','FormationClaim','FormationSession')):
        return 'reception/formation function', 'retain source names; change exact holder consumers', 'No source moves; frozen scanner extension unnecessary'
    if path.startswith('src/'):
        return 'other Citadel runtime capabilities', 'retain jurisdiction and unrelated consumers', 'Shared institutions and other cognition are not Courtyard'
    return 'enclosing Citadel', 'retain enclosing jurisdiction; current reception pointer', 'Citadel identity is distinct from Courtyard function'

def produce(ref, output):
    rows = []
    entries = git('ls-tree', '-r', ref).decode().splitlines()
    blobs = [entry.split()[2] for entry in entries]
    batch = subprocess.run(['git','cat-file','--batch'],input=('\n'.join(blobs)+'\n').encode(),stdout=subprocess.PIPE,check=True,cwd=ROOT).stdout
    offset = 0
    for entry in entries:
        meta, path = entry.split('\t'); mode, typ, blob = meta.split()
        end=batch.index(b'\n',offset); size=int(batch[offset:end].split()[-1]); offset=end+1
        data=batch[offset:offset+size]; offset+=size+1
        if typ != 'blob' or path.startswith('.env') or path in ('docs/courtyard-identity-inventory.tsv','docs/courtyard-identity-residual-audit.tsv'):
            continue
        if b'\0' in data:
            continue
        for number, line in enumerate(data.decode('utf-8', errors='replace').splitlines(), 1):
            if not re.search('citadel|castellan', line, re.I):
                continue
            cat, action, reason = category(path, line)
            producer,consumer=CHAIN.get(path.split('/')[-1],('Author/source at Git blob '+blob,'Exact consumers and retained categories in compatibility contract'))
            rows.append([f'{path}:{number}', line.strip().replace('\t',' ')[:700], cat,
                         producer, consumer, action, reason,
                         'CourtyardIdentityTest; FormationClaimCustodyTest; existing formation/native/coverage gates',
                         hashlib.sha256(data).hexdigest()])
    with (ROOT/output).open('w', encoding='utf-8', newline='') as handle:
        writer=csv.writer(handle,delimiter='\t',lineterminator='\n')
        writer.writerow(['path/symbol','source excerpt','semantic category','producer','consumer','proposed action','compatibility rationale','affected proof','source SHA-256'])
        writer.writerows(rows)
    print(f'{output}: {len(rows)} classified references at {ref}')

if __name__ == '__main__':
    import sys
    if len(sys.argv) == 1:
        produce(ENTRY, Path('docs/courtyard-identity-inventory.tsv'))
    elif sys.argv[1:] == ['--residual']:
        produce('HEAD', Path('docs/courtyard-identity-residual-audit.tsv'))
    else:
        raise SystemExit('No runtime or arbitrary root input accepted')
