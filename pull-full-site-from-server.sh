#!/usr/bin/env bash
# 1. Tải toàn bộ site từ server (tgndigital-pod@cloud) về máy local
# 2. Commit vào branch full-site và push lên Git
# Run from repo root: ./pull-full-site-from-server.sh
# Requires: SSH access to tgndigital-pod@cloud

set -e
REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
SERVER="tgndigital-pod@cloud"
REMOTE_PATH="~/htdocs/pod.tgndigital.vn/"

cd "${REPO_ROOT}"
echo "Switching to branch full-site..."
git checkout full-site

echo "Downloading full site from ${SERVER}:${REMOTE_PATH} into ${REPO_ROOT}"
rsync -avz --exclude '.git' --exclude 'pull-full-site-from-server.sh' --exclude 'pull-site-on-server.sh' \
  "${SERVER}:${REMOTE_PATH}" "${REPO_ROOT}/"

echo "Adding and committing..."
git add -A
git status
if git diff --cached --quiet; then
  echo "No changes to commit."
else
  git commit -m "Full site from server (pod.tgndigital.vn)"
  echo "Pushing to origin full-site..."
  git push origin full-site
  echo "Done. Full site đã tải về và push lên branch full-site."
fi
