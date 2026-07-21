# app_venny_chat

`app_venny_chat` provides lightweight conversational primitives for Venny I/O.

## Endpoint families

```text
/threads
/messages
```

## Chatio facade endpoints

```text
GET     /chat/threads
POST    /threads/find-or-create-direct
GET     /chat/threads/:id/messages
POST    /chat/threads/:id/messages
PATCH   /messages/:id/read
```

These facade routes keep the React reference app simple while still using the underlying `threads` and `messages` tables.

## Security model

Chat endpoints require both:

```text
Authorization: Bearer {api_key}
X-Venny-Session-Id: {session_id}
```

The Bearer key resolves the app/tenant through `created_for_app_id`. The session resolves the current user. Thread and message reads are scoped so the current session user must be a thread participant.

## Delete behavior

Deletes are soft deletes:

```text
status = archived
active = 0
```

Message deletes also replace `message_body` with `[deleted]` so the record can remain in the conversation history without exposing deleted content.

## Boundary

This cartridge owns threads and messages. It does not own public profiles, followships, reactions, provider delivery, presence, or websocket transport.

Provider-specific delivery, email, SMS, and notification tracking belong in `app_venny_communications` or future `int_*` cartridges.
