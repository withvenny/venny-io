# Venny I/O Bootstrap Restore Patch

Restores `config/bootstrap.php`, which is required by `public/index.php` before route dispatch.

The Heroku error this fixes is:

```text
Failed opening required '/app/config/bootstrap.php'
```

Apply from the repository root:

```bash
unzip -l ~/Downloads/venny-bootstrap-restore-patch.zip
unzip -o ~/Downloads/venny-bootstrap-restore-patch.zip -d .
git status
git diff -- config/bootstrap.php
git add config/bootstrap.php
git commit -m "Restore bootstrap config"
git push heroku master
```
