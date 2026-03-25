# Maxfielder

[status](https://elkuku.github.io/maxfielder/)

This is a PHP frontend for the [maxfield](https://github.com/tvwenger/maxfield) python script.

Plan and Play.

### Local DEV setup

* Clone the repo then use the `bin/install` command or execute the script content manually.
* Use `symfony console user-admin` to create an admin user.
* Use the `bin/start` and `bin/stop` scripts to start and stop the environment.

```shell
bin/start
```
```shell
bin/stop
```

### Import

Two import formats are supported. Paste the JSON data into the corresponding field on the import page.

**Multi Export JSON** (portal multi export by modkin)

Use the IITC plugin to export portals in JSON format:

* https://raw.githubusercontent.com/IITC-CE/Community-plugins/master/dist/modkin/multi_export.user.js

Expected format:
```json
[{"guid":"...","title":"Portal Name","coordinates":{"lat":0.0,"lng":0.0},"image":"..."}]
```

**KExport** (IITC export by elkuku)

Use the IITC community plugin:

* https://iitc.app/community_plugins#export-by-elkuku

Expected format:
```json
[{"guid":"...","title":"Portal Name","lat":0.0,"lng":0.0,"image":"..."}]
```

Both formats support an optional **Import Images** checkbox to download portal images, and a **Force Update** checkbox to overwrite existing waypoints with matching coordinates.

### Maxfield generator options

The generator behaviour is controlled via `.env.local` variables:

| Variable | Default | Description |
|---|---|---|
| `APP_DOCKER_CONTAINER` | _(empty)_ | Docker image ID of the maxfield container (e.g. `db53e0717976`). When set, Docker is used to run the generator. |
| `MAXFIELDS_EXEC` | `maxfield-plan` | Path/name of the maxfield executable (used when not running via Docker). |
| `MAXFIELD_VERSION` | `4` | Maxfield version. Values `< 4` use the legacy Python CLI flags (`-d`, `-f`, `-n`). Version `4+` uses `--outdir`, `--num_agents`, etc. |
| `USE_PHP_MAXFIELD` | `false` | Set to `true` or `1` to use the built-in PHP maxfield implementation (`bin/console maxfield:plan`) instead of the external script or Docker container. |
| `INTEL_URL` | _(empty)_ | Base URL for Ingress Intel links embedded in the portal list (e.g. `https://intel.ingress.com/intel`). |
| `GOOGLE_API_KEY` / `GOOGLE_API_SECRET` | _(empty)_ | Google Maps API credentials. When provided, they are passed to the external maxfield command for map generation. |

Generator options available on the generate page:

| Option | Flag passed | Description |
|---|---|---|
| Skip plots | `--skip_plots` | Skip generating static field plot images. |
| Skip step plots | `--skip_step_plots` | Skip generating per-step plot images. |

### Maxfield docker container
There is a maxfield docker container at https://hub.docker.com/r/nikp3h/maxfield to use it in this frontend create a
`.env.local` file and set the `APP_DOCKER_CONTAINER` env var to the id of the cloned image.
(Find it using `docker images`)

e.g.
```dotenv
APP_DOCKER_CONTAINER=db53e0717976
```
----

Happy fielding `=;)`

https://leaflet-extras.github.io/leaflet-providers/preview/
