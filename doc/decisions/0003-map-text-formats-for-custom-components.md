# 3. Map text formats for custom components

Date: 2020-09-10

## Status

Accepted

## Context

Version 2 custom components' copy fields default to `filtered_html` and include processing logic to explicitly hide the CKEditor toolbar. The rationale is to discourage -- but not disallow -- formatted text. Content editors can also switch the text format to `full_html` or `plain_text`.

Version 3 adopts the new, core-provided `restricted_html` (no CKEditor toolbar) as the default text format for custom components copy fields, but still allows content editors to switch text formats. Additionally, Version 3 replaces `filtered_html` with `flex_html`, which is intended to provide feature parity.

## Decision

If the source data copy format is set to `plain_text` or `full_html` preserve that text format choice in the migration. Otherwise migrate to `flex_html`.

## Consequences

- Copy entered in version 2 with the `filtered_html` format will migrate to `flex_html` for parity with formatting.
    - and content editors will see the CKEditor toolbar displayed above the copy input.
    - but newly created content will default to `restricted_html` (no toolbar and more markup limitations)
- Copy referencing core default formats (`plain_text`, `full_html`) will migrate as-is.
- Copy referencing any other text formats (i.e., custom) will be set to `flex_html`.
- Developers who wish to map custom formats will override this default method.