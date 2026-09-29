<?php

declare(strict_types=1);

namespace TestApp\Different;

use Rebet\Routing\Attribute\Channel;
use Rebet\Routing\Controller;

#[Channel("web")]
class DifferentNamespaceController extends Controller
{
    public function foo()
    {
        return 'Different: foo';
    }
}
