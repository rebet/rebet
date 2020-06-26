<?php
namespace Rebet\Tests\Annotation;

use Rebet\Annotation\AnnotationReader;
use Rebet\Tests\RebetTestCase;

class AnnotationReaderTest extends RebetTestCase
{
    public function test_getShared()
    {
        $this->assertInstanceOf(AnnotationReader::class, AnnotationReader::getShared());
    }
}
