#!/usr/bin/env bash
SITE=$1
UPENV=$2 # is there a live env or just test?
UPVER=$3
RELEASE="Release $UPVER"

echo "terminus -y upstream:updates:apply $SITE.dev --accept-upstream --updatedb && \
terminus -y env:deploy $SITE.test --note=\"$RELEASE\" --updatedb && \
"

terminus -y upstream:updates:apply $SITE.dev --accept-upstream --updatedb && \
terminus -y env:deploy $SITE.test --note "$RELEASE" --updatedb && \
if [ "$UPENV" == 'live' ]; then
    echo "terminus -y env:deploy $SITE.live --note \"$RELEASE\" --updatedb"
    terminus -y env:deploy $SITE.live --note "$RELEASE" --updatedb
fi

echo "Checking homepage HTTP status look for 200 at end:"
wget -O /dev/null --server-response http://$UPENV-$SITE.pantheon.berkeley.edu 2>&1 | awk '/^  HTTP/{print $2}'

echo "DONE with $SITE"
