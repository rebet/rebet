{env in='unittest'}
unittest
{/env}
{env in=['unittest','local']}
unittest or local
{/env}
{env in='production'}
production
{/env}
{env not_in='production'}
Not production.
{/env}