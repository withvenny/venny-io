# app_venny_chat Endpoints

`app_venny_chat` owns conversational thread and message resources.

## Required headers

```text
Authorization: Bearer {api_key}
X-Venny-Session-Id: {session_id}
```

The API key scopes records to the authenticated app. The session identifies the current user so the API can enforce participant-level access.

## Resource endpoints

```text
GET     /threads
GET     /threads/:id
POST    /threads
PATCH   /threads/:id
DELETE  /threads/:id

GET     /messages?thread_id=:thread_id
GET     /messages/:id
POST    /messages
PATCH   /messages/:id
DELETE  /messages/:id
```

## Chatio facade endpoints

```text
GET     /chat/threads
POST    /threads/find-or-create-direct
GET     /chat/threads/:id/messages
POST    /chat/threads/:id/messages
PATCH   /messages/:id/read
```

## Access rules

```text
A user can only see threads where they are a participant.
A user can only see messages in threads where they are a participant.
A user can only delete their own messages.
A user can only archive a thread if they are a participant.
Collection reads are scoped to created_for_app_id from the API key.
```

## Soft-delete behavior

```text
DELETE /threads/:id   -> status = archived, active = 0
DELETE /messages/:id  -> status = archived, active = 0, message_body = [deleted]
```

## Direct thread behavior

`POST /threads/find-or-create-direct` finds an active direct-message thread for the current session user and a recipient. If one does not exist, it creates one only when the recipient is eligible through:

```text
accepted followship
or public/open profile
```

The endpoint uses `thread_attributes.kind = direct_message` and `thread_participants.users` to identify direct-message threads.

## Boundary

Chat does not own public profile discovery, followship state, reactions, delivery-provider execution, presence, or websocket transport.
