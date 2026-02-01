#!/usr/bin/env bash
# 1. Tải toàn bộ site từ server về máy local
# 2. Commit vào branch full-site và push lên Git
# Run from repo root: ./pull-full-site-from-server.sh
# SSH: ssh -p 24700 tgndigital-pod@103.216.117.213

set -e
REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
SERVER="tgndigital-pod@103.216.117.213"
SSH_PORT="24700"
REMOTE_PATH="~/htdocs/pod.tgndigital.vn/"

cd "${REPO_ROOT}"
echo "Switching to branch full-site..."
git checkout full-site

echo "Downloading full site from ${SERVER}:${REMOTE_PATH} (port ${SSH_PORT}) into ${REPO_ROOT}"
rsync -avz -e "ssh -p ${SSH_PORT}" --exclude '.git' --exclude 'pull-full-site-from-server.sh' --exclude 'pull-site-on-server.sh' \
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
