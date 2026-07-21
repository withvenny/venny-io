# posts Schema Reference

Owned by `app_venny_posts`.

Primary key: `post_id`

## JSONB fields

- `post_attributes`
- `post_images`

## Core fields

- `post_object_id` — object/feed this post belongs to.
- `post_parent_object_id` — optional parent object for reply-style relationships.
- `post_body` — required post body.
- `post_closed` — boolean flag for closing replies or interaction.
- `post_deleted` — boolean flag for product-level deletion state.

Standard fields include `created_by_user_id`, `created_for_app_id`, `access`, `status`, `active`, `time_started`, and `time_updated`.
