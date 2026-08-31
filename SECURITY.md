# Security

Venny I/O configuration secrets belong in deployment environment variables, not in the repository.

Do not commit passwords, passphrases, API keys, access tokens, private keys, credential-bearing database URLs, provider credentials, or production infrastructure addresses.

Before opening a pull request or publishing a release, run:

```bash
php scripts/audit-public-release.php
php scripts/validate-cartridges.php
```

The repository audit checks the working tree for high-confidence credential formats, literal credential-like example values, non-loopback IPv4 addresses, credential-bearing URLs, private-key blocks, and project-specific brand leakage.

If you discover a security issue, use GitHub's private vulnerability reporting or security advisory workflow rather than posting credential material in a public issue.
