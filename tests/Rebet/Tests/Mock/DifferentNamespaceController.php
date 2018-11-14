<?php
namespace Rebet\Tests\Mock;

use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Controller;

/**
 * @Channel("web")
 */
class DifferentNamespaceController extends Controller
{
    public function foo()
    {
        return 'Different: foo';
    }
}
