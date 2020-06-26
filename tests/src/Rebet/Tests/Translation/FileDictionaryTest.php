<?php
namespace Rebet\Tests\Translation;

use Rebet\Application\App;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetTestCase;
use Rebet\Translation\FileDictionary;

class FileDictionaryTest extends RebetTestCase
{
    private $resource;
    /**
     * @var FileDictionary
     */
    private $dictionary;

    public function setUp()
    {
        parent::setUp();
        $this->dictionary = new FileDictionary();
    }

    public function test___construct()
    {
        $this->assertInstanceOf(FileDictionary::class, new FileDictionary());
    }

    public function test_addLibraryResource()
    {
        $label_key = Gender::class.'.label.1';
        $mark_key  = Gender::class.'.mark.1';

        $label = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);
        $mark  = $this->dictionary->sentence('enum', $mark_key, ['ja', 'en']);
        $this->assertSame('男性', $label);
        $this->assertSame(null, $mark);
        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('attribute', 'ja'));

        $attribute = $this->dictionary->sentence('attribute', 'translate', ['ja', 'en']);
        $this->assertSame('翻訳', $attribute);
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $this->assertInstanceOf(FileDictionary::class, $this->dictionary->addLibraryResource(App::structure()->resources('/adhoc/Translation/FileDictionary'), 'enum'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $label = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);
        $mark  = $this->dictionary->sentence('enum', $mark_key, ['ja', 'en']);
        $this->assertSame('男性', $label);
        $this->assertSame('♂', $mark);
        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));
    }

    public function test_clear()
    {
        $label_key = Gender::class.'.label.1';
        $label     = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);
        $label     = $this->dictionary->sentence('enum', $label_key, ['de', 'en']);
        $attribute = $this->dictionary->sentence('attribute', 'translate', ['ja', 'en']);
        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('enum', 'de'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $this->assertInstanceOf(FileDictionary::class, $this->dictionary->clear('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('enum', 'de'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $label = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);
        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('enum', 'de'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $this->assertInstanceOf(FileDictionary::class, $this->dictionary->clear('enum'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'de'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $label = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);
        $label = $this->dictionary->sentence('enum', $label_key, ['de', 'en']);
        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertTrue($this->dictionary->isLoaded('enum', 'de'));
        $this->assertTrue($this->dictionary->isLoaded('attribute', 'ja'));

        $this->assertInstanceOf(FileDictionary::class, $this->dictionary->clear());
        $this->assertFalse($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'de'));
        $this->assertFalse($this->dictionary->isLoaded('attribute', 'ja'));
    }

    public function test_isLoaded()
    {
        $this->assertFalse($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'de'));
        $this->assertFalse($this->dictionary->isLoaded('attribute', 'ja'));

        $label_key = Gender::class.'.label.1';
        $label     = $this->dictionary->sentence('enum', $label_key, ['ja', 'en']);

        $this->assertTrue($this->dictionary->isLoaded('enum', 'ja'));
        $this->assertFalse($this->dictionary->isLoaded('enum', 'de'));
        $this->assertFalse($this->dictionary->isLoaded('attribute', 'ja'));
    }

    public function test_grammar()
    {
        $this->dictionary->addLibraryResource(App::structure()->resources('/adhoc/Translation/FileDictionary'));
        $this->assertSame(', ', $this->dictionary->grammar('unittest', 'delimiter', 'en'));
        $this->assertSame(':first_name :last_name', $this->dictionary->grammar('unittest', 'full_name', 'en'));
        $this->assertSame(':last_name:first_name', $this->dictionary->grammar('unittest', 'full_name', 'ja'));
        $this->assertSame(null, $this->dictionary->grammar('unittest', 'invlid', 'en'));
        $this->assertSame('default', $this->dictionary->grammar('unittest', 'invlid', 'en', 'default'));
    }

    /**
     * @dataProvider dataSentences
     */
    public function test_sentence($expect, string $key, array $locales, $selector = null, bool $recursive = true)
    {
        $this->dictionary->addLibraryResource(App::structure()->resources('/adhoc/Translation/FileDictionary'));
        $this->assertSame($expect, $this->dictionary->sentence('unittest', $key, $locales, $selector, $recursive));
    }

    public function dataSentences() : array
    {
        return [
            [null, 'invalid', ['ja', 'en']],

            ['こんにちは Rebet。', 'hello', ['ja', 'en']],
            ['Hello Rebet.'     , 'hello', ['en']      ],

            [null            , 'welcom', ['ja']      ],
            ['Welcom :name !', 'welcom', ['ja', 'en']],
            ['Welcom :name !', 'welcom', ['en']      ],

            ['This is *(othre).', 'select_by_number', ['en']   ],
            ['This is 1.'       , 'select_by_number', ['en'], 1],
            ['This is 2.'       , 'select_by_number', ['en'], 2],
            ['This is 3.'       , 'select_by_number', ['en'], 3],
            ['This is *(othre).', 'select_by_number', ['en'], 4],

            ['This is *(othre).', 'select_by_number_using_pipe', ['en']   ],
            ['This is 1.'       , 'select_by_number_using_pipe', ['en'], 1],
            ['This is 2.'       , 'select_by_number_using_pipe', ['en'], 2],
            ['This is 3.'       , 'select_by_number_using_pipe', ['en'], 3],
            ['This is *(othre).', 'select_by_number_using_pipe', ['en'], 4],

            ['This is *,*(othre).'              , 'select_by_number_range', ['en']    ],
            ['This is less than or equal 9.'    , 'select_by_number_range', ['en'],  1],
            ['This is less than or equal 9.'    , 'select_by_number_range', ['en'],  9],
            ['This is 10 to 19.'                , 'select_by_number_range', ['en'], 10],
            ['This is 10 to 19.'                , 'select_by_number_range', ['en'], 19],
            ['This is greater than or equal 20.', 'select_by_number_range', ['en'], 20],
            ['This is greater than or equal 20.', 'select_by_number_range', ['en'], 29],

            [null        , 'select_by_number_without_other', ['en']   ],
            ['This is 1.', 'select_by_number_without_other', ['en'], 1],
            [null        , 'select_by_number_without_other', ['en'], 2],

            ['This is *(othre).', 'select_by_word', ['en']        ],
            ['This is one.'     , 'select_by_word', ['en'], 'one' ],
            ['This is some.'    , 'select_by_word', ['en'], 'some'],
            ['This is all.'     , 'select_by_word', ['en'], 'all' ],
            ['This is *(othre).', 'select_by_word', ['en'], 'foo' ],

            ['This is *(othre).'    , 'select_by_word_multi', ['en']              ],
            ['This is today or now.', 'select_by_word_multi', ['en'], 'today'     ],
            ['This is today or now.', 'select_by_word_multi', ['en'], 'now'       ],
            ['This is *(othre).'    , 'select_by_word_multi', ['en'], '2010-01-02'],

            [null          , 'select_by_word_withot_other', ['en']       ],
            ['This is one.', 'select_by_word_withot_other', ['en'], 'one'],
            [null          , 'select_by_word_withot_other', ['en'], 'two'],

            ['root',          'recursive', ['en']],
            ['a'   ,        'a.recursive', ['en']],
            ['b'   ,        'b.recursive', ['en']],
            ['root',        'c.recursive', ['en']],
            ['A'   , 'custom.a.recursive', ['en']],
            ['b'   , 'custom.b.recursive', ['en']],
            ['root', 'custom.c.recursive', ['en']],

            ['root',          'recursive', ['en'], null, false],
            ['a'   ,        'a.recursive', ['en'], null, false],
            ['b'   ,        'b.recursive', ['en'], null, false],
            [null  ,        'c.recursive', ['en'], null, false],
            ['A'   , 'custom.a.recursive', ['en'], null, false],
            [null  , 'custom.b.recursive', ['en'], null, false],
            [null  , 'custom.c.recursive', ['en'], null, false],

            ['baz', 'group.parent.child', ['en']   ],
            ['foo', 'group.parent.child', ['en'], 1],
            ['bar', 'group.parent.child', ['en'], 2],
            ['baz', 'group.parent.child', ['en'], 3],
        ];
    }
}
