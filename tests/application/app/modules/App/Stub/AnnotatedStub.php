<?php
namespace App\Stub;

use Rebet\Auth\Annotation\Guard;
use Rebet\Auth\Annotation\Role;
use Rebet\Database\Annotation\Defaults;
use Rebet\Database\Annotation\PrimaryKey;
use Rebet\Database\Annotation\Table;
use Rebet\Database\Annotation\Unmap;
use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Routing\Annotation\Where;

/**
 * @Guard("a")
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
     * @PrimaryKey
     * @Defaults("now")
     * @Unmap
     */
    public $annotations;

    /**
     * No Annotaitons field
     */
    public $no_annotaions;

    /**
     * @Guard("b")
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
