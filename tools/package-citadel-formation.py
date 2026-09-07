"""Package committed, allowlisted source plus the exact offline verification run.

Run with Python 3 from the candidate checkout after verification.json exists.
Never includes runtime journals, dotenv files, credentials, vendor or Git metadata.
"""
import hashlib
import io
import json
from pathlib import Path
import subprocess
import tarfile
import zipfile

ROOT = Path(__file__).resolve().parents[1]
PROOF = ROOT / "var/citadel-formation-proof"


def git(*args):
    return subprocess.check_output(["git", *args], cwd=ROOT)


def digest(data):
    return hashlib.sha256(data).hexdigest()


head = git("rev-parse", "HEAD").decode().strip()
tree = git("rev-parse", "HEAD^{tree}").decode().strip()
if git("status", "--porcelain", "--untracked-files=normal").strip():
    raise SystemExit("Refused: candidate checkout is not clean.")
verification = json.loads((PROOF / "verification.json").read_text(encoding="utf-8"))
if verification["tested_commit"] != head or verification["tested_tree"] != tree:
    raise SystemExit("Refused: verification does not identify this exact candidate.")
if verification["full_suite"]["exit_code"] != 0:
    raise SystemExit("Refused: full-suite completion is not successful.")
demo = json.loads((PROOF / "offline-demo.json").read_text(encoding="utf-8"))
if demo["boundary"]["network_calls"] != 0 or demo["boundary"]["mission_execution"]:
    raise SystemExit("Refused: proof boundary is not offline/non-executing.")

index = json.loads(git("show", "HEAD:docs/citadel-batch0/source-identities.json"))
paths = {"src", "tests", "contracts", "config", "bin/console", "composer.json", "composer.lock",
         "symfony.lock", "imperium-doctrine.md", ".github/workflows/phpunit.yml",
         "docs/citadel-batch0/source-identities.json", "docs/citadel-mission-formation-preparation.md",
         "docs/citadel-mission-formation-decisions.md", "docs/citadel-mission-formation-implementation.md",
         "docs/citadel-mission-formation-runtime-inventory-v1.tsv", "docs/delegate-mission-flow.md",
         "docs/next-lifecycle-delegate-mission-route.md", "docs/next-campaign-citadel-mission-formation.md",
         "docs/handoffs/citadel-mission-formation-implementation-ready.md",
         "docs/handoffs/citadel-mission-formation-preparation-complete.md",
         "tools/prove-citadel-formation.php", "tools/package-citadel-formation.py"}
paths.update(item["path"] for item in index["sources"])
entries = {}
with tarfile.open(fileobj=io.BytesIO(git("archive", "--format=tar", head, "--", *sorted(paths)))) as archive:
    for member in archive.getmembers():
        if member.isfile():
            entries["candidate/" + member.name] = archive.extractfile(member).read()
for item in index["historical_evidence"]:
    data = git("show", item["revision"] + ":" + item["path"])
    if digest(data) != item["git_bytes_sha256"]:
        raise SystemExit("Historical evidence identity mismatch.")
    entries["historical/" + item["revision"][:8] + "/" + item["path"]] = data
for name in ["verification.json", "full-suite.txt", "full-suite.xml", "offline-demo.json"]:
    data = (PROOF / name).read_bytes()
    if name != "offline-demo.json":
        data = data.replace(str(ROOT).encode(), b"<candidate-root>")
    entries["proof/" + name] = data
entries["changes.patch"] = git("diff", "35f4c3bbcb0a010a6c4b12a51bf13126c3a33ac1", head, "--")
entries["HANDOFF.md"] = (
    "# Citadel formation local review packet\n\n"
    f"Tested commit: `{head}`\n\nTested tree: `{tree}`\n\n"
    "Stages 1–3 reach an attributable synthetic receiving acceptance and child receipt, "
    "then non-executing Step 1 schema/reference validation. The full suite passed; "
    "see proof/verification.json for counts, command, environment and limitations.\n\n"
    "Read candidate/docs/citadel-mission-formation-implementation.md and "
    "candidate/contracts/citadel-formation-runtime.md for commands, authority mapping, "
    "migration behavior and later readiness prerequisites. No live transport is enabled.\n\n"
    "The archive includes allowlisted committed source/tests/contracts/configuration and "
    "required reading, plus public synthetic proof. It excludes dotenv, installed state, "
    "runtime journals, vendor and signing secrets. Historical artifacts retain their original bytes.\n\n"
    "For independent full-suite reproduction, use the repository at the tested commit with "
    "its locked dependencies. This review packet does not include every historical documentary "
    "fixture referenced by all repository tests. The included offline demo uses only synthetic roots.\n\n"
    "Verify MANIFEST.sha256 after extraction. The companion manifest also identifies the ZIP hash. "
    "No push, merge, installation, commissioning, activation or deployment was performed.\n"
).encode()
manifest = [{"path": name, "bytes": len(data), "sha256": digest(data)} for name, data in sorted(entries.items())]
entries["MANIFEST.sha256"] = "".join(f"{row['sha256']}  {row['path']}\n" for row in manifest).encode()
out = ROOT / "var/review-packets"
out.mkdir(parents=True, exist_ok=True)
target = out / f"citadel-formation-{head[:12]}.zip"
if target.exists():
    raise SystemExit("Refused: preserve the existing review packet.")
with zipfile.ZipFile(target, "x", compression=zipfile.ZIP_DEFLATED) as archive:
    for name, data in sorted(entries.items()):
        info = zipfile.ZipInfo(name, (2026, 9, 7, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(info, data)
with zipfile.ZipFile(target) as archive:
    for name, data in entries.items():
        if digest(archive.read(name)) != digest(data):
            raise SystemExit("Archive readback mismatch.")
outer = {"schema": "imperium.citadel-review-manifest/v1", "tested_commit": head, "tested_tree": tree,
         "archive": {"name": target.name, "bytes": target.stat().st_size, "sha256": digest(target.read_bytes())},
         "entries": manifest, "inner_manifest_sha256": digest(entries["MANIFEST.sha256"])}
manifest_path = target.with_suffix(".manifest.json")
manifest_path.write_text(json.dumps(outer, indent=2) + "\n", encoding="utf-8")
print(json.dumps({"archive": str(target), "manifest": str(manifest_path), "sha256": outer["archive"]["sha256"], "entries": len(entries)}, indent=2))
