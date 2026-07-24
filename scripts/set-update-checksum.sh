#!/bin/sh
# Insert or replace <sha256> for a given package version in balancirk_update.xml.
#
# Usage: scripts/set-update-checksum.sh <version> <sha256> [update-xml-path]
# Example: scripts/set-update-checksum.sh 1.3.19 "$(sha256sum pkg_balancirk.zip | cut -d' ' -f1)"

set -eu

VERSION=${1:-}
SHA256=${2:-}
UPDATE_XML=${3:-balancirk_update.xml}

if [ -z "$VERSION" ] || [ -z "$SHA256" ]; then
    echo "Usage: $0 <version> <sha256> [update-xml-path]" >&2
    exit 1
fi

case "$SHA256" in
    *[!0-9a-fA-F]* | "")
        echo "Invalid sha256 (expected hex): $SHA256" >&2
        exit 1
        ;;
esac

# Normalize to lowercase (Joomla compares case-insensitively but stores lowercase).
SHA256=$(printf '%s' "$SHA256" | tr 'A-F' 'a-f')

if [ ! -f "$UPDATE_XML" ]; then
    echo "Update XML not found: $UPDATE_XML" >&2
    exit 1
fi

python3 - "$VERSION" "$SHA256" "$UPDATE_XML" <<'PY'
import re
import sys

version, sha256, path = sys.argv[1], sys.argv[2], sys.argv[3]
text = open(path, encoding="utf-8").read()

# Match the <update>...</update> block that contains this exact <version>.
pattern = re.compile(
    r"(<update>\s*"
    r"(?:(?!</update>).)*?"
    r"<version>" + re.escape(version) + r"</version>"
    r"(?:(?!</update>).)*?"
    r"</update>)",
    re.DOTALL,
)

match = pattern.search(text)
if not match:
    raise SystemExit(f"No <update> block found for version {version} in {path}")

block = match.group(1)

sha_tag = f"<sha256>{sha256}</sha256>"
if re.search(r"<sha256>[^<]*</sha256>", block):
    block = re.sub(r"<sha256>[^<]*</sha256>", sha_tag, block, count=1)
elif "</downloads>" in block:
    block = block.replace("</downloads>", f"</downloads>\n\t\t{sha_tag}", 1)
else:
    # Fallback: place before </update>
    block = block.replace("</update>", f"\t\t{sha_tag}\n\t</update>", 1)

text = text[: match.start(1)] + block + text[match.end(1) :]
open(path, "w", encoding="utf-8", newline="\n").write(text)
print(f"Set sha256 for {version} in {path}")
PY
