# 4. Migrate Standard Page & Landing Page to Flex Page

Date: 2020-09-10

## Status

Accepted

## Context

In version 2, two content types, "Standard Page" and "Landing Page," split the responsibility for flexible page layout. In version 3, parity is provided by the single "Flex Page" content type.

Version 2 content is stored as fields on the node. Layout is stored in a Context entity reference.

Version 3's "Flex page" defines no node fields. Content is instead entered as blocks (reusable or inline) referenced in the Layout storage.

## Decision

Define separate migration tasks for Standard Page and Landing Page that only migrate first-class node data (title, created, updated, author, published) and add content-type specific choices (display page title, display breadcrumbs).

Define a subsequent, single migration task covering both Standard Pages and Landing pages that retrieves source field and layout data, then transforms the fields into inline blocks and the templated layout into Layout Builder sections, and saves the existing node.

## Consequences

- Standard node migration tasks that require no customization and can be defined in .yml configuration are separate from the highly customized migration tasks related to Layout Editor to Layout Builder that require a processor class.
- The entire source-to-destination layout mapping can be viewed, evaluated, and adjusted in a single location, regardless of whether the source page was a Standard Page or Landing Page.
