#!/bin/sh

# usage: migrate-full utqs-migration-tester
if [ -z "$1" ];
then
  echo 'You must supply a Pantheon site machine name as the first parameter.'
  exit
fi

SOURCE_SITE="$1"
(
  export SOURCE_SITE
  web/modules/custom/utexas_migrate/scripts/scaffold.sh
  web/modules/custom/utexas_migrate/scripts/all-tasks.sh
)


