<?php
namespace App\Stub;

use Rebet\Auth\Attribute\Guard;
use Rebet\Auth\Attribute\Role;
use Rebet\Database\Attribute\Defaults;
use Rebet\Database\Attribute\PrimaryKey;
use Rebet\Database\Attribute\Table;
use Rebet\Database\Attribute\Unmap;
use Rebet\Routing\Attribute\AliasOnly;
use Rebet\Routing\Attribute\Channel;
use Rebet\Routing\Attribute\Method;
use Rebet\Routing\Attribute\NotRouting;
use Rebet\Routing\Attribute\Where;

#[Guard("a")]
#[Role("a")]
#[AliasOnly]
#[Channel("web")]
#[Method("GET", "PUT")]
#[Where(id: "[0-9]+")]
#[Table("table_name")]
class AttributedStub
{
    #[PrimaryKey]
    #[Defaults("now")]
    #[Unmap]
    public $attributes;

    /**
     * No Attributes field
     */
    public $no_attributes;

    #[Guard("b")]
    #[Role("b", "c")]
    #[AliasOnly]
    #[Channel("web", "api")]
    #[Method("HEAD", "OPTION")]
    #[NotRouting]
    #[Where(seq: "[0-9]+", code: "[a-zA-Z]+")]
    public function attributes()
    {
    }

    /**
     * No Attributes method
     */
    public function noAttributes()
    {
    }
}
