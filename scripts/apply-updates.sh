#!/bin/bash

## Helper for use with Jenkins
if [ -z "$terminus" ];
then
  terminus="terminus"
fi

run_updates_on_site() {
  echo "Running updates on $1 ..."
  # Update 'preview'

  # $terminus env:wake $1.preview
  # $terminus env:clear-cache $1.preview
  # $terminus upstream:updates:apply --updatedb --accept-upstream -- $1.preview
  # $terminus drush $1.dev updb -y
  # $terminus drush $1.dev cr

  # Update 'dev', 'test', and 'live'
  # $terminus env:wake $1.dev
  # $terminus env:clear-cache $1.dev
  # $terminus upstream:updates:apply --updatedb --accept-upstream -- $1.dev
  # $terminus drush $1.dev updb -y
  # $terminus drush $1.dev cr
  $terminus env:deploy "$1".test --sync-content --note='Apply upstream updates' --cc --updatedb
  $terminus env:deploy "$1".live --sync-content --note='Apply upstream updates' --cc --updatedb

  # Push to 'preview'
  $terminus env:wake $1.preview
  $terminus multidev:merge-from-dev --updatedb --yes -- $1.preview
  $terminus env:clone-content --cc --updatedb --yes -- $1.live preview
  espeak "Process complete"
}

if [ -z "$1" ];
then
  echo "You must supply a filename or site name as the first parameter (e.g. 'quicksites-to-managed-sites.sh' or 'utexas-whole-health')"
  exit
fi
if [ -f "$1" ]; 
then 
  source $1
  if [ -z "$SITES" ];
  then
    echo 'Your source file is missing required variables'
    exit
  fi
  for SITE in "${SITES[@]}"
  do
    run_updates_on_site $SITE
  done
else
  SITE="$1"
  echo "Checking validity of Pantheon site..."
  ID=$(terminus site:info "$SITE" --field=ID)
  if [ ! $ID ];
  then
    echo "ERROR: No Pantheon site found matching $SITE"
    echo "Exiting..."
    return
  else
    run_updates_on_site $SITE
  fi
fi
