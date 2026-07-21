# Communications endpoints

The `app_venny_communications` cartridge exposes four resource families.

| Domain | Endpoints | Purpose |
|---|---|---|
| Communications | `/communications` | Create and process communication intents. |
| Deliveries | `/deliveries` | Track channel-specific delivery records. |
| Threads | `/threads` | Track conversations and participants. |
| Messages | `/messages` | Track messages inside threads. |

All endpoints require Bearer auth and follow the standard Venny I/O response envelope.

DELETE requests soft-archive records by setting `status = archived` and `active = 0`.
