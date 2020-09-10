# 2. Migration processors for custom components

Date: 2020-09-10

## Status

Accepted

## Context

Drupal's Migrate API architecture uses the 3-stage “Extract-Transform-Load” (ETL) process ([reference](https://www.drupal.org/docs/8/api/migrate-api/migrate-api-overview#s-migrations-are-extract-transform-load-etl-processes)). The most onerous stage involves transforming the idiosyncrasies of a given component. Although UT Drupal Kit custom components share similar elements such as a list of links or a call to action field, transformation details will differ due to machine names and schema structure.

## Decision

Provide separate base helper classes for each custom component migration instead of an abstract class that can be extended. In this single file, define callbacks for extracting the source data, and for transforming it into a format that can be loaded in version 3.

Provide some transformation methods in a separate helper class for similar tasks (preparing a link, getting the destination media ID from the source FID), but minimize the amount of abstraction so that the developer can more easily debug an individual component in the single helper class.

## Consequences

- Developers may easily add breakpoints/debugging output for a single component (rather than getting output from all components when debugging).
- This will require writing more duplicate method names and foregoing the advantages of class inheritance.

