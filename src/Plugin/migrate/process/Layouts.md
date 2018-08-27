## A D7 Page Builder layout, from the `context` table, will look like this

```php
    [fieldblock-208a521aa519bc1ed37d8992aeffae83] => Array
        (
            [module] => fieldblock
            [delta] => 208a521aa519bc1ed37d8992aeffae83
            [region] => main_content_top_left
            [weight] => 0
        )

    [fieldblock-fda604d130a57f15015895c8268f20d2] => Array
        (
            [module] => fieldblock
            [delta] => fda604d130a57f15015895c8268f20d2
            [region] => main_content_top_left
            [weight] => 1
        )

    [fieldblock-c4c10ae36665adf0e722e7e3f4be74d4] => Array
        (
            [module] => fieldblock
            [delta] => c4c10ae36665adf0e722e7e3f4be74d4
            [region] => main_content_top_right
            [weight] => 0
        )

    [fieldblock-9c079efa827f76dea650869c5d2631e6] => Array
        (
            [module] => fieldblock
            [delta] => 9c079efa827f76dea650869c5d2631e6
            [region] => content_bottom
            [weight] => 0
        )
```

## A D8 Layout Builder *section*, from the `node__layout_builder__layout` table, will look like this:

```php
__PHP_Incomplete_Class Object
(
    [__PHP_Incomplete_Class_Name] => Drupal\layout_builder\Section
    [layoutId:protected] => layout_utexas_50_50
    [layoutSettings:protected] => Array
        (
        )

    [components:protected] => Array
        (
            [ec93b42c-0668-4b92-ae60-d9091684440f] => __PHP_Incomplete_Class Object
                (
                    [__PHP_Incomplete_Class_Name] => Drupal\layout_builder\SectionComponent
                    [uuid:protected] => ec93b42c-0668-4b92-ae60-d9091684440f
                    [region:protected] => left
                    [configuration:protected] => Array
                        (
                            [id] => field_block:node:utexas_flex_page:field_flex_page_pu
                            [label] => Promo Units
                            [provider] => layout_builder
                            [label_display] => 0
                            [formatter] => Array
                                (
                                    [label] => hidden
                                    [type] => entity_reference_revisions_entity_view
                                    [settings] => Array
                                        (
                                            [view_mode] => default
                                        )

                                    [third_party_settings] => Array
                                        (
                                        )

                                )

                            [context_mapping] => Array
                                (
                                    [entity] => layout_builder.entity
                                )

                        )

                    [weight:protected] => 0
                    [additional:protected] => Array
                        (
                        )

                )

            [93535cd8-5043-4b58-a823-ba60b483a794] => __PHP_Incomplete_Class Object
                (
                    [__PHP_Incomplete_Class_Name] => Drupal\layout_builder\SectionComponent
                    [uuid:protected] => 93535cd8-5043-4b58-a823-ba60b483a794
                    [region:protected] => right
                    [configuration:protected] => Array
                        (
                            [id] => field_block:node:utexas_flex_page:field_flex_page_pl
                            [label] => Promo List
                            [provider] => layout_builder
                            [label_display] => 0
                            [formatter] => Array
                                (
                                    [label] => hidden
                                    [type] => entity_reference_revisions_entity_view
                                    [settings] => Array
                                        (
                                            [view_mode] => default
                                        )

                                    [third_party_settings] => Array
                                        (
                                        )

                                )

                            [context_mapping] => Array
                                (
                                    [entity] => layout_builder.entity
                                )

                        )

                    [weight:protected] => 0
                    [additional:protected] => Array
                        (
                        )

                )

        )

)
```