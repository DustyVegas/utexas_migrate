echo "************************************"
echo "*** UT Drupal Kit Migration Tool ***"
echo "************************************"
echo
echo "Prior to running this script, it is assumed that a UT Drupal Kit version 3 site has been created on Pantheon, but no modifications have been made to the codebase (i.e., adding the add-ons)."

echo "Enter the Pantheon machine name of the source site (e.g., utqs-migration-tester):"
read SOURCE_SITE

echo "Enter the Pantheon machine name of the destination site (utdk-migration-tester):"
read DESTINATION_SITE

echo "Enter the domain of the site (e.g., https://zoom.its.utexas.edu)"
read DOMAIN

echo "Cloning the destination site & building the codebase..."
PANTHEON_SITE_GIT_URL="$(terminus connection:info $DESTINATION_SITE.dev --field=git_url)"
git clone "$PANTHEON_SITE_GIT_URL" $DESTINATION_SITE
cd $DESTINATION_SITE
composer install

# echo "Adding add-ons..."
# composer require utexas/pantheon_saml_integration:^3
# composer require utexas/utprof:^3
# composer require utexas/utnews:^3
# composer require utexas/utevent:^3

echo "Installing site..."
git clone git@github.austin.utexas.edu:eis1-wcs/pantheon_docksal_starter.git .docksal
rm -rf .docksal/.git
fin init
fin config set HOSTING_SITE="$DESTINATION_SITE"
# fin drush si utexas -y
fin drush si utexas utexas_installation_options.default_content=NULL -y
chmod 755 web/sites/default

echo "Rsyncing files from $SOURCE_SITE..."
SOURCE_ID=$(terminus site:info "$SOURCE_SITE" --field=ID)
rsync -rvlz --copy-unsafe-links --exclude=css --exclude=php --exclude=js --exclude=styles --size-only --checksum --ipv4 --progress -e 'ssh -p 2222' live.$SOURCE_ID@appserver.live.$SOURCE_ID.drush.in:files/ web/sites/default/files
fin drush cr

echo "*************************"
echo "Enabling SaaS elements..."
echo "*************************"
fin drush en utexas_role_site_manager

fin drush -y en utprof utprof_block_type_profile_listing utprof_content_type_profile utprof_view_profiles utprof_vocabulary_groups utprof_vocabulary_tags
fin drush utprof:grant --set=manager --role=utexas_site_manager
fin drush utprof:grant --set=editor --role=utexas_content_editor

fin drush -y en utnews utnews_block_type_news_listing utnews_content_type_news utnews_view_listing_page utnews_vocabulary_authors utnews_vocabulary_categories utnews_vocabulary_tags
fin drush utnews:grant --set=manager --role=utexas_site_manager
fin drush utnews:grant --set=editor --role=utexas_content_editor

fin drush -y en utevent utevent_block_type_event_listing utevent_content_type_event utevent_view_listing_page utevent_vocabulary_location utevent_vocabulary_tags
fin drush utevent:grant --set=manager --role=utexas_site_manager
fin drush utevent:grant --set=editor --role=utexas_content_editor

fin drush en utexas_saml_auth_helper -y
fin drush config-set simplesamlphp_auth.settings activate 1 -y

echo "Running migration..."
composer require utexas/utexas_migrate:dev-develop
fin drush en utexas_migrate -y
sh web/modules/custom/utexas_migrate/scripts/migrate.sh $SOURCE_SITE $DOMAIN

echo "Peforming cleanup tasks..."
fin drush pmu utprof_migrate utevent_migrate utnews_migrate utexas_migrate migrate_tools migrate_plus migrate -y
composer remove utexas/utexas_migrate
fin drush cr

say "Migration complete"

echo
echo "***************************"
echo "*** Migration complete! ***"
echo "***************************"
echo
echo "Summary of tasks:"
echo "- Installed the site with no demo content"
echo "- Added & enabled the add-ons"
echo "- Enabled the utexas_site_manager role"
echo "- Assigned the add-on permissions to the Content Editor and Site Manager roles."
echo "- Added & enabled SAML integration."
echo "- Synced all files from the source site to the destination site"
echo "- Executed the migration"
echo
echo "This migration is staged locally. To push it to the destination site:"
echo " 1. Review the migration at http://$DESTINATION_SITE.docksal"
echo " 2. Version changes to the codebase (optionally adding the .docksal directory)"
echo " 3. cd into the local staged repo directory (1 level below) and Rsync all files to the destination (bash ../scripts/pantheon-rsync-all-files.sh)"
echo " 4. Export the database (fin db dump $DESTINATION_SITE.sql)"
echo " 5. Import the database to Pantheon (bash ../scripts/pantheon-import-db.sh)"
echo " 6. Add SAML assets (https://github.austin.utexas.edu/eis1-wcs/pantheon_saml_integration/issues/11)"
echo " 7. Tag the site in the Pantheon dasboard with 'SAML'"
echo " 8. Sign in and change the site email and user 1 email to wcs-drupal-site-admins@utlists.utexas.edu"
