"""Verify extracted review payload and exact Git source archives using Python stdlib."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import zipfile

def digest(data): return hashlib.sha256(data).hexdigest()

def verify(root,repository=None):
    root=Path(root)
    manifest=json.loads((root/'payload-manifest.json').read_text(encoding='utf-8'))
    actual={p.relative_to(root).as_posix() for p in root.rglob('*') if p.is_file()}
    expected={row['path'] for row in manifest}
    if actual != expected | {'payload-manifest.json'}: raise ValueError('Payload path set mismatch')
    for row in manifest:
        data=(root/row['path']).read_bytes()
        if len(data)!=row['bytes'] or digest(data)!=row['sha256']: raise ValueError('Payload mismatch: '+row['path'])
    identity=json.loads((root/'source-identities.json').read_text(encoding='utf-8'))
    for kind in ('tested','final'):
        rows=json.loads((root/f'{kind}-source-manifest.json').read_text(encoding='utf-8'))
        with zipfile.ZipFile(root/f'{kind}-source.zip') as archive:
            if len(archive.namelist())!=len(set(archive.namelist())) or set(archive.namelist())!={r['path'] for r in rows}: raise ValueError('Source path set mismatch')
            for row in rows:
                data=archive.read(row['path']); info=archive.getinfo(row['path'])
                blob=hashlib.sha1(b'blob '+str(len(data)).encode()+b'\0'+data).hexdigest()
                if len(data)!=row['bytes'] or digest(data)!=row['sha256'] or blob!=row['blob'] or (info.external_attr>>16)!=int(row['mode'],8):
                    raise ValueError('Git source mismatch: '+row['path'])
        if repository:
            ref=identity[kind]['commit']
            def git(*args): return subprocess.check_output(['git','-C',str(repository),*args]).decode().strip()
            if git('rev-parse',ref+'^{tree}')!=identity[kind]['tree']: raise ValueError('Commit tree mismatch')
            objects={}
            for line in git('ls-tree','-r',ref).splitlines():
                meta,path=line.split('\t'); mode,typ,blob=meta.split(); objects[path]=(mode,blob)
            supplied={row['path']:(row['mode'],row['blob']) for row in rows+identity[kind]['excluded_tracked_files']}
            if objects!=supplied: raise ValueError('Manifest differs from committed Git tree')
    print(json.dumps({'status':'VERIFIED','payloads':len(manifest),'source_identity':identity},indent=2))

if __name__=='__main__':
    if len(sys.argv) not in (2,4) or (len(sys.argv)==4 and sys.argv[2]!='--git-repository'):
        raise SystemExit('Usage: python verify-courtyard-packet.py EXTRACTED_PACKET_DIRECTORY [--git-repository LOCAL_REPOSITORY]')
    verify(sys.argv[1],sys.argv[3] if len(sys.argv)==4 else None)
