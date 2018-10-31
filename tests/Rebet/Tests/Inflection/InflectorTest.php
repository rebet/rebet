<?php
namespace Rebet\Tests\Inflection;

use Rebet\Config\Config;
use Rebet\Inflection\Inflector;
use Rebet\Tests\RebetTestCase;

/**
 * Tests for the fonction pluralize and singularize are borrowed from doctrine/inflector ver 1.3.x with some modifications.
 *
 * @see https://github.com/doctrine/inflector/blob/1.3.x/tests/Doctrine/Tests/Common/Inflector/InflectorTest.php
 * @see https://github.com/doctrine/inflector/blob/1.3.x/LICENSE
 */
class InflectorTest extends RebetTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_pascalize()
    {
        $this->assertNull(Inflector::pascalize(null));
        $this->assertSame('', Inflector::pascalize(''));
        $this->assertSame('PascalCase', Inflector::pascalize('PascalCase'));
        $this->assertSame('CamelCase', Inflector::pascalize('camelCase'));
        $this->assertSame('SnakeCase', Inflector::pascalize('snake_case'));
        $this->assertSame('KebabCase', Inflector::pascalize('kebab-case'));
        $this->assertSame('HumanReadableCase', Inflector::pascalize('Human Readable Case'));
        $this->assertSame('HumanReadableCase', Inflector::pascalize('Human readable case'));
        $this->assertSame('HumanReadableCase', Inflector::pascalize('human readable case'));
    }

    public function test_camelize()
    {
        $this->assertNull(Inflector::camelize(null));
        $this->assertSame('', Inflector::camelize(''));
        $this->assertSame('pascalCase', Inflector::camelize('PascalCase'));
        $this->assertSame('camelCase', Inflector::camelize('camelCase'));
        $this->assertSame('snakeCase', Inflector::camelize('snake_case'));
        $this->assertSame('kebabCase', Inflector::camelize('kebab-case'));
        $this->assertSame('humanReadableCase', Inflector::camelize('Human Readable Case'));
        $this->assertSame('humanReadableCase', Inflector::camelize('Human readable case'));
        $this->assertSame('humanReadableCase', Inflector::camelize('human readable case'));
    }

    public function test_snakize()
    {
        $this->assertNull(Inflector::snakize(null));
        $this->assertSame('', Inflector::snakize(''));
        $this->assertSame('pascal_case', Inflector::snakize('PascalCase'));
        $this->assertSame('camel_case', Inflector::snakize('camelCase'));
        $this->assertSame('snake_case', Inflector::snakize('snake_case'));
        $this->assertSame('kebab_case', Inflector::snakize('kebab-case'));
        $this->assertSame('human_readable_case', Inflector::snakize('Human Readable Case'));
        $this->assertSame('human_readable_case', Inflector::snakize('Human readable case'));
        $this->assertSame('human_readable_case', Inflector::snakize('human readable case'));
    }

    public function test_kebabize()
    {
        $this->assertNull(Inflector::kebabize(null));
        $this->assertSame('', Inflector::kebabize(''));
        $this->assertSame('pascal-case', Inflector::kebabize('PascalCase'));
        $this->assertSame('camel-case', Inflector::kebabize('camelCase'));
        $this->assertSame('snake-case', Inflector::kebabize('snake_case'));
        $this->assertSame('kebab-case', Inflector::kebabize('kebab-case'));
        $this->assertSame('human-readable-case', Inflector::kebabize('Human Readable Case'));
        $this->assertSame('human-readable-case', Inflector::kebabize('Human readable case'));
        $this->assertSame('human-readable-case', Inflector::kebabize('human readable case'));
    }

    public function test_humanize()
    {
        $this->assertNull(Inflector::humanize(null));
        $this->assertSame('', Inflector::humanize(''));
        $this->assertSame('Pascal Case', Inflector::humanize('PascalCase'));
        $this->assertSame('Camel Case', Inflector::humanize('camelCase'));
        $this->assertSame('Snake Case', Inflector::humanize('snake_case'));
        $this->assertSame('Kebab Case', Inflector::humanize('kebab-case'));
        $this->assertSame('Human Readable Case', Inflector::humanize('Human Readable Case'));
        $this->assertSame('Human Readable Case', Inflector::humanize('Human readable case'));
        $this->assertSame('Human Readable Case', Inflector::humanize('human readable case'));
    }

    public function test_capitalize()
    {
        $this->assertNull(Inflector::capitalize(null));
        $this->assertSame('', Inflector::capitalize(''));
        $this->assertSame('PascalCase', Inflector::capitalize('PascalCase'));
        $this->assertSame('CamelCase', Inflector::capitalize('camelCase'));
        $this->assertSame('Snake_case', Inflector::capitalize('snake_case'));
        $this->assertSame('Kebab-case', Inflector::capitalize('kebab-case'));
        $this->assertSame('Human Readable Case', Inflector::capitalize('Human Readable Case'));
        $this->assertSame('Human Readable Case', Inflector::capitalize('Human readable case'));
        $this->assertSame('Human Readable Case', Inflector::capitalize('human readable case'));
    }

    public function test_tableize()
    {
        $this->assertNull(Inflector::tableize(null));
        $this->assertSame('', Inflector::tableize(''));
        $this->assertSame('pascal_cases', Inflector::tableize('PascalCase'));
        $this->assertSame('camel_cases', Inflector::tableize('camelCase'));
        $this->assertSame('snake_cases', Inflector::tableize('snake_case'));
        $this->assertSame('kebab_cases', Inflector::tableize('kebab-case'));
        $this->assertSame('human_readable_cases', Inflector::tableize('Human Readable Case'));
        $this->assertSame('human_readable_cases', Inflector::tableize('Human readable case'));
        $this->assertSame('human_readable_cases', Inflector::tableize('human readable case'));
    }

    public function test_classify()
    {
        $this->assertNull(Inflector::classify(null));
        $this->assertSame('', Inflector::classify(''));
        $this->assertSame('PascalCase', Inflector::classify('PascalCases'));
        $this->assertSame('CamelCase', Inflector::classify('camelCases'));
        $this->assertSame('SnakeCase', Inflector::classify('snake_cases'));
        $this->assertSame('KebabCase', Inflector::classify('kebab-cases'));
        $this->assertSame('HumanReadableCase', Inflector::classify('Human Readable Cases'));
        $this->assertSame('HumanReadableCase', Inflector::classify('Human readable cases'));
        $this->assertSame('HumanReadableCase', Inflector::classify('human readable cases'));
    }

    /**
     * Singular & Plural test data. Returns an array of sample words.
     *
     * @return string[][]
     */
    public function dataSampleWords() : array
    {
        Inflector::reset();
        
        // In the format ['singular', 'plural']
        return [
            ['', ''],
            ['Abuse', 'Abuses'],
            ['AcceptanceCriterion', 'AcceptanceCriteria'],
            ['Alias', 'Aliases'],
            ['alumnus', 'alumni'],
            ['analysis', 'analyses'],
            ['aquarium', 'aquaria'],
            ['arch', 'arches'],
            ['atlas', 'atlases'],
            ['avalanche', 'avalanches'],
            ['axe', 'axes'],
            ['baby', 'babies'],
            ['bacillus', 'bacilli'],
            ['bacterium', 'bacteria'],
            ['bureau', 'bureaus'],
            ['bus', 'buses'],
            ['Bus', 'Buses'],
            ['cache', 'caches'],
            ['cactus', 'cacti'],
            ['cafe', 'cafes'],
            ['calf', 'calves'],
            ['categoria', 'categorias'],
            ['chateau', 'chateaux'],
            ['cherry', 'cherries'],
            ['child', 'children'],
            ['church', 'churches'],
            ['circus', 'circuses'],
            ['city', 'cities'],
            ['cod', 'cod'],
            ['cookie', 'cookies'],
            ['copy', 'copies'],
            ['crisis', 'crises'],
            ['criterion', 'criteria'],
            ['curriculum', 'curricula'],
            ['curve', 'curves'],
            ['data', 'data'],
            ['deer', 'deer'],
            ['demo', 'demos'],
            ['dictionary', 'dictionaries'],
            ['domino', 'dominoes'],
            ['dwarf', 'dwarves'],
            ['echo', 'echoes'],
            ['elf', 'elves'],
            ['emphasis', 'emphases'],
            ['family', 'families'],
            ['fax', 'faxes'],
            ['fish', 'fish'],
            ['flush', 'flushes'],
            ['fly', 'flies'],
            ['focus', 'foci'],
            ['foe', 'foes'],
            ['food_menu', 'food_menus'],
            ['FoodMenu', 'FoodMenus'],
            ['foot', 'feet'],
            ['fungus', 'fungi'],
            ['goose', 'geese'],
            ['glove', 'gloves'],
            ['gulf', 'gulfs'],
            ['grave', 'graves'],
            ['half', 'halves'],
            ['hero', 'heroes'],
            ['hippopotamus', 'hippopotami'],
            ['hoax', 'hoaxes'],
            ['house', 'houses'],
            ['human', 'humans'],
            ['identity', 'identities'],
            ['index', 'indices'],
            ['iris', 'irises'],
            ['kiss', 'kisses'],
            ['knife', 'knives'],
            ['larva', 'larvae'],
            ['leaf', 'leaves'],
            ['life', 'lives'],
            ['loaf', 'loaves'],
            ['man', 'men'],
            ['matrix', 'matrices'],
            ['matrix_row', 'matrix_rows'],
            ['medium', 'media'],
            ['memorandum', 'memoranda'],
            ['menu', 'menus'],
            ['Menu', 'Menus'],
            ['mess', 'messes'],
            ['moose', 'moose'],
            ['motto', 'mottoes'],
            ['mouse', 'mice'],
            ['neurosis', 'neuroses'],
            ['news', 'news'],
            ['niveau', 'niveaux'],
            ['NodeMedia', 'NodeMedia'],
            ['nucleus', 'nuclei'],
            ['oasis', 'oases'],
            ['octopus', 'octopuses'],
            ['pass', 'passes'],
            ['passerby', 'passersby'],
            ['person', 'people'],
            ['plateau', 'plateaux'],
            ['potato', 'potatoes'],
            ['powerhouse', 'powerhouses'],
            ['quiz', 'quizzes'],
            ['radius', 'radii'],
            ['reflex', 'reflexes'],
            ['roof', 'roofs'],
            ['runner-up', 'runners-up'],
            ['scarf', 'scarves'],
            ['scratch', 'scratches'],
            ['series', 'series'],
            ['sheep', 'sheep'],
            ['shelf', 'shelves'],
            ['shoe', 'shoes'],
            ['son-in-law', 'sons-in-law'],
            ['species', 'species'],
            ['splash', 'splashes'],
            ['spouse', 'spouses'],
            ['spy', 'spies'],
            ['stimulus', 'stimuli'],
            ['stitch', 'stitches'],
            ['story', 'stories'],
            ['syllabus', 'syllabi'],
            ['tax', 'taxes'],
            ['terminus', 'termini'],
            ['thesis', 'theses'],
            ['thief', 'thieves'],
            ['tomato', 'tomatoes'],
            ['tooth', 'teeth'],
            ['tornado', 'tornadoes'],
            ['try', 'tries'],
            ['vertex', 'vertices'],
            ['virus', 'viri'],
            ['valve', 'valves'],
            ['volcano', 'volcanoes'],
            ['wash', 'washes'],
            ['watch', 'watches'],
            ['wave', 'waves'],
            ['wharf', 'wharves'],
            ['wife', 'wives'],
            ['woman', 'women'],
            ['clothes', 'clothes'],
            ['pants', 'pants'],
            ['police', 'police'],
            ['scissors', 'scissors'],
            ['trousers', 'trousers'],
            ['dive', 'dives'],
            ['olive', 'olives'],
            // Uninflected words possibly not defined under singular/plural rules
            ["Amoyese", "Amoyese"],
            ["audio", "audio"],
            ["bison", "bison"],
            ["Borghese", "Borghese"],
            ["bream", "bream"],
            ["breeches", "breeches"],
            ["britches", "britches"],
            ["buffalo", "buffalo"],
            ["cantus", "cantus"],
            ["carp", "carp"],
            ["chassis", "chassis"],
            ["clippers", "clippers"],
            ["cod", "cod"],
            ["coitus", "coitus"],
            ["compensation", "compensation"],
            ["Congoese", "Congoese"],
            ["contretemps", "contretemps"],
            ["coreopsis", "coreopsis"],
            ["corps", "corps"],
            ["data", "data"],
            ["debris", "debris"],
            ["deer", "deer"],
            ["diabetes", "diabetes"],
            ["djinn", "djinn"],
            ["education", "education"],
            ["eland", "eland"],
            ["elk", "elk"],
            ["emoji", "emoji"],
            ["equipment", "equipment"],
            ["evidence", "evidence"],
            ["Faroese", "Faroese"],
            ["feedback", "feedback"],
            ["fish", "fish"],
            ["flounder", "flounder"],
            ["Foochowese", "Foochowese"],
            ["Furniture", "Furniture"],
            ["furniture", "furniture"],
            ["gallows", "gallows"],
            ["Genevese", "Genevese"],
            ["Genoese", "Genoese"],
            ["Gilbertese", "Gilbertese"],
            ["gold", "gold"],
            ["headquarters", "headquarters"],
            ["herpes", "herpes"],
            ["hijinks", "hijinks"],
            ["Hottentotese", "Hottentotese"],
            ["information", "information"],
            ["innings", "innings"],
            ["jackanapes", "jackanapes"],
            ["jedi", "jedi"],
            ["Kiplingese", "Kiplingese"],
            ["knowledge", "knowledge"],
            ["Kongoese", "Kongoese"],
            ["love", "love"],
            ["Lucchese", "Lucchese"],
            ["Luggage", "Luggage"],
            ["mackerel", "mackerel"],
            ["Maltese", "Maltese"],
            ["metadata", "metadata"],
            ["mews", "mews"],
            ["moose", "moose"],
            ["mumps", "mumps"],
            ["Nankingese", "Nankingese"],
            ["news", "news"],
            ["nexus", "nexus"],
            ["Niasese", "Niasese"],
            ["nutrition", "nutrition"],
            ["offspring", "offspring"],
            ["Pekingese", "Pekingese"],
            ["Piedmontese", "Piedmontese"],
            ["pincers", "pincers"],
            ["Pistoiese", "Pistoiese"],
            ["plankton", "plankton"],
            ["pliers", "pliers"],
            ["pokemon", "pokemon"],
            ["police", "police"],
            ["Portuguese", "Portuguese"],
            ["proceedings", "proceedings"],
            ["rabies", "rabies"],
            ["rain", "rain"],
            ["rhinoceros", "rhinoceros"],
            ["rice", "rice"],
            ["salmon", "salmon"],
            ["Sarawakese", "Sarawakese"],
            ["scissors", "scissors"],
            ["series", "series"],
            ["Shavese", "Shavese"],
            ["shears", "shears"],
            ["sheep", "sheep"],
            ["siemens", "siemens"],
            ["species", "species"],
            ["staff", "staff"],
            ["swine", "swine"],
            ["traffic", "traffic"],
            ["trousers", "trousers"],
            ["trout", "trout"],
            ["tuna", "tuna"],
            ["us", "us"],
            ["Vermontese", "Vermontese"],
            ["Wenchowese", "Wenchowese"],
            ["wheat", "wheat"],
            ["whiting", "whiting"],
            ["wildebeest", "wildebeest"],
            ["Yengeese", "Yengeese"],
            // Regex uninflected words
            ["sea bass", "sea bass"],
            ["sea-bass", "sea-bass"],
        ];
    }

    /**
     * @dataProvider dataSampleWords
     */
    public function testInflectingSingulars(string $singular, string $plural) : void
    {
        $this->assertEquals($singular, Inflector::singularize($plural), "'{$plural}' should be singularized to '{$singular}'");
    }

    /**
     * @dataProvider dataSampleWords
     */
    public function testInflectingPlurals(string $singular, string $plural) : void
    {
        $this->assertEquals($plural, Inflector::pluralize($singular), "'{$singular}' should be pluralized to '{$plural}'");
    }

    public function testCustomPluralRule() : void
    {
        Inflector::reset();
        Config::application([
            Inflector::class => [
                'plural' => [
                    'rules' => [
                        ['/^(custom)$/i', '\1izables'],
                    ],
                ],
            ]
        ]);
        $this->assertEquals(Inflector::pluralize('custom'), 'customizables');

        Config::application([
            Inflector::class => [
                'plural' => [
                    'uninflected' => [
                        'uninflectable',
                    ],
                ],
            ]
        ]);
        $this->assertEquals(Inflector::pluralize('uninflectable'), 'uninflectable');

        Config::application([
            Inflector::class => [
                'plural' => [
                    'rules'       => [['/^(alert)$/i', '\1ables']],
                    'uninflected' => ['noflect', 'abtuse'],
                    'irregular'   => ['amaze' => 'amazable', 'phone' => 'phonezes']
                ],
            ]
        ]);
        $this->assertEquals(Inflector::pluralize('noflect'), 'noflect');
        $this->assertEquals(Inflector::pluralize('abtuse'), 'abtuse');
        $this->assertEquals(Inflector::pluralize('alert'), 'alertables');
        $this->assertEquals(Inflector::pluralize('amaze'), 'amazable');
        $this->assertEquals(Inflector::pluralize('phone'), 'phonezes');
    }

    public function testCustomSingularRule() : void
    {
        Inflector::reset();
        Config::application([
            Inflector::class => [
                'singular' => [
                    'rules' => [['/(eple)r$/i', '\1'], ['/(jente)r$/i', '\1']]
                ],
            ]
        ]);
        $this->assertEquals(Inflector::singularize('epler'), 'eple');
        $this->assertEquals(Inflector::singularize('jenter'), 'jente');

        Config::application([
            Inflector::class => [
                'singular' => [
                    'rules'       => [['/^(bil)er$/i', '\1'], ['/^(inflec|contribu)tors$/i', '\1ta']],
                    'uninflected' => ['singulars'],
                    'irregular'   => ['spins' => 'spinor']
                ],
            ]
        ]);
        $this->assertEquals(Inflector::singularize('inflectors'), 'inflecta');
        $this->assertEquals(Inflector::singularize('contributors'), 'contributa');
        $this->assertEquals(Inflector::singularize('spins'), 'spinor');
        $this->assertEquals(Inflector::singularize('singulars'), 'singulars');
    }

    public function testCustomRuleWithReset() : void
    {
        Inflector::reset();
        $uninflected      = ['atlas', 'lapis', 'onibus', 'pires', 'virus', '.*x'];
        $plural_irregular = ['as' => 'ases'];
        Config::application([
            Inflector::class => [
                'singular!' => [
                    'rules'       => [['/^(.*)(a|e|o|u)is$/i', '\1\2l']],
                    'uninflected' => $uninflected,
                ],
                'plural!' => [
                    'rules'       => [['/^(.*)(a|e|o|u)l$/i', '\1\2is']],
                    'uninflected' => $uninflected,
                    'irregular'   => $plural_irregular
                ],
            ]
        ]);
        $this->assertEquals(Inflector::pluralize('Alcool'), 'Alcoois');
        $this->assertEquals(Inflector::pluralize('Atlas'), 'Atlas');
        $this->assertEquals(Inflector::singularize('Alcoois'), 'Alcool');
        $this->assertEquals(Inflector::singularize('Atlas'), 'Atlas');
    }
}
