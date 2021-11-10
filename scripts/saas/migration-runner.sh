#!/bin/bash

SITES="short-sites.csv"

while IFS="," read -r SOURCE DESTINATION DOMAIN
do
  ./migrate-to-preview.sh $SOURCE $DESTINATION $DOMAIN
done < <(tail -n +2 $SITES)
