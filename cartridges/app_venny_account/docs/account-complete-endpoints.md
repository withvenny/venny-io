# Account endpoints

The account cartridge gives the UI one safe account settings surface while preserving table separation.

- `persons` stores personal information like name, email, phone, and address.
- `users` stores login/account fields like email, username, password hash, display name, and opt-ins.
- `profiles` stores public or social metadata like screen name, bio, avatar URL, theme, and handles.

The active session determines the user. Request bodies cannot select another user.
