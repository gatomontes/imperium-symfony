"""Export complete committed source and public offline evidence, never installed state."""
import datetime as dt
import hashlib
import io
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tarfile
import zipfile

ROOT=Path(__file__).resolve().parents[1]
ENTRY='b1a8378668ecd9f4e4c1502411a2713111915535'

def git(*args): return subprocess.check_output(['git',*args],cwd=ROOT)
def sha(data): return hashlib.sha256(data).hexdigest()
def write_json(path,value): path.write_text(json.dumps(value,indent=2)+'\n',encoding='utf-8')

def source(ref,target):
    rows=[]; excluded=[]
    entries=git('ls-tree','-r',ref).decode().splitlines()
    ids=[line.split()[2] for line in entries]
    data=subprocess.run(['git','cat-file','--batch'],cwd=ROOT,input=('\n'.join(ids)+'\n').encode(),stdout=subprocess.PIPE,check=True).stdout
    offset=0
    with zipfile.ZipFile(target,'x',compression=zipfile.ZIP_DEFLATED,compresslevel=6) as archive:
        for entry in entries:
            meta,path=entry.split('\t'); mode,typ,blob=meta.split()
            end=data.index(b'\n',offset); size=int(data[offset:end].split()[-1]); offset=end+1
            content=data[offset:offset+size]; offset+=size+1
            row={'path':path,'mode':mode,'blob':blob,'bytes':size,'sha256':sha(content)}
            if path=='.env' or path.startswith(('vendor/','var/','.git/','config/secrets/')):
                excluded.append(row); continue
            if typ!='blob': raise ValueError('Unsupported source object '+path)
            info=zipfile.ZipInfo(path,(2026,9,9,0,0,0)); info.create_system=3; info.external_attr=int(mode,8)<<16; info.compress_type=zipfile.ZIP_DEFLATED
            archive.writestr(info,content); rows.append(row)
    return rows,excluded

