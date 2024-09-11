# Maxfielder

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
Use portal multi export by modkin using the json format

* https://raw.githubusercontent.com/IITC-CE/Community-plugins/master/dist/modkin/multi_export.user.js

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
