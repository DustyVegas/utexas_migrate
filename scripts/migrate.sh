#!/bin/sh

# usage: migrate-full utqs-migration-tester https://zoom.its.utexas.edu
if [ -z "$1" ];
then
  echo 'You must supply a Pantheon site machine name as the first parameter.'
  exit
fi

SOURCE_SITE="$1"
DOMAIN="$2"
(
  export SOURCE_SITE
  export DOMAIN
  web/modules/custom/utexas_migrate/scripts/scaffold.sh $SOURCE_SITE $DOMAIN
  web/modules/custom/utexas_migrate/scripts/all-tasks.sh
)


