#!/usr/bin/env bash
# Pull full site code from server into current repo (branch full-site).
# Run from repo root: ./pull-full-site-from-server.sh
# Requires: SSH access to tgndigital-pod@cloud

set -e
REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
SERVER="tgndigital-pod@cloud"
REMOTE_PATH="~/htdocs/pod.tgndigital.vn/"

echo "Pulling from ${SERVER}:${REMOTE_PATH} into ${REPO_ROOT}"
rsync -avz --exclude '.git' --exclude 'pull-full-site-from-server.sh' \
  "${SERVER}:${REMOTE_PATH}" "${REPO_ROOT}/"

echo "Adding and committing..."
cd "${REPO_ROOT}"
git add -A
git status
if git diff --cached --quiet; then
  echo "No changes to commit."
else
  git commit -m "Full site from server (pod.tgndigital.vn)"
  echo "Done. Branch full-site now has server code."
fi
