#!/bin/bash

set -ex

for filename in *.php; do
  php -l "$filename"
done
