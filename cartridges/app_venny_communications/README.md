# app_venny_communications

`app_venny_communications` owns the Venny I/O communications domain.

This cartridge manages outbound communication orchestration, delivery records, conversation threads, and messages. It is metadata/API focused. Provider-specific send behavior belongs in integration cartridges such as `int_twilio`, `int_sendgrid`, or `int_mailgun`.

## Endpoint families

```text
GET     /communications
GET     /communications/:id
POST    /communications
PATCH   /communications/:id
DELETE  /communications/:id

GET     /deliveries
GET     /deliveries/:id
POST    /deliveries
PATCH   /deliveries/:id
DELETE  /deliveries/:id

GET     /threads
GET     /threads/:id
POST    /threads
PATCH   /threads/:id
DELETE  /threads/:id

GET     /messages
GET     /messages/:id
POST    /messages
PATCH   /messages/:id
DELETE  /messages/:id
```

## Boundaries

This cartridge owns:

```text
communications
deliveries
threads
messages
```

This cartridge does not own:

```text
/persons, /users, /profiles      -> app_venny_identity
/contacts, /companies, /deals    -> app_venny_crm
/content, /assets                -> app_venny_cms
/provider send APIs              -> integration cartridges
```
