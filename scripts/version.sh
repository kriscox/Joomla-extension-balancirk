#!/bin/sh

set -eu

ROOT_DIR=${ROOT_DIR:-$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)}

VERSION_FILES="
$ROOT_DIR/balancirk.xml
$ROOT_DIR/pkg_balancirk.xml
$ROOT_DIR/components/com_balancirk/balancirk.xml
"

current_version() {
    sed -n 's#.*<version>\(.*\)</version>.*#\1#p' "$ROOT_DIR/pkg_balancirk.xml" | head -n 1
}

bump_version() {
    version=$1
    kind=$2

    old_ifs=$IFS
    IFS=.
    set -- $version
    IFS=$old_ifs

    major=${1:-0}
    minor=${2:-0}
    patch=${3:-0}

    case "$kind" in
        patch)
            patch=$((patch + 1))
            ;;
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        *)
            echo "Unsupported bump kind: $kind" >&2
            exit 1
            ;;
    esac

    printf '%s.%s.%s\n' "$major" "$minor" "$patch"
}

write_version() {
    old_version=$1
    new_version=$2
    escaped_old_version=$(printf '%s\n' "$old_version" | sed 's/[.[\*^$()+?{|]/\\&/g')

    for file in $VERSION_FILES; do
        perl -0pi -e "s#<version>${escaped_old_version}</version>#<version>${new_version}</version>#g" "$file"
    done
}

ensure_clean() {
    if ! git -C "$ROOT_DIR" diff --quiet --ignore-submodules HEAD --; then
        echo "Git worktree is not clean. Commit or stash changes before running release." >&2
        exit 1
    fi

    if [ -n "$(git -C "$ROOT_DIR" ls-files --others --exclude-standard)" ]; then
        echo "Git worktree has untracked files. Commit or stash them before running release." >&2
        exit 1
    fi
}

case "${1:-}" in
    current)
        current_version
        ;;
    bump)
        if [ $# -ne 2 ]; then
            echo "Usage: $0 bump <patch|major>" >&2
            exit 1
        fi

        old_version=$(current_version)
        new_version=$(bump_version "$old_version" "$2")
        write_version "$old_version" "$new_version"
        printf '%s\n' "$new_version"
        ;;
    ensure-clean)
        ensure_clean
        ;;
    *)
        echo "Usage: $0 {current|bump <patch|major>|ensure-clean}" >&2
        exit 1
        ;;
esac
