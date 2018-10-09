<?php
namespace Rebet\Tests\Mock;

use Rebet\Routing\Controller;
use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\Where;

/**
 * @Surface("web")
 */
class DifferentNamespaceController extends Controller
{
    public function foo()
    {
        return 'Different: foo';
    }
}
