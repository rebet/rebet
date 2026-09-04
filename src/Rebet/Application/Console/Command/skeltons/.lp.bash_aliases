# =========================================================
# Here is an alias that is convenient for development using docker-compose.
# Please use it as needed.
# =========================================================
alias up="docker-compose up -d"
alias down="docker-compose down --volumes --remove-orphans"
alias composer="docker-compose run --rm composer --ignore-platform-reqs"
alias phpunit="docker-compose exec php vendor/bin/phpunit -d memory_limit=256M"
alias psysh="docker-compose exec php vendor/bin/psysh"
alias nginx="docker-compose exec nginx bash"
alias build="docker-compose build"
alias build-all="docker-compose build php nginx{% if $database == 'sqlite' %} sqlite{% endif %}"
