<?php
namespace Rebet\Tests\Mock\Controller;

use Rebet\Routing\Annotation\AliasOnly;
use Rebet\Routing\Annotation\Channel;
use Rebet\Routing\Annotation\Method;
use Rebet\Routing\Annotation\NotRouting;
use Rebet\Routing\Annotation\Where;
use Rebet\Routing\Controller;
use Rebet\Tests\Mock\Enum\Gender;

/**
 * @Channel("web")
 * @Where({"user_id": "/^[0-9]+$/"})
 */
class TestController extends Controller
{
    public function index()
    {
        return 'Controller: index';
    }

    private function privateCall()
    {
        return 'Controller: privateCall';
    }

    protected function protectedCall()
    {
        return 'Controller: protectedCall';
    }

    public function publicCall()
    {
        return 'Controller: publicCall';
    }

    public function withParam($id)
    {
        return "Controller: withParam - {$id}";
    }

    public function withOptionalParam($id = 'default')
    {
        return "Controller: withOptionalParam - {$id}";
    }

    public function withMultiParam($from, $to)
    {
        return "Controller: withMultiParam - {$from} to {$to}";
    }

    public function withMultiInvertParam($to, $from)
    {
        return "Controller: withMultiInvertParam - {$from} to {$to}";
    }

    public function withConvertEnumParam(Gender $gender)
    {
        return "Controller: withConvertEnumParam - {$gender}";
    }

    /**
     * @Channel("api")
     */
    public function annotationChannelApi()
    {
        return 'Controller: annotationChannelApi';
    }

    /**
     * @Method("GET")
     */
    public function annotationMethodGet()
    {
        return 'Controller: annotationMethodGet';
    }

    /**
     * @Where({"id": "/^[a-zA-Z]+$/"})
     */
    public function annotationWhere($id)
    {
        return "Controller: annotationWhere - {$id}";
    }

    public function annotationClassWhere($user_id)
    {
        return "Controller: annotationClassWhere - {$user_id}";
    }

    /**
     * @NotRouting
     */
    public function annotationNotRouting()
    {
        return "Controller: annotationNotRouting";
    }

    /**
     * @AliasOnly
     */
    public function annotationAliasOnly()
    {
        return "Controller: annotationAliasOnly";
    }
}
