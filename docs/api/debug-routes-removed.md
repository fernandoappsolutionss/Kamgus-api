# Debug Routes Removed

T1 removed unauthenticated or dangerous debug/migration routes from public route files.

| Route | Source | Disposition |
| --- | --- | --- |
| `GET /test_command` | `routes/web.php` | Removed. It executed Artisan from a web route. |
| `GET /generate_link` | `routes/web.php` | Removed. It exposed storage link generation from a web route. |
| `GET /api/v2/migrate_v1/*` | `routes/api/old_kamgus.php` | Removed. Legacy migration endpoints must not be callable through the public API. |
| `GET /api/v2/test` | `routes/api/old_kamgus.php` | Removed. Debug-only route. |
| `GET /api/v2/db/{id}` | `routes/api/drivers.php` | Removed. Debug database inspection route. |
| `GET /api/v2/finish_service` | `routes/api/drivers.php` | Removed. State-changing debug route. |
| `GET /api/v2/driver/notify/{id}` | `routes/api/drivers.php` | Removed. Push-notification debug route. |
| `GET /api/v2/old` / `GET /api/v2/old/{id?}` | `routes/api/drivers.php`, `routes/api/inviteds.php` | Removed. Legacy inspection endpoints. |
| `GET /api/v2/invited/cs/{id}` | `routes/api/inviteds.php` | Removed. Internal/debug invited service lookup. |
| `/api/v2/invited/services/test_refund` controller case | `app/Http/Controllers/V2/Inviteds/ServiceController.php` | Removed from `show()`. The route pattern no longer dispatches this debug action and falls through to 404. |

Verification criterion: route registration and URL probes are covered by `tests/Feature/T1SecurityTest.php`.
