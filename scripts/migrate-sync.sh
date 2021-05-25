#!/bin/sh

# usage: migrate-sync utqs-migration-tester
SOURCE_SITE="$1"
fin db create utexas_migrate
terminus env:wake $SOURCE_SITE.live
terminus drush $SOURCE_SITE.live cc all
terminus backup:create $SOURCE_SITE.live --element=db
terminus backup:get $SOURCE_SITE.live  --element=db --to=./db.sql.gz
gunzip -c ./db.sql.gz > db.sql
fin db import db.sql --db=utexas_migrate
rm db.sql db.sql.gz

cat web/modules/custom/utexas_migrate/scripts/migrate-settings.php >> web/sites/default/settings.local.php
echo "\$settings['migration_source_base_url'] = 'https://live-$SOURCE_SITE.pantheonsite.io/';" >> web/sites/default/settings.local.php

fin drush migrate-import --group=utexas
fin drush migrate-import --group=utprof
fin drush migrate-import --group=utnews
fin drush migrate-import --group=utevent
fin drush migrate-import --group=utexas_end
