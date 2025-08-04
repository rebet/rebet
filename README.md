# Rebet

 [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
 [![Build Status](https://api.travis-ci.com/rebet/rebet.svg?branch=master)](https://travis-ci.com/rebet/rebet)
 [![codecov](https://codecov.io/gh/rebet/rebet/branch/master/graph/badge.svg)](https://codecov.io/gh/rebet/rebet)

Rebet is a PHP framework for small or middle web applications.  
It is currently under development and has not been released yet.

## Local Environment Unit Tests Guaid

The following assumes that Docker, Docker Compose and VSCode (include Remote Containers extension) are already installed.

1. Open a project by `File > Open Folder... > /path/to/rebet` with VSCode
2. Click `><` (green background button) at the bottom left of VSCode and select `Reopen in Container...` under `Remote-Containers` 
    * `Starting Dev Container (show log): Starting container` to appear in the lower right corner as the Docker container build begins, so wait for finish build.
3. And then type command below,
   ```sh
   phpunit
   ```

We have prepared `.bash_aliases` that defines abbreviated alias commands like below,

| Aliases          | Full Commands                                                                                              |
| :--------------- | :--------------------------------------------------------------------------------------------------------- |
| `ls`             | `ls --color=auto`                                                                                          |
| `ll`             | `ls -l`                                                                                                    |
| `la`             | `ls -A`                                                                                                    |
| `php-cs-fixer-f` | `php-cs-fixer fix --config=/workspace/.php-cs-fixer.dist.php`                                              |
| `psysh-app`      | `psysh /workspace/vendor/autoload.php`                                                                     |
| `phpunit`        | `php -d memory_limit=256M -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit`                   |
| `phpunit-sof`    | `php -d memory_limit=256M -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit --stop-on-failure` |
| `phpunit-xd`     | `php -d memory_limit=256M /workspace/vendor/bin/phpunit`                                                   |
