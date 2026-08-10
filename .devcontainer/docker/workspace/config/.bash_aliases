# =========================================================
# Here are aliases these are convenient for development.
# =========================================================
# Show/Edit/Source this file
# ---------------------------------------------------------
alias ba-show="cat ~/.bash_aliases | egrep '^(alias|function)'"
alias ba-edit="vim ~/.bash_aliases"
alias ba-load="source ~/.bash_aliases"

# ---------------------------------------------------------
# Utility aliases
# ---------------------------------------------------------
alias ls="ls --color=auto"
alias ll="ls -l"
alias la='ls -A'

# ---------------------------------------------------------
# Add Composer global vendor bin directory to PATH
# ---------------------------------------------------------
export PATH=$PATH:~/.composer/vendor/bin

# ---------------------------------------------------------
# php-cs-fixer
# ---------------------------------------------------------
alias php-cs-fixer-f="php-cs-fixer fix --config=/workspace/.php-cs-fixer.dist.php"

# ---------------------------------------------------------
# phpstan
# ---------------------------------------------------------
alias phpstan-a="phpstan analyze --memory-limit=512M -c /workspace/phpstan.neon"

# ---------------------------------------------------------
# psysh
# ---------------------------------------------------------
alias psysh-app="psysh /workspace/vendor/autoload.php"

# ---------------------------------------------------------
# phpunit
# ---------------------------------------------------------
alias phpunit="php -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit"
alias phpunit-sod="php -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit --stop-on-defect"
alias phpunit-xd="php /workspace/vendor/bin/phpunit"
alias phpunit-t="php /workspace/vendor/bin/phpunit --display-phpunit-deprecations --display-deprecations --stop-on-defect"
