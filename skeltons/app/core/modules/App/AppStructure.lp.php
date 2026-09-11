<?php
declare(strict_types=1);

namespace App;

use Override;
use Rebet\Application\Structure;

/**
 * Application Structure Class For {! $code_name !} Application
 *
 * Define application structure settings.
 * NOTE: If you want to change project directories structure, you can do it by override methods of this class.
 */
class AppStructure extends Structure
{
    /**
     * Get environment file path
     * Defaultly this method return "{Structure::root()}/core/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function env(string|null $relative_path = null) : string
    {
        return parent::env($relative_path);
    }

    /**
     * Get application config path
     * Defaultly this method return "{Structure::root()}/core/configs/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function configs(string|null $relative_path = null) : string
    {
        return parent::configs($relative_path);
    }

    /**
     * Get application resources path
     * Defaultly this method return "{Structure::root()}/core/resources/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function resources(string|null $relative_path = null) : string
    {
        return parent::resources($relative_path);
    }

    /**
     * Get application routes configuration path
     * Defaultly this method return "{Structure::root()}/core/routes/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function routes(string|null $relative_path = null) : string
    {
        return parent::routes($relative_path);
    }

    /**
     * Get application views path
     * Defaultly this method return "{Structure::root()}/core/views/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function views(string|null $relative_path = null) : string
    {
        return parent::views($relative_path);
    }

    /**
     * Get public root path
     * Defaultly this method return "{Structure::root()}/public/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function public(string|null $relative_path = null) : string
    {
        return parent::public($relative_path);
    }

    /**
     * Get cache path
     * Defaultly this method return "{Structure::root()}/var/cache/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function cache(string|null $relative_path = null) : string
    {
        return parent::cache($relative_path);
    }

    /**
     * Get logs path
     * Defaultly this method return "{Structure::root()}/var/logs/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function logs(string|null $relative_path = null) : string
    {
        return parent::logs($relative_path);
    }

    /**
     * Get root storage path.
     * Defaultly this method return "{Structure::root()}/var/storage/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function storage(string|null $relative_path = null) : string
    {
        return parent::storage($relative_path);
    }

    /**
     * Get private storage path.
     * Defaultly this method return "{Structure::storage()}/private/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function privateStorage(string|null $relative_path = null) : string
    {
        return parent::privateStorage($relative_path);
    }

    /**
     * Get public storage path.
     * Defaultly this method return "{Structure::storage()}/public/{$relative_path}", you can override this method if you want.
     *
     * @param string|null $relative_path (default: null)
     * @return string
     */
    #[Override]
    public function publicStorage(string|null $relative_path = null) : string
    {
        return parent::publicStorage($relative_path);
    }

    /**
     * Get root storage url.
     * Defaultly this method return "/storage", you can override this method if you want.
     *
     * @return string
     */
    #[Override]
    public function storageUrl() : string
    {
        return parent::storageUrl();
    }
}
