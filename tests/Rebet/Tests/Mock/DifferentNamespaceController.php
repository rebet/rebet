<?php
namespace Rebet\Tests\Mock;

use Rebet\Routing\Annotation\Surface;
use Rebet\Routing\Controller;

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
