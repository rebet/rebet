# =========================================================
# Here is an alias that is convenient for development.
# =========================================================
alias ls="ls --color=auto"
alias ll="ls -l"
alias phpunit="php -d memory_limit=256M -d xdebug.start_with_request=no vendor/bin/phpunit"
alias x-phpunit="php -d memory_limit=256M vendor/bin/phpunit"
alias psysh="php vendor/bin/psysh"

if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi
