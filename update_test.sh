#!/usr/bin/env bash

#!/usr/bin/env bash
SITE=$1
UPENV=$2 # is there a live env or just test?
UPVER=$3
RELEASE="Release $UPVER"
terminus -y site:info $SITE && \
terminus -y domain:list $SITE.test && \
if [ "$UPENV" == 'live' ]; then
    terminus -y env:error $SITE.live --note $RELEASE --updatedb
fi

echo "Checking homepage HTTP status look for 200 at end:"
wget --server-response http://$UPENV-$SITE.pantheon.berkeley.edu 2>&1 | awk '/^  HTTP/{print "$1 $2 $3"}'

echo "DONE with $SITE"
