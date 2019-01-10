#!/bin/bash

set -ex

for filename in *.php; do
  php -l "$filename"
done

curl --fail "http://web/api.php?method=fetchPodcast&id=itunes617416468%2Faccidental-tech-podcast"
echo
