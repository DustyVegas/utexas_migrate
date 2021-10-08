#!/bin/bash

echo "Enter Pantheon site name (e.g., utexas-its):"
read SITE
echo "Enter the destination env (dev, test, live):"
read ENV
ID=$(terminus site:info "$SITE" --field=ID)
if [ ! $ID ];
then
  echo "No Pantheon site.env found matching $SITE"
  return
fi
PATH_TO_FILES="$PWD/web/sites/default/files"
if [ ! -d $PATH_TO_FILES ];
then
  echo "The path to files, $FILEPATH , does not appear to be a valid directory. Exiting without sync."
  return
fi
echo "Site files found at $PATH_TO_FILES"
echo "Syncing..."
rsync -rLvz --size-only --checksum --ipv4 --progress -e 'ssh -p 2222' "$PATH_TO_FILES/" --temp-dir=~/tmp/ $ENV.$ID@appserver.$ENV.$ID.drush.in:/files/
say "File synchronization complete"
