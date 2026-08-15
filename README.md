# User Limit

Hard cap on the total number of users per Nextcloud instance. Hooks
`BeforeUserCreatedEvent` and aborts creation once the configured ceiling
is reached - covers the web UI, `occ user:add`, the provisioning API and
the `registration` app.

## Install

### From the App Store (comming soon)

Not available for now

### Manually (Docker)

Copy the folder into the app container's `custom_apps/`, then enable:

```bash
docker cp userlimit <app_container>:/var/www/html/custom_apps/userlimit
docker exec -u www-data <app_container> php occ app:enable userlimit
```

For a compose setup that does not persist `/var/www/html`, bind-mount the
app read-only instead of copying, and enable it from a startup hook:

```yaml
volumes:
  - ./apps/userlimit:/var/www/html/custom_apps/userlimit:ro
```

```sh
# hooks/before-starting/10-userlimit.sh   (idempotent, safe every boot)
php occ app:enable userlimit || true
php occ config:app:set userlimit limit --value=5
```

## Configure

Default cap is 5 (enforced immediately on enable)

```bash
occ config:app:set userlimit limit --value=10
occ config:app:get userlimit limit
occ config:app:set userlimit limit --value=0   # 0 or negative = disabled
```

## Logs

Structured entries land in `nextcloud.log`. The blocked-attempt line logs
at `warning` (visible at the default loglevel); permit/debug lines need
`loglevel` <= 1 in `config.php`.

```bash
occ log:watch
grep userlimit /var/www/html/data/nextcloud.log | jq
```

## Behaviour and caveats

- `countUsers()` counts every backend, LDAP included. To cap local
  accounts only, count the Database backend specifically.
- Enforces at the application layer. Raw SQL writes to `oc_users` bypass
  it; add a DB trigger if that is in your threat model.
- Existing accounts above the cap are never touched — the hook only fires
  on new creations.
- NC 34's typed AppConfig is strict about value types; the listener
  degrades to the default on `AppConfigTypeConflictException`. To clear a
  bad value: `occ config:app:delete userlimit limit`, then set it again.

