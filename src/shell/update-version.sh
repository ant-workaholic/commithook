#!/usr/bin/env bash
: <<'LCS'
/**
 * @license https://raw.githubusercontent.com/andkirby/commithook/master/LICENSE.md
 */
LCS

set -o pipefail
set -o errexit
set -o nounset
set -o xtrace

VERSION_DRY_RUN=0

# Set magic variables for current file & dir
__dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
__file="${__dir}/$(basename "${BASH_SOURCE[0]}")"
readonly __dir __file

cd "${__dir}/.."

current_branch=$(git branch | grep '*' | grep -Eo '[^* ]+')
echo -n "Use '${current_branch}' branch? (y/n) "
read answer
if [ -z $(echo "${answer}" | grep -i "^y") ]; then
    # Answer is not YES
    echo Stop!
    exit 1
fi

if [ $? != 0 ]; then echo "error: Can't go to master branch."; exit 1; fi

# reset files with the version
git checkout -- config/root.xml && git checkout -- lib/PreCommit/Command/Application.php
if [ $? != 0 ]; then echo "error: Can't reset files Application.php and root.xml."; exit 1; fi

# Read current version
current_version=$(grep -E '<version>[^<]' ${__dir}/../config/root.xml | grep -Eo '[0-9][^<]+')
if [ $? != 0 ]; then echo "error: Can't get current version."; exit 1; fi
echo "Current version: ${current_version}"

match=$(grep " = '${current_version}'" ${__dir}/../lib/PreCommit/Command/Application.php)
if [ -z "${match}" ]; then
    echo "error: The same version '${current_version}' not found in PreCommit/Command/Application.php file."
    exit 1
fi

last_tag_name=$(git tag -l | sort -V | tail -1)
last_version=$(echo "${last_version}" | sed 's/^v\(.*\)/\1/')

# Ask about selected version
echo -n "Is it correct version to change? ${last_version} (y/n) "
read answer
if [ -z $(echo "${answer}" | grep -i "^y") ]; then
    # Answer is not YES
    echo Stop!
    exit 1
fi

echo ${current_version} > ${__dir}/../../dev_version \
    && echo ${last_version} > ${__dir}/../../release_version
if [ $? != 0 ]; then echo "error: Can't create version files."; exit 1; fi

# replace version
sed 's|<version>'"${current_version}"'</version>|<version>'"${last_version}"'</version>|g' ${__dir}/../config/root.xml \
        > ${__dir}/../config/root.xml.tmp \
        && mv ${__dir}/../config/root.xml.tmp ${__dir}/../config/root.xml \
&& sed "s| = '${current_version}';| = '${last_version}';|g" ${__dir}/../lib/PreCommit/Command/Application.php \
    > ${__dir}/../lib/PreCommit/Command/Application.php.tmp \
    && mv ${__dir}/../lib/PreCommit/Command/Application.php.tmp ${__dir}/../lib/PreCommit/Command/Application.php
if [ $? != 0 ]; then echo "error: Can't update version in files."; exit 1; fi

# commit updated files
git add ${__dir}/../../src/lib/PreCommit/Command/Application.php && \
git add ${__dir}/../../src/config/root.xml && \
git add ${__dir}/../../dev_version && \
git add ${__dir}/../../release_version
git status

# Ask about selected version
echo -n "Correct files for commit? (y/n) "
read answer
if [ -z $(echo "${answer}" | grep -i "^y") ]; then
    # Answer is not YES
    echo Stop!
    exit 1
fi

# git tag-move -- custom command
# Make commit, add tag, revert this commit
git commit -m "@@through Update version to ${last_tag_name}." \
    && git tag-move ${last_tag_name} \
    && git revert HEAD

