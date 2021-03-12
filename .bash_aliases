# =========================================================
# Here is an alias that is convenient for development using docker-compose.
# Please use it as needed.
# =========================================================
alias up="docker-compose up -d"
alias down="docker-compose down --volumes --remove-orphans"
alias composer="docker-compose run --rm composer"
alias phpunit="docker-compose run --rm php vendor/bin/phpunit -d memory_limit=256M"
alias psysh="docker-compose run --rm php vendor/bin/psysh"
alias build="docker-compose build php sqlite sqlsrv"
