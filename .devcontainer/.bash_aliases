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
# psysh
# ---------------------------------------------------------
alias psysh-app="psysh /workspace/vendor/autoload.php"

# ---------------------------------------------------------
# phpunit
# ---------------------------------------------------------
alias phpunit="php -d memory_limit=256M -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit"
alias phpunit-sof="php -d memory_limit=256M -d xdebug.start_with_request=no /workspace/vendor/bin/phpunit --stop-on-failure"
alias phpunit-xd="php -d memory_limit=256M /workspace/vendor/bin/phpunit"
