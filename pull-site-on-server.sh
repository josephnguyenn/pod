#!/usr/bin/env bash
# Run this ON THE SERVER (tgndigital-pod@cloud) inside ~/htdocs/pod.tgndigital.vn
# to pull all code from git (origin/full-site).
#
# Usage on server:
#   cd ~/htdocs/pod.tgndigital.vn
#   bash pull-site-on-server.sh
# Or upload this file to the server and run it there.

set -e
cd ~/htdocs/pod.tgndigital.vn

echo "Current directory: $(pwd)"
git status
echo "Pulling latest from origin full-site..."
git fetch origin
git pull origin full-site
echo "Done. Site code (full-site branch) is up to date."
