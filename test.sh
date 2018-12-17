#!/bin/bash

set -ex

for filename in *.php; do
  php -l "$filename"
done

./docker-healthcheck.sh
