#!/bin/bash

source $1
if [ -z "$SITES" ];
then
  echo 'Your source file is missing required variables'
  exit
fi
for SITE in "${SITES[@]}"
do
  # terminus drush $SITE.live pmu update -y
done
