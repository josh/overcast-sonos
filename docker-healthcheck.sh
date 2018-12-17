#!/bin/bash

IIDFILE="docker_image_id~"

set -ex

docker build --iidfile="$IIDFILE" .

IMAGE_ID=$(cat "$IIDFILE" ; rm "$IIDFILE")
CONTAINER_ID=$(docker run --detach --rm "$IMAGE_ID")

function docker_stop {
  docker stop "$CONTAINER_ID"
}
trap docker_stop EXIT

heathcheck() {
  docker inspect --format='{{.State.Health.Status}}' "$CONTAINER_ID"
}

set +x
while [ $(heathcheck) = "starting" ]; do
  sleep 1
done
set -x

[ $(heathcheck) = "healthy" ] || exit 1