def main():
    if len(sys.argv)!=2: raise SystemExit('Usage: python tools/package-courtyard.py var/courtyard-gates/final-UTC')
    gates=(ROOT/sys.argv[1]).resolve()
    if not gates.is_relative_to(ROOT/'var/courtyard-gates'): raise SystemExit('Expected local generated gate evidence')
    v=json.loads((gates/'verification.json').read_text(encoding='utf-8'))
    if v['mode']!='final' or v['status_before'] or any(r['exit_code'] for r in v['runs'].values()) or 'full-suite' not in v['runs']:
        raise SystemExit('Successful committed focused/full gates required')
    if git('status','--porcelain').strip(): raise SystemExit('Clean final source required')
    final=git('rev-parse','HEAD').decode().strip(); tested=v['tested_commit']
    post=git('diff','--name-only',tested,final).decode().splitlines()
    if any(not p.endswith('.md') for p in post): raise SystemExit('Post-test changes must be Markdown only')
    date=dt.datetime.now(dt.timezone.utc).strftime('%Y%m%d')
    out=ROOT.parent/('courtyard-identity-review-'+date); out.mkdir(exist_ok=False)
    identity={'entry':{'commit':ENTRY,'tree':git('rev-parse',ENTRY+'^{tree}').decode().strip()},
              'preparation_merge':ENTRY,'source_baseline':{'commit':'88cee012d5fae0dc8e18f25eebed167912e06230','tree':'3826740810f42c90e6d8b6e00686947003ab62c4'},
              'tested':{'commit':tested,'tree':v['tested_tree']},'final':{'commit':final,'tree':git('rev-parse','HEAD^{tree}').decode().strip()},
              'branch':git('branch','--show-current').decode().strip(),'post_test_paths':post,'fc_independent_acceptance':'PENDING',
              'deployment_approved':False,'enrollment_authorized':False,'live_ready':False,'activation':False,'execution_authority':False}
    for kind,ref in [('tested',tested),('final',final)]:
        rows,excluded=source(ref,out/f'{kind}-source.zip'); write_json(out/f'{kind}-source-manifest.json',rows)
        identity[kind]['excluded_tracked_files']=excluded
    write_json(out/'source-identities.json',identity)
    (out/'final.diff').write_bytes(git('diff','--binary',ENTRY,final))
    (out/'post-test.diff').write_bytes(git('diff','--binary',tested,final))
    (out/'history.txt').write_bytes(git('log','--format=fuller','--stat',ENTRY+'..'+final))
    git('bundle','create',str(out/'history.bundle'),identity['branch'],'^'+ENTRY)
    result=subprocess.run(['git','bundle','verify',str(out/'history.bundle')],cwd=ROOT,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
    (out/'bundle-verification.txt').write_bytes(result.stdout)
    if result.returncode: raise SystemExit('Bundle verification failed')
    shutil.copytree(ROOT/'var/courtyard-gates',out/'gates')
    shutil.copy2(ROOT/'docs/courtyard-identity-report.md',out/'REPORT.md')
    shutil.copy2(ROOT/'tools/verify-courtyard-packet.py',out/'verify-courtyard-packet.py')
    (out/'VERIFY.md').write_text('''# Independent verification

Compare the outer ZIP SHA-256 with the separately supplied hash before extraction.
Run `python verify-courtyard-packet.py EXTRACTED_PACKET_DIRECTORY` for the exact
payload set, source archive set, modes, lengths, SHA-256 and Git blob identities.
For commit-tree verification add `--git-repository PATH_TO_LOCAL_REPOSITORY`.
That repository must contain the final review commit, imported from history.bundle
using a disposable clone of the known baseline repository; no remote fetch is needed.

The bounded bundle requires entry b1a8378668ecd9f4e4c1502411a2713111915535.
Use `git bundle verify history.bundle` from that baseline repository, then import
the named codex/courtyard-identity-mission-formation branch into the disposable
review clone. Compare both commits/trees in source-identities.json with Git.
The archives include every committed source/test/doctrine/tool/public fixture file
except the tracked .env, whose mode/blob/length/SHA is explicitly listed as omitted.
No vendor, untracked runtime, installed state, genuine credential or private key is
included. Restore locked offline dependencies separately for PHP reproduction.

Exact safe PHP command arrays, UTC times, native exits, JUnit, source statuses and
dependency identities are under gates/. Development results remain distinct from
the final committed gates. Final executable code was committed before validation;
post-test.diff is restricted to Markdown. Original synthetic fixtures were produced
before consumer changes and retain hard-coded compressed and per-file digests.

To verify public signatures/claims with the source checkout and locked PHP runtime:
`php tools/verify-courtyard-public.php FORMATION_PROOF CUSTODY_PROOF`, supplying the
final gate's formation-proof.stdout.txt and custody-proof.stdout.txt. The final
public-verification log records the exact run. This checks public bytes and
signatures, not genuine competence, independent historical custody or remote I/O.

FC0–FC3 independent acceptance remains pending. This packet is local review only;
no deployment, enrollment, live activity, activation or execution is authorized.
''',encoding='utf-8')
    manifest=[{'path':p.relative_to(out).as_posix(),'bytes':p.stat().st_size,'sha256':sha(p.read_bytes())} for p in sorted(out.rglob('*')) if p.is_file()]
    write_json(out/'payload-manifest.json',manifest)
    subprocess.run([sys.executable,str(out/'verify-courtyard-packet.py'),str(out),'--git-repository',str(ROOT)],check=True,cwd=ROOT,stdout=subprocess.DEVNULL)
    target=out.with_suffix('.zip')
    with zipfile.ZipFile(target,'x',compression=zipfile.ZIP_DEFLATED,compresslevel=6) as archive:
        for p in sorted(out.rglob('*')):
            if p.is_file(): archive.write(p,p.relative_to(out).as_posix())
    with zipfile.ZipFile(target) as archive:
        for p in sorted(out.rglob('*')):
            if p.is_file() and sha(archive.read(p.relative_to(out).as_posix()))!=sha(p.read_bytes()): raise ValueError('Outer readback mismatch')
    digest=sha(target.read_bytes()); target.with_suffix('.sha256').write_text(digest+'  '+target.name+'\n',encoding='ascii')
    print(json.dumps({'directory':str(out),'zip':str(target),'sha256':digest,'final':identity['final']},indent=2))

if __name__=='__main__': main()
