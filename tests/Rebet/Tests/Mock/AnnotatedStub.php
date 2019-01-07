<?php
namespace Rebet\Tests\Mock;

use Rebet\Auth\Annotation\Authenticator;
use Rebet\Auth\Annotation\Role;

/**
 * @Authenticator("a")
 * @Role("a")
 */
class AnnotatedStub
{
    /**
     */
    public $annotations;

    /**
     * No Annotaitons field
     */
    public $no_annotaions;

    /**
     * @Authenticator("b")
     * @Role({"b","c"})
     */
    public function annotations()
    {
    }

    /**
     * No Annotaitons method
     */
    public function noAnnotaions()
    {
    }
}
