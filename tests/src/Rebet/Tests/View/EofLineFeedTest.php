<?php

declare(strict_types=1);

namespace Rebet\Tests\View;

use Rebet\Tests\RebetTestCase;
use Rebet\Tools\Enum\Enumeration;
use Rebet\Tools\Reflection\Reflector;
use Rebet\View\EofLineFeed;

class EofLineFeedTest extends RebetTestCase
{
    public function test_process(): void
    {
        $contents = "a\r\nb\r\n\r\n";
        $this->assertSame(null, EofLineFeed::Trim->process(null));
        $this->assertSame("a\r\nb", EofLineFeed::Trim->process($contents));
        $this->assertSame("a\r\nb\r\n\r\n", EofLineFeed::Keep->process($contents));
        $this->assertSame("a\r\nb\n", EofLineFeed::One->process($contents));
    }

    public function test_process_noEof(): void
    {
        $this->assertSame('ab', EofLineFeed::Keep->process('ab'));
        $this->assertSame('ab', EofLineFeed::Trim->process('ab'));
        $this->assertSame("ab\n", EofLineFeed::One->process('ab'));
        $this->assertSame("ab\n", EofLineFeed::One->process("ab\n"));
        $this->assertSame('', EofLineFeed::Trim->process("\r\n"));
        $this->assertSame("\n", EofLineFeed::One->process(''));
    }

    public function test_enum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, EofLineFeed::Keep);
        $this->assertInstanceOf(Enumeration::class, EofLineFeed::Keep);

        $this->assertSame([1, 2, 3], EofLineFeed::values());
        $this->assertSame(['Keep', 'Trim', 'One'], EofLineFeed::names());
        $this->assertSame([EofLineFeed::Keep, EofLineFeed::Trim, EofLineFeed::One], EofLineFeed::lists());
        $this->assertSame(EofLineFeed::Trim, EofLineFeed::from(2));
        $this->assertSame(EofLineFeed::One, EofLineFeed::valueOf(3));
        $this->assertSame(EofLineFeed::One, EofLineFeed::nameOf('One'));
        $this->assertNull(EofLineFeed::valueOf(4));
        $this->assertNull(EofLineFeed::tryFrom(4));
    }

    public function test_label_isNotTranslated(): void
    {
        // The label is not display text, so it is the name of case even if the locale is changed.
        $this->assertSame(['Keep', 'Trim', 'One'], EofLineFeed::labels());
        $this->assertSame('Trim', EofLineFeed::Trim->label());
        $this->assertSame('Trim', EofLineFeed::Trim->label('ja'));
    }

    public function test_convert(): void
    {
        // The value of the configuration file and the other sources can be converted to enum.
        $this->assertSame(EofLineFeed::Trim, Reflector::convert(2, EofLineFeed::class));
        $this->assertSame(EofLineFeed::Trim, Reflector::convert('2', EofLineFeed::class));
        $this->assertSame(EofLineFeed::One, Reflector::convert(EofLineFeed::One, EofLineFeed::class));
        $this->assertNull(Reflector::convert(9, EofLineFeed::class));
        $this->assertNull(Reflector::convert('trim', EofLineFeed::class));
    }
}
