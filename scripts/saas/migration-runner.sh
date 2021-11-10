#!/bin/bash

SITES="short-sites.csv"

while IFS="," read -r SOURCE_SITE DESTINATION_SITE DOMAIN
do
  ./migrate-to-preview.sh $SOURCE $DESTINATION $DOMAIN
done < <(tail $SITES)
