#!/bin/bash

ENV="preview"

if [ -z "$1" ];
then
  echo "You must supply a source site name (e.g., utqs-zoom)"
  exit
fi
SOURCE_SITE=$1

if [ -z "$2" ];
then
  echo "You must supply a destination site name (e.g., utexas-zoom)"
  exit
fi
DESTINATION_SITE=$2

if [ -z "$3" ];
then
  echo "You must supply a domain(e.g., https://zoom.its.utexas.edu)"
  exit
fi
DOMAIN=$3

echo "****************************************"
echo "*** BEGIN MIGRATION: $DESTINATION_SITE ***"
echo "****************************************"

echo "Performing migration of $SOURCE_SITE to $DESTINATION_SITE as $DOMAIN..."

echo "Cloning the destination site & building the codebase..."
PANTHEON_SITE_GIT_URL="$(terminus connection:info $DESTINATION_SITE.dev --field=git_url)"
git clone "$PANTHEON_SITE_GIT_URL" $DESTINATION_SITE
cd $DESTINATION_SITE
composer install --ignore-platform-reqs

echo "Initiating site..."
git clone git@github.austin.utexas.edu:eis1-wcs/pantheon_docksal_starter.git .docksal
rm -rf .docksal/.git
fin init
fin config set HOSTING_SITE="$DESTINATION_SITE"
fin pull db -y
# fin drush si utexas utexas_installation_options.default_content=NULL -y
chmod 755 web/sites/default

echo "Rsyncing files from $SOURCE_SITE..."
SOURCE_ID=$(terminus site:info "$SOURCE_SITE" --field=ID)
rsync -rvlz --copy-unsafe-links --exclude=css --exclude=php --exclude=js --exclude=styles --size-only --checksum --ipv4 --progress -e 'ssh -p 2222' live.$SOURCE_ID@appserver.live.$SOURCE_ID.drush.in:files/ web/sites/default/files
fin drush cr

echo "Running migration..."
composer require utexas/utexas_migrate:dev-develop
fin drush en utexas_migrate -y
sh web/modules/custom/utexas_migrate/scripts/migrate.sh $SOURCE_SITE $DOMAIN

echo "Peforming cleanup tasks..."
fin drush pmu utprof_migrate utevent_migrate utnews_migrate utexas_migrate migrate_tools migrate_plus migrate -y
composer remove utexas/utexas_migrate
fin drush cr

echo "Migration complete... Proceeding to sync..."

fin db dump $DESTINATION_SITE.sql
terminus env:wake $DESTINATION_SITE.$ENV

echo "Importing database. This can take ~10 minutes..."
PANTHEON_SQL_CMD=`terminus connection:info $DESTINATION_SITE.$ENV --field=mysql_command`
$PANTHEON_SQL_CMD < $DESTINATION_SITE.sql

echo "Rsyncing files..."
DEST_ID=$(terminus site:info "$DESTINATION_SITE" --field=ID)
if [ ! $DEST_ID ];
then
  echo "No Pantheon site.env found matching $SITE"
  exit
fi
PATH_TO_FILES="$PWD/web/sites/default/files"
if [ ! -d $PATH_TO_FILES ];
then
  echo "The path to files, $FILEPATH , does not appear to be a valid directory. Exiting without sync."
  exit
fi
echo "Site files found at $PATH_TO_FILES"
echo "Syncing..."
rsync -rLvz --size-only --checksum --ipv4 --progress -e 'ssh -p 2222' "$PATH_TO_FILES/" --temp-dir=~/tmp/ $ENV.$DEST_ID@appserver.$ENV.$DEST_ID.drush.in:/files/
echo "File synchronization complete"

echo "Removing Docker containers..."
fin project remove 
cd ..
rm -r $DESTINATION_SITE

echo "****************************************"
echo "*** END MIGRATION: $DESTINATION_SITE ***"
echo "****************************************"
