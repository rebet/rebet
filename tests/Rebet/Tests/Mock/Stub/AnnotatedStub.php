<?php
namespace Rebet\Tests\Mock\Stub;

use Rebet\Auth\Annotation\Authenticator;
use Rebet\Auth\Annotation\Role;
use Rebet\Common\Annotation\Nest;
use Rebet\Database\Annotation\PhpType;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\DateTime\DateTime; // Use for Annotation
use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Routing\Annotation\Where;
use Rebet\Tests\Mock\User; // Use for Annotation

/**
 * @Authenticator("a")
 * @Role("a")
 * @AliasOnly
 * @Channel("web")
 * @Method({"GET","PUT"})
 * @Where({"id": "[0-9]+"})
 * @Table("table_name")
 */
class AnnotatedStub
{
    /**
     * @Nest(User::class)
     * @PrimaryKey
     * @PhpType(DateTime::class)
     * @Unmap
     */
    public $annotations;

    /**
     * No Annotaitons field
     */
    public $no_annotaions;

    /**
     * @Authenticator("b")
     * @Role({"b","c"})
     * @AliasOnly
     * @Channel(rejects={"web", "api"})
     * @Method(rejects={"HEAD", "OPTION"})
     * @NotRouting
     * @Where({"seq": "[0-9]+", "code": "[a-zA-Z]+"})
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
