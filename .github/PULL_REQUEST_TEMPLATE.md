## Purpose
Resolves #

## Vouches
- [ ] I checked the changed files for syntax compliance & [naming conventions](https://github.austin.utexas.edu/eis1-wcs/d8-standards/blob/master/Naming_Conventions.md) adherence
- [ ] I considered whether documentation or a decision record needs to be added
- [ ] Content exists in https://live-utqs-migration-tester.pantheonsite.io/ to demonstrate the migration task.

## Run the migration

```
composer create-project utexas/utdk-project utdk-project --stability=dev --remove-vcs --no-install \
    && cd utdk-project \
    && (cd upstream-configuration && composer require utexas/utdk_profile:dev-develop --no-update) \
    && composer require utexas/utdk_localdev:dev-master \
    && fin init && fin drush si utexas utexas_installation_options.default_content=NULL -y \
    && composer require utexas/utprof:dev-develop \
    && fin drush -y en utprof utprof_block_type_profile_listing utprof_content_type_profile utprof_view_profiles utprof_vocabulary_groups utprof_vocabulary_tags \
    && composer require utexas/utnews:dev-develop \
    && fin drush -y en utnews utnews_block_type_news_listing utnews_content_type_news utnews_view_listing_page utnews_vocabulary_authors utnews_vocabulary_categories utnews_vocabulary_tags \
    && composer require utexas/utevent:dev-develop \
    && fin drush -y en utevent utevent_block_type_event_listing utevent_content_type_event utevent_view_listing_page utevent_vocabulary_location utevent_vocabulary_tags \
```

2. Add this branch

```
composer require utexas/utexas_migrate:dev-<branchname>
fin drush en utexas_migrate
```

3. Run the migration against `utqs-migration-tester`:

```
sh web/modules/custom/utexas_migrate/scripts/migrate.sh utqs-migration-tester
```

## Evaluation steps
