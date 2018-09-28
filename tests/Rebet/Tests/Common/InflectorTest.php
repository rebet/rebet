<?php
namespace Rebet\Tests\Common;

use Rebet\Tests\RebetTestCase;
use Rebet\Common\Inflector;
use Rebet\Config\Config;

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/3.0/en/development/testing.html
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * InflectorTest Class
 *
 * This class based on Cake\Test\TestCase\Utility\InflectorTest of cakephp/cakephp ver 3.6.11.
 *
 * Function diffs between CakePHP and Rebet are like below;
 *  - remove: tests about slug() deprecated function on CakePHP (deprecated CakePHP 3.2.7)
 *  - change: rename test method to use underscore
 *
 * @see https://github.com/cakephp/cakephp/blob/3.6.11/tests/TestCase/Utility/InflectorTest.php
 * @see https://github.com/cakephp/cakephp/blob/3.6.11/LICENSE
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class InflectorTest extends RebetTestCase
{
    /**
     * A list of chars to test transliteration.
     *
     * @var array
     */
    public static $maps = [
        'de' => [ /* German */
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'ẞ' => 'SS'
        ],
        'latin' => [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ă' => 'A', 'Æ' => 'AE', 'Ç' =>
            'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
            'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ő' => 'O', 'Ø' => 'O',
            'Ș' => 'S', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ű' => 'U',
            'Ý' => 'Y', 'Þ' => 'TH', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'å' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' =>
            'o', 'ô' => 'o', 'õ' => 'o', 'ő' => 'o', 'ø' => 'o', 'ș' => 's', 'ț' => 't', 'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y'
        ],
        'tr' => [ /* Turkish */
            'ş' => 's', 'Ş' => 'S', 'ı' => 'i', 'İ' => 'I', 'ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G'
        ],
        'uk' => [ /* Ukrainian */
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G', 'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g'
        ],
        'cs' => [ /* Czech */
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z', 'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T',
            'Ů' => 'U', 'Ž' => 'Z'
        ],
        'pl' => [ /* Polish */
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z', 'Ą' => 'A', 'Ć' => 'C', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'O', 'Ś' => 'S',
            'Ź' => 'Z', 'Ż' => 'Z'
        ],
        'ro' => [ /* Romanian */
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't', 'Ţ' => 'T', 'ţ' => 't'
        ],
        'lv' => [ /* Latvian */
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z', 'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'I',
            'Ķ' => 'K', 'Ļ' => 'L', 'Ņ' => 'N', 'Š' => 'S', 'Ū' => 'U', 'Ž' => 'Z'
        ],
        'lt' => [ /* Lithuanian */
            'ą' => 'a', 'č' => 'c', 'ę' => 'e', 'ė' => 'e', 'į' => 'i', 'š' => 's', 'ų' => 'u', 'ū' => 'u', 'ž' => 'z',
            'Ą' => 'A', 'Č' => 'C', 'Ę' => 'E', 'Ė' => 'E', 'Į' => 'I', 'Š' => 'S', 'Ų' => 'U', 'Ū' => 'U', 'Ž' => 'Z'
        ]
    ];

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Config::clear();
    }

    /**
     * testInflectingSingulars method
     *
     * @dataProvider singularizeProvider
     * @return void
     */
    public function test_InflectingSingulars($singular, $plural)
    {
        $this->assertEquals($singular, Inflector::singularize($plural));
    }

    /**
     * Data provider for testing singularize()
     *
     * @return array
     */
    public function singularizeProvider()
    {
        return [
            ['categoria', 'categorias'],
            ['menu', 'menus'],
            ['news', 'news'],
            ['food_menu', 'food_menus'],
            ['Menu', 'Menus'],
            ['FoodMenu', 'FoodMenus'],
            ['house', 'houses'],
            ['powerhouse', 'powerhouses'],
            ['quiz', 'quizzes'],
            ['Bus', 'Buses'],
            ['bus', 'buses'],
            ['matrix_row', 'matrix_rows'],
            ['matrix', 'matrices'],
            ['vertex', 'vertices'],
            ['index', 'indices'],
            ['Alias', 'Aliases'],
            ['Alias', 'Alias'],
            ['Media', 'Media'],
            ['NodeMedia', 'NodeMedia'],
            ['alumnus', 'alumni'],
            ['bacillus', 'bacilli'],
            ['cactus', 'cacti'],
            ['focus', 'foci'],
            ['fungus', 'fungi'],
            ['nucleus', 'nuclei'],
            ['octopus', 'octopuses'],
            ['radius', 'radii'],
            ['stimulus', 'stimuli'],
            ['syllabus', 'syllabi'],
            ['terminus', 'termini'],
            ['virus', 'viruses'],
            ['person', 'people'],
            ['glove', 'gloves'],
            ['dove', 'doves'],
            ['life', 'lives'],
            ['knife', 'knives'],
            ['wolf', 'wolves'],
            ['slave', 'slaves'],
            ['shelf', 'shelves'],
            ['taxi', 'taxis'],
            ['tax', 'taxes'],
            ['Tax', 'Taxes'],
            ['AwesomeTax', 'AwesomeTaxes'],
            ['fax', 'faxes'],
            ['wax', 'waxes'],
            ['niche', 'niches'],
            ['cave', 'caves'],
            ['grave', 'graves'],
            ['wave', 'waves'],
            ['bureau', 'bureaus'],
            ['genetic_analysis', 'genetic_analyses'],
            ['doctor_diagnosis', 'doctor_diagnoses'],
            ['paranthesis', 'parantheses'],
            ['Cause', 'Causes'],
            ['colossus', 'colossuses'],
            ['diagnosis', 'diagnoses'],
            ['basis', 'bases'],
            ['analysis', 'analyses'],
            ['curve', 'curves'],
            ['cafe', 'cafes'],
            ['roof', 'roofs'],
            ['foe', 'foes'],
            ['database', 'databases'],
            ['cookie', 'cookies'],
            ['thief', 'thieves'],
            ['potato', 'potatoes'],
            ['hero', 'heroes'],
            ['buffalo', 'buffaloes'],
            ['baby', 'babies'],
            ['tooth', 'teeth'],
            ['goose', 'geese'],
            ['foot', 'feet'],
            ['objective', 'objectives'],
            ['archive', 'archives'],
            ['brief', 'briefs'],
            ['quota', 'quotas'],
            ['curve', 'curves'],
            ['body_curve', 'body_curves'],
            ['metadata', 'metadata'],
            ['files_metadata', 'files_metadata'],
            ['address', 'addresses'],
            ['sieve', 'sieves'],
            ['blue_octopus', 'blue_octopuses'],
            ['chef', 'chefs'],
            ['', ''],
            ['cache', 'caches'],
        ];
    }

    /**
     * Test that overlapping irregulars don't collide.
     *
     * @return void
     */
    public function test_SingularizeMultiWordIrregular()
    {
        Inflector::rules('irregular', [
            'pregunta_frecuente' => 'preguntas_frecuentes',
            'categoria_pregunta_frecuente' => 'categorias_preguntas_frecuentes',
        ]);
        $this->assertEquals('pregunta_frecuente', Inflector::singularize('preguntas_frecuentes'));
        $this->assertEquals(
            'categoria_pregunta_frecuente',
            Inflector::singularize('categorias_preguntas_frecuentes')
        );
        $this->assertEquals(
            'faq_categoria_pregunta_frecuente',
            Inflector::singularize('faq_categorias_preguntas_frecuentes')
        );
    }

    /**
     * testInflectingPlurals method
     *
     * @dataProvider pluralizeProvider
     * @return void
     */
    public function test_InflectingPlurals($plural, $singular)
    {
        $this->assertEquals($plural, Inflector::pluralize($singular));
    }

    /**
     * Data provider for testing pluralize()
     *
     * @return array
     */
    public function pluralizeProvider()
    {
        return [
            ['axmen', 'axman'],
            ['men', 'man'],
            ['women', 'woman'],
            ['humans', 'human'],
            ['axmen', 'axman'],
            ['men', 'man'],
            ['women', 'woman'],
            ['humans', 'human'],
            ['categorias', 'categoria'],
            ['houses', 'house'],
            ['powerhouses', 'powerhouse'],
            ['Buses', 'Bus'],
            ['buses', 'bus'],
            ['menus', 'menu'],
            ['news', 'news'],
            ['food_menus', 'food_menu'],
            ['Menus', 'Menu'],
            ['FoodMenus', 'FoodMenu'],
            ['quizzes', 'quiz'],
            ['matrix_rows', 'matrix_row'],
            ['matrices', 'matrix'],
            ['vertices', 'vertex'],
            ['indices', 'index'],
            ['Aliases', 'Alias'],
            ['Aliases', 'Aliases'],
            ['Media', 'Media'],
            ['NodeMedia', 'NodeMedia'],
            ['alumni', 'alumnus'],
            ['bacilli', 'bacillus'],
            ['cacti', 'cactus'],
            ['foci', 'focus'],
            ['fungi', 'fungus'],
            ['nuclei', 'nucleus'],
            ['octopuses', 'octopus'],
            ['radii', 'radius'],
            ['stimuli', 'stimulus'],
            ['syllabi', 'syllabus'],
            ['termini', 'terminus'],
            ['viruses', 'virus'],
            ['people', 'person'],
            ['people', 'people'],
            ['gloves', 'glove'],
            ['crises', 'crisis'],
            ['taxes', 'tax'],
            ['waves', 'wave'],
            ['bureaus', 'bureau'],
            ['cafes', 'cafe'],
            ['roofs', 'roof'],
            ['foes', 'foe'],
            ['cookies', 'cookie'],
            ['wolves', 'wolf'],
            ['thieves', 'thief'],
            ['potatoes', 'potato'],
            ['heroes', 'hero'],
            ['buffaloes', 'buffalo'],
            ['teeth', 'tooth'],
            ['geese', 'goose'],
            ['feet', 'foot'],
            ['objectives', 'objective'],
            ['briefs', 'brief'],
            ['quotas', 'quota'],
            ['curves', 'curve'],
            ['body_curves', 'body_curve'],
            ['metadata', 'metadata'],
            ['files_metadata', 'files_metadata'],
            ['stadia', 'stadia'],
            ['Addresses', 'Address'],
            ['sieves', 'sieve'],
            ['blue_octopuses', 'blue_octopus'],
            ['chefs', 'chef'],
            ['', ''],
            ['pokemon', 'pokemon']
        ];
    }

    /**
     * Test that overlapping irregulars don't collide.
     *
     * @return void
     */
    public function test_PluralizeMultiWordIrregular()
    {
        Inflector::rules('irregular', [
            'pregunta_frecuente' => 'preguntas_frecuentes',
            'categoria_pregunta_frecuente' => 'categorias_preguntas_frecuentes',
        ]);
        $this->assertEquals('preguntas_frecuentes', Inflector::pluralize('pregunta_frecuente'));
        $this->assertEquals(
            'categorias_preguntas_frecuentes',
            Inflector::pluralize('categoria_pregunta_frecuente')
        );
        $this->assertEquals(
            'faq_categorias_preguntas_frecuentes',
            Inflector::pluralize('faq_categoria_pregunta_frecuente')
        );
    }

    /**
     * testInflectingMultiWordIrregulars
     *
     * @return void
     */
    public function test_InflectingMultiWordIrregulars()
    {
        // unset the default rules in order to avoid them possibly matching
        // the words in case the irregular regex won't match, the tests
        // should fail in that case
        Inflector::rules('plural', [
            ['rules', []],
        ]);
        Inflector::rules('singular', [
            ['rules', []],
        ]);

        $this->assertEquals('wisdom tooth', Inflector::singularize('wisdom teeth'));
        $this->assertEquals('wisdom-tooth', Inflector::singularize('wisdom-teeth'));
        $this->assertEquals('wisdom_tooth', Inflector::singularize('wisdom_teeth'));

        $this->assertEquals('sweet potatoes', Inflector::pluralize('sweet potato'));
        $this->assertEquals('sweet-potatoes', Inflector::pluralize('sweet-potato'));
        $this->assertEquals('sweet_potatoes', Inflector::pluralize('sweet_potato'));
    }

    /**
     * testUnderscore method
     *
     * @return void
     */
    public function test_Underscore()
    {
        $this->assertSame('test_thing', Inflector::underscore('TestThing'));
        $this->assertSame('test_thing', Inflector::underscore('testThing'));
        $this->assertSame('test_thing_extra', Inflector::underscore('TestThingExtra'));
        $this->assertSame('test_thing_extra', Inflector::underscore('testThingExtra'));
        $this->assertSame('test_this_thing', Inflector::underscore('test-this-thing'));
        $this->assertSame('test_thing_extrå', Inflector::underscore('testThingExtrå'));

        // Identical checks test the cache code path.
        $this->assertSame('test_thing', Inflector::underscore('TestThing'));
        $this->assertSame('test_thing', Inflector::underscore('testThing'));
        $this->assertSame('test_thing_extra', Inflector::underscore('TestThingExtra'));
        $this->assertSame('test_thing_extra', Inflector::underscore('testThingExtra'));
        $this->assertSame('test_thing_extrå', Inflector::underscore('testThingExtrå'));

        // Test stupid values
        $this->assertSame('', Inflector::underscore(''));
        $this->assertSame('0', Inflector::underscore(0));
        $this->assertSame('', Inflector::underscore(false));
    }

    /**
     * testDasherized method
     *
     * @return void
     */
    public function test_Dasherized()
    {
        $this->assertSame('test-thing', Inflector::dasherize('TestThing'));
        $this->assertSame('test-thing', Inflector::dasherize('testThing'));
        $this->assertSame('test-thing-extra', Inflector::dasherize('TestThingExtra'));
        $this->assertSame('test-thing-extra', Inflector::dasherize('testThingExtra'));
        $this->assertSame('test-this-thing', Inflector::dasherize('test_this_thing'));

        // Test stupid values
        $this->assertSame('', Inflector::dasherize(null));
        $this->assertSame('', Inflector::dasherize(''));
        $this->assertSame('0', Inflector::dasherize(0));
        $this->assertSame('', Inflector::dasherize(false));
    }

    /**
     * Demonstrate the expected output for bad inputs
     *
     * @return void
     */
    public function test_Camelize()
    {
        $this->assertSame('TestThing', Inflector::camelize('test_thing'));
        $this->assertSame('Test-thing', Inflector::camelize('test-thing'));
        $this->assertSame('TestThing', Inflector::camelize('test thing'));

        $this->assertSame('Test_thing', Inflector::camelize('test_thing', '-'));
        $this->assertSame('TestThing', Inflector::camelize('test-thing', '-'));
        $this->assertSame('TestThing', Inflector::camelize('test thing', '-'));

        $this->assertSame('Test_thing', Inflector::camelize('test_thing', ' '));
        $this->assertSame('Test-thing', Inflector::camelize('test-thing', ' '));
        $this->assertSame('TestThing', Inflector::camelize('test thing', ' '));

        $this->assertSame('TestPlugin.TestPluginComments', Inflector::camelize('TestPlugin.TestPluginComments'));
    }

    /**
     * testVariableNaming method
     *
     * @return void
     */
    public function test_VariableNaming()
    {
        $this->assertEquals('testField', Inflector::variable('test_field'));
        $this->assertEquals('testFieLd', Inflector::variable('test_fieLd'));
        $this->assertEquals('testField', Inflector::variable('test field'));
        $this->assertEquals('testField', Inflector::variable('Test_field'));
    }

    /**
     * testClassNaming method
     *
     * @return void
     */
    public function test_ClassNaming()
    {
        $this->assertEquals('ArtistsGenre', Inflector::classify('artists_genres'));
        $this->assertEquals('FileSystem', Inflector::classify('file_systems'));
        $this->assertEquals('News', Inflector::classify('news'));
        $this->assertEquals('Bureau', Inflector::classify('bureaus'));
    }

    /**
     * testTableNaming method
     *
     * @return void
     */
    public function test_TableNaming()
    {
        $this->assertEquals('artists_genres', Inflector::tableize('ArtistsGenre'));
        $this->assertEquals('file_systems', Inflector::tableize('FileSystem'));
        $this->assertEquals('news', Inflector::tableize('News'));
        $this->assertEquals('bureaus', Inflector::tableize('Bureau'));
    }

    /**
     * testHumanization method
     *
     * @return void
     */
    public function test_Humanization()
    {
        $this->assertEquals('Posts', Inflector::humanize('posts'));
        $this->assertEquals('Posts Tags', Inflector::humanize('posts_tags'));
        $this->assertEquals('File Systems', Inflector::humanize('file_systems'));
        $this->assertSame('', Inflector::humanize(null));
        $this->assertSame('', Inflector::humanize(false));
        $this->assertSame('Hello Wörld', Inflector::humanize('hello_wörld'));
        $this->assertSame('福岡 City', Inflector::humanize('福岡_city'));
    }

    /**
     * testCustomPluralRule method
     *
     * @return void
     */
    public function test_CustomPluralRule()
    {
        Inflector::rules('plural<', [['/^(custom)$/i', '\1izables']]);
        Inflector::rules('uninflected', ['uninflectable']);

        $this->assertEquals('customizables', Inflector::pluralize('custom'));
        $this->assertEquals('uninflectable', Inflector::pluralize('uninflectable'));

        Inflector::rules('plural', [['/^(alert)$/i', '\1ables']]);
        Inflector::rules('irregular', ['amaze' => 'amazable', 'phone' => 'phonezes']);
        Inflector::rules('uninflected', ['noflect', 'abtuse']);
        $this->assertEquals('noflect', Inflector::pluralize('noflect'));
        $this->assertEquals('abtuse', Inflector::pluralize('abtuse'));
        $this->assertEquals('alertables', Inflector::pluralize('alert'));
        $this->assertEquals('amazable', Inflector::pluralize('amaze'));
        $this->assertEquals('phonezes', Inflector::pluralize('phone'));
    }

    /**
     * testCustomSingularRule method
     *
     * @return void
     */
    public function test_CustomSingularRule()
    {
        Inflector::rules('uninflected', ['singulars']);
        Inflector::rules('singular', [['/(eple)r$/i', '\1'], ['/(jente)r$/i', '\1']]);
        
        $this->assertEquals('eple', Inflector::singularize('epler'));
        $this->assertEquals('jente', Inflector::singularize('jenter'));

        Inflector::rules('singular<', [['/^(bil)er$/i', '\1'], ['/^(inflec|contribu)tors$/i', '\1ta']]);
        Inflector::rules('irregular', ['spinor' => 'spins']);

        $this->assertEquals('spinor', Inflector::singularize('spins'));
        $this->assertEquals('inflecta', Inflector::singularize('inflectors'));
        $this->assertEquals('contributa', Inflector::singularize('contributors'));
        $this->assertEquals('singulars', Inflector::singularize('singulars'));
    }

    /**
     * test that setting new rules clears the inflector caches.
     *
     * @return void
     */
    public function test_RulesClearsCaches()
    {
        $this->assertEquals('Banana', Inflector::singularize('Bananas'));
        $this->assertEquals('bananas', Inflector::tableize('Banana'));
        $this->assertEquals('Bananas', Inflector::pluralize('Banana'));

        Inflector::rules('singular<', [['/(.*)nas$/i', '\1zzz']]);
        $this->assertEquals('Banazzz', Inflector::singularize('Bananas'), 'Was inflected with old rules.');

        Inflector::rules('plural<', [['/(.*)na$/i', '\1zzz']]);
        Inflector::rules('irregular', ['corpus' => 'corpora']);
        $this->assertEquals('Banazzz', Inflector::pluralize('Banana'), 'Was inflected with old rules.');
        $this->assertEquals('corpora', Inflector::pluralize('corpus'), 'Was inflected with old irregular form.');
    }

    /**
     * Test resetting inflection rules.
     *
     * @return void
     */
    public function test_CustomRuleWithReset()
    {
        $uninflected = ['atlas', 'lapis', 'onibus', 'pires', 'virus', '.*x'];
        $pluralIrregular = ['as' => 'ases'];

        Inflector::rules('singular!', [['/^(.*)(a|e|o|u)is$/i', '\1\2l']]);
        Inflector::rules('plural!', [['/^(.*)(a|e|o|u)l$/i', '\1\2is']]);
        Inflector::rules('uninflected!', $uninflected);
        Inflector::rules('irregular!', $pluralIrregular);

        $this->assertEquals('Alcoois', Inflector::pluralize('Alcool'));
        $this->assertEquals('Atlas', Inflector::pluralize('Atlas'));
        $this->assertEquals('Alcool', Inflector::singularize('Alcoois'));
        $this->assertEquals('Atlas', Inflector::singularize('Atlas'));
    }
}