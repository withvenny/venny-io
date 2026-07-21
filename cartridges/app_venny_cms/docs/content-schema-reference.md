# `/content` schema reference

The `app_venny_cms` cartridge maps `/content` to the `content` table.

## Required create fields

```text
content_slug
content_title
content_body
```

## Optional create fields

```text
content_id
content_attributes
content_startdate
content_enddate
content_description
content_tags
content_template
content_visible
created_by_user_id
created_for_app_id
event_id
process_id
access
status
active
```

## JSON fields

```text
content_attributes  JSON object, defaults to {}
content_tags        JSON array or JSON object, nullable
```

## Date behavior

`content_startdate` and `content_enddate` accept parseable timestamps. If both are provided, `content_enddate` must be greater than or equal to `content_startdate`.

## Slug behavior

`content_slug` is normalized to lowercase and URL-safe format.

Example:

```text
"Homepage Hero" -> "homepage-hero"
```
