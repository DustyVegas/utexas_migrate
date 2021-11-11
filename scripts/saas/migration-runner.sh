#!/bin/bash

SITES="short-sites.csv"

while IFS="," read -r SOURCE_SITE DESTINATION_SITE DOMAIN
do
  echo "Running migration for $DESTINATION_SITE..."
  ./migrate-to-preview.sh $SOURCE_SITE $DESTINATION_SITE $DOMAIN >> migration-report.txt 2>&1 
done < <(tail $SITES)
