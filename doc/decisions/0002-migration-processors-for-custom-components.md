# 2. Migration processors for custom components

Date: 2020-09-10

## Status

Accepted

## Context

Migrating custom components on the Standard & Landing Page content types involves mapping data from the Context module to Layout Builder section data, which is done as a separate migration task after the nodes themselves have been migrated.

Managing the particulars of each custom components' logic in this single, necessarily large migration task requires an architecture that facilitates debugging and easily following the lifecycle of a given component's transformation.

## Decision

Provide separate traits for each custom component that may be `use`d by the `Layouts` process plugin. In each trait, define methods for extracting the source data and for transforming it into a format that can be loaded in version 3.

Provide separate helper class for similar tasks (preparing a link, getting the destination media ID from the source FID), but minimize the amount of abstraction so that the developer can more easily debug an individual component in the trait.

## Consequences

- Developers may easily add breakpoints/debugging output for a single component (rather than getting output from all components when debugging).
- This will require writing more duplicate method names and foregoing the advantages of class inheritance.
