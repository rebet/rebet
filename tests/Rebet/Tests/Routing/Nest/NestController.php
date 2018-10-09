<?php
namespace Rebet\Tests\Routing\Nest;

use Rebet\Routing\Controller;
use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Where;

/**
 * @Surface("web")
 */
class NestController extends Controller
{
    public function foo()
    {
        return 'Nest: foo';
    }
}
