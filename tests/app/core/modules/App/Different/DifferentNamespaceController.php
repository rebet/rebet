<?php
namespace App\Different;

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
