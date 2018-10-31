<?php
namespace Rebet\Tests\Routing\Nest;

use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Controller;

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
