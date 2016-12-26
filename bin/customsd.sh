#!/bin/bash

if [ -z "$SONOS_IP" ]; then
  echo "SONOS_IP unset" >&2
  exit 1
fi

set -x
set -e

LOCAL_IP=$(ifconfig -a | grep 'inet ' | grep broadcast | awk '{ print $2 }')

SID="255"
NAME="Overcast"
URI="http://$LOCAL_IP/smapi.php"
SECURE_URI="http://$LOCAL_IP/smapi.php"
POLL_INTERVAL=10
AUTH_TYPE="Anonymous"
STRINGS_VERSION=1
STRINGS_URI="http://$LOCAL_IP/strings.xml"
PRESENTATION_MAP_VERSION=0
PRESENTATION_MAP_URI=""
CONTAINER_TYPE="SoundLab"

curl -v "http://$SONOS_IP:1400/customsd" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "sid=$SID" \
  --data-urlencode "name=$NAME" \
  --data-urlencode "uri=$URI" \
  --data-urlencode "secureUri=$SECURE_URI" \
  --data-urlencode "pollInterval=$POLL_INTERVAL" \
  --data-urlencode "authType=$AUTH_TYPE" \
  --data-urlencode "stringsVersion=$STRINGS_VERSION" \
  --data-urlencode "stringsUri=$STRINGS_URI" \
  --data-urlencode "presentationMapVersion=$PRESENTATION_MAP_VERSION" \
  --data-urlencode "presentationMapUri=$PRESENTATION_MAP_URI" \
  --data-urlencode "containerType=$CONTAINER_TYPE"

exit
