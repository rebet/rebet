<?php
namespace App\Controller;

use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Controller;

/**
 * @Channel("web")
 */
class TopController extends Controller
{
    public function index()
    {
        return 'Top: index';
    }

    public function withParam($id)
    {
        return "Top: withParam - {$id}";
    }
}
