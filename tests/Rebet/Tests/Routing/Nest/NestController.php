<?php
namespace Rebet\Tests\Routing\Nest;

use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Controller;

/**
 * @Channel("web")
 */
class NestController extends Controller
{
    public function foo()
    {
        return 'Nest: foo';
    }
}
