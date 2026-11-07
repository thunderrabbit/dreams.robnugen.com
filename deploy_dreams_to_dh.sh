#!/bin/bash
# Deploy current branch to DreamHost via git push + ssh pull
# (based on mg.robnugen.com's deploy_on_dh.sh)
set -e

cd "$(dirname "$0")"
BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "Pushing $BRANCH to origin..."
git push -u origin "$BRANCH"

echo "Pulling $BRANCH on DreamHost..."
ssh drc "cd dreams.robnugen.com; git restore .; git pull origin $BRANCH"

echo "Deployed $BRANCH to DreamHost."
