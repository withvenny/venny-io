# Sign up for updates

`POST /sign-up-for-updates` captures lightweight update-signup forms into the `contacts` table.

## Behavior

- Creates a new `contacts` record every time.
- Allows duplicate contacts because different campaigns and website views may capture the same person again.
- Requires only an email address.
- Accepts optional name and phone values.
- Requires source view metadata so the origin of the capture is auditable.

## Required field

- `email`

## Source view

Provide at least one of:

- `view`
- `source_view`
- `route`
- `contact_source.view`

The source view is stored in both `contact_source.view` and `contact_attributes.source_view`.
