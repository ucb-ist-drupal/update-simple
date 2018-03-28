#!/usr/bin/env bash
SITE=$1
UPENV=$2 # is there a live env or just test?
# terminus -y env:clone-content $SITE.$UPENV dev && \
terminus -y upstream:updates:apply $SITE.dev --accept-upstream --updatedb && \
terminus -y env:deploy $SITE.test --note "release 0.16.8" --updatedb && \
if [ "$UPENV" == 'live' ]; then
    terminus -y env:deploy $SITE.live --note "release 0.16.8" --updatedb
fi

echo "Checking homepage HTTP status look for 200 at end:"
wget --server-response http://$UPENV-$SITE.pantheon.berkeley.edu 2>&1 | awk '/^  HTTP/{print $2}'

echo "DONE with $SITE"
