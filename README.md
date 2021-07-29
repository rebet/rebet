# Rebet

 [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
 [![Build Status](https://api.travis-ci.com/rebet/rebet.svg?branch=master)](https://travis-ci.com/rebet/rebet)
 [![codecov](https://codecov.io/gh/rebet/rebet/branch/master/graph/badge.svg)](https://codecov.io/gh/rebet/rebet)

Rebet is a PHP framework for small or middle web applications.  
It is currently under development and has not been released yet.

## Local Environment Unit Tests Guaid

The following assumes that Docker and Docker Compose are already installed.

```sh
docker-compose run --rm composer install --ignore-platform-reqs
docker-compose up -d
docker-compose exec php vender/bin/phpunit -d memory_limit=256M tests/src/
```

But the `docker-compose` command can sometimes feel lengthy.
So we have prepared `.bash_aliases` that defines abbreviated commands, please use them as follows if necessary.

```sh
. .bash_aliases
composer install
up
phpunit tests/src/
```

Before running a unit test, you need to run the `up` and `composer install` commands (once only).
You can also destroy the docker container created with the `down` command when you are done.
And you can use these alias commands.

| Aliases     | Full Commands                                                     |
| :---------- | :---------------------------------------------------------------- |
| `up`        | `docker-compose up -d`                                            |
| `down`      | `docker-compose down --volumes --remove-orphans`                  |
| `composer`  | `docker-compose run --rm composer --ignore-platform-reqs`         |
| `phpunit`   | `docker-compose exec php vender/bin/phpunit -d memory_limit=256M` |
| `psysh`     | `docker-compose exec php vender/bin/psysh`                        |
| `build`     | `docker-compose build`                                            |
| `build-all` | `docker-compose build php sqlite sqlsrv`                          |
