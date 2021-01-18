<?php
namespace Rebet\Tests\Database\Ransack;

use Rebet\Database\Condition;
use Rebet\Database\Dao;
use Rebet\Database\Database;
use Rebet\Database\Exception\RansackException;
use Rebet\Database\Ransack\Ransack;
use Rebet\Tests\RebetDatabaseTestCase;

class RansackTest extends RebetDatabaseTestCase
{
    public function dataResolves() : array
    {
        return [
            [null, null, 'name'   , null],
            [null, null, 'name_eq', ''],
            [null, null, 'name_in', []],
            [
                '?age? > :age_gt',
                ['age_gt' => 0],
                'age_gt' , 0
            ],
            [
                '((?name? = :name_0 AND ?age? > :age_gt_0) OR (?name? = :name_1 AND ?age? > :age_gt_1))',
                ['name_0' => 'foo', 'age_gt_0' => 20, 'name_1' => 'bar', 'age_gt_1' => 18],
                0 , [['name' => 'foo', 'age_gt' => 20], ['name' => 'bar', 'age_gt' => 18]]
            ],
            [
                '((((?name? = :name_0_0) OR (?name? = :name_0_1)) AND ?age? > :age_gt_0) OR (?name? = :name_1 AND ?age? > :age_gt_1))',
                ['name_0_0' => 'foo', 'name_0_1' => 'bar', 'age_gt_0' => 20, 'name_1' => 'baz', 'age_gt_1' => 18],
                0 , [[[['name' => 'foo'], ['name' => 'bar']], 'age_gt' => 20], ['name' => 'baz', 'age_gt' => 18]]
            ],
            [
                '(((?last_name? = :name_0_0 OR ?first_name? = :name_0_1) AND ?age? > :age_gt_0) OR ((?last_name? = :name_1_0 OR ?first_name? = :name_1_1) AND ?age? > :age_gt_1))',
                ['name_0_0' => 'foo', 'name_0_1' => 'foo', 'age_gt_0' => 20, 'name_1_0' => 'bar', 'name_1_1' => 'bar', 'age_gt_1' => 18],
                0 , [['name' => 'foo', 'age_gt' => 20], ['name' => 'bar', 'age_gt' => 18]],
                ['name' => ['last_name', 'first_name']]
            ],
            [
                '?age? > :age_gt',
                ['age_gt' => 20],
                'age_gt' , 20, [],
                function (Ransack $ransack) : ?Condition {
                    return null;
                }
            ],
            [
                '?age? > :age_gt',
                ['age_gt' => 20],
                'age_gt' , 20, [],
                function (Ransack $ransack) : ?Condition {
                    return $ransack->convert();
                }
            ],
            [
                '?age? grater than :age_gt',
                ['age_gt' => 20],
                'age_gt' , 20, [],
                function (Ransack $ransack) : ?Condition {
                    if ($ransack->origin() === 'age_gt') {
                        return $ransack->convert('{col} grater than {val}');
                    }
                    return null;
                }
            ],
            [
                '?age? grater than :age_gt',
                ['age_gt' => 40],
                'age_gt' , 20, [],
                function (Ransack $ransack) : ?Condition {
                    if ($ransack->origin() === 'age_gt') {
                        return $ransack->convert('{col} grater than {val}', function ($v) { return $v * 2; });
                    }
                    return null;
                }
            ],
            [
                'age <> :bar',
                ['bar' => 20],
                'age_gt' , 20, [],
                function (Ransack $ransack) : ?Condition {
                    if ($ransack->origin() === 'age_gt') {
                        return new Condition('age <> :bar', ['bar' => $ransack->value(true)]);
                    }
                    return null;
                }
            ],
        ];
    }

    /**
     * @dataProvider dataResolves
     */
    public function test_resolve($expect_sql, $expect_params, $ransack_predicate, $value, array $alias = [], ?\Closure $extension = null)
    {
        $this->eachDb(function (Database $db) use ($expect_sql, $expect_params, $ransack_predicate, $value, $alias, $extension) {
            $condition = Ransack::resolve($db->driver(), $ransack_predicate, $value, $alias, $extension);
            if ($expect_sql === null) {
                $this->assertNull($condition);
            } else {
                $this->assertWildcardString($expect_sql, $condition->sql());
                $this->assertEquals($expect_params, $condition->params());
            }
        });
    }

    public function test_analyze()
    {
        $this->eachDb(function (Database $db) {
            $ransack = Ransack::analyze($db->driver(), 'name', 'John');
            $this->assertInstanceOf(Ransack::class, $ransack);
        });
    }

    public function test_analyze_exception()
    {
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Short predicates of 'in' and 'eq' can not contain 'any' and 'all' compound word.");

        Ransack::analyze(Dao::db()->driver(), 'name_any', 'John');
    }

    public function dataOrigins() : array
    {
        return [
            ['name'                , 'foo'         , []                                                           ],
            ['name'                , ['foo', 'bar'], []                                                           ],
            ['name_contains'       , 'foo'         , []                                                           ],
            ['name_contains_any'   , ['foo', 'bar'], []                                                           ],
            ['name_contains_any'   , 'foo bar'     , []                                                           ],
            ['name_contains_any_cs', ['foo', 'bar'], []                                     , ['mysql', 'mariadb']],
            ['name_contains_any_cs', ['foo', 'bar'], ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
        ];
    }

    /**
     * @dataProvider dataOrigins
     */
    public function test_origin($ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertSame($ransack_predicate, $ransack->origin());
        }, ...$dbs);
    }

    public function dataValues() : array
    {
        return [
            ['foo'                   , true , 'name'                , 'foo'              , []                                                           ],
            ['foo 100%'              , true , 'name'                , 'foo 100%'         , []                                                           ],
            [['foo', '100%']         , true , 'name'                , ['foo', '100%']    , []                                                           ],
            ['%foo%'                 , true , 'name_contains'       , 'foo'              , []                                                           ],
            ['foo'                   , false, 'name_contains'       , 'foo'              , []                                                           ],
            ['%foo 100|%%'           , true , 'name_contains'       , 'foo 100%'         , []                                                           ],
            ['foo 100%'              , false, 'name_contains'       , 'foo 100%'         , []                                                           ],
            [['%foo%']               , true , 'name_contains_any'   , 'foo'              , []                                                           ],
            ['foo'                   , false, 'name_contains_any'   , 'foo'              , []                                                           ],
            [['%foo%', '%100|%%']    , true , 'name_contains_any'   , 'foo 100%'         , []                                                           ],
            ['foo 100%'              , false, 'name_contains_any'   , 'foo 100%'         , []                                                           ],
            [['%foo%', '%100|%%']    , true , 'name_contains_any'   , ['foo', '100%']    , []                                                           ],
            [['foo', '100%']         , false, 'name_contains_any'   , ['foo', '100%']    , []                                                           ],
            [['%foo%', '%bar 100|%%'], true , 'name_contains_any'   , ['foo', 'bar 100%'], []                                                           ],
            [['foo', 'bar 100%']     , false, 'name_contains_any'   , ['foo', 'bar 100%'], []                                                           ],
            [['%foo%', '%100|%%']    , true , 'name_contains_any_cs', 'foo　 100%'       , []                                     , ['mysql', 'mariadb']],
            ['foo　 100%'            , false, 'name_contains_any_cs', 'foo　 100%'       , []                                     , ['mysql', 'mariadb']],
            [['%foo%', '%100|%%']    , true , 'name_contains_any_cs', "foo\t100%"        , ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
            ["foo\t100%"             , false, 'name_contains_any_cs', "foo\t100%"        , ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
        ];
    }

    /**
     * @dataProvider dataValues
     */
    public function test_value($expect, $convert, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $convert, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertEquals($expect, $ransack->value($convert));
        }, ...$dbs);
    }

    public function dataPredicates() : array
    {
        $this->setUp();
        $data = [
            ['eq'          , 'name'                , 'foo'         , []                                                           ],
            ['in'          , 'name'                , ['foo', 'bar'], []                                                           ],
            ['contains'    , 'name_contains'       , 'foo'         , []                                                           ],
            ['contains'    , 'name_contains_any'   , ['foo', 'bar'], []                                                           ],
            ['contains'    , 'name_contains_any'   , 'foo bar'     , []                                                           ],
            ['contains'    , 'name_contains_all'   , ['foo', 'bar'], []                                                           ],
            ['contains'    , 'name_contains_all'   , 'foo bar'     , []                                                           ],
            ['contains'    , 'name_contains_any_cs', ['foo', 'bar'], []                                     , ['mysql', 'mariadb']],
            ['contains'    , 'name_contains_any_cs', ['foo', 'bar'], ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
        ];

        $this->eachDb(function (Database $db) use (&$data) {
            foreach ($db->driver()->ransackPredicates() as $predicate => [$themplate, $value_converter, $conjunction]) {
                $data[] = [$predicate, "name_{$predicate}", 'foo', [], [$db->name()]];
            }
        });

        return $data;
    }

    /**
     * @dataProvider dataPredicates
     */
    public function test_predicate($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertSame($expect, $ransack->predicate());
        }, ...$dbs);
    }

    public function dataTemplates() : array
    {
        $this->setUp();
        $data = [
            ['{col} = {val}'              , 'name'                , 'foo'         , []                                                           ],
            ['{col} IN ({val})'           , 'name'                , ['foo', 'bar'], []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains'       , 'foo'         , []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_any'   , ['foo', 'bar'], []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_any'   , 'foo bar'     , []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_all'   , ['foo', 'bar'], []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_all'   , 'foo bar'     , []                                                           ],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_any_cs', ['foo', 'bar'], []                                     , ['mysql', 'mariadb']],
            ["{col} LIKE {val} ESCAPE '|'", 'name_contains_any_cs', ['foo', 'bar'], ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
        ];

        $this->eachDb(function (Database $db) use (&$data) {
            foreach ($db->driver()->ransackPredicates() as $predicate => [$themplate, $value_converter, $conjunction]) {
                $data[] = [$themplate, "name_{$predicate}", 'foo', [], [$db->name()]];
            }
        });

        return $data;
    }

    /**
     * @dataProvider dataTemplates
     */
    public function test_template($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertSame($expect, $ransack->template());
        }, ...$dbs);
    }

    public function dataValueConverters() : array
    {
        $this->setUp();
        $data = [];
        $this->eachDb(function (Database $db) use (&$data) {
            foreach ($db->driver()->ransackPredicates() as $predicate => [$themplate, $value_converter, $conjunction]) {
                $value_converter = is_string($value_converter) ? Ransack::config("value_converters.{$value_converter}") : $value_converter ;
                $data[]          = [$value_converter, "name_{$predicate}", 'foo', [], [$db->name()]];
            }
        });
        return $data;
    }

    /**
     * @dataProvider dataValueConverters
     */
    public function test_valueConverter($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertEquals($expect, $ransack->valueConverter());
        }, ...$dbs);
    }

    public function dataConjunctions() : array
    {
        $this->setUp();
        $data = [];
        $this->eachDb(function (Database $db) use (&$data) {
            foreach ($db->driver()->ransackPredicates() as $predicate => [$themplate, $value_converter, $conjunction]) {
                $data[] = [$conjunction, "name_{$predicate}", 'foo', [], [$db->name()]];
            }
        });
        return $data;
    }

    /**
     * @dataProvider dataConjunctions
     */
    public function test_conjunction($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertEquals($expect, $ransack->conjunction());
        }, ...$dbs);
    }

    public function dataCompounds() : array
    {
        $data = [
            [null , 'name'                , 'foo'         , []                                                           ],
            [null , 'name'                , ['foo', 'bar'], []                                                           ],
            [null , 'name_contains'       , 'foo'         , []                                                           ],
            [null , 'name_contains_cs'    , 'foo'         , []                                     , ['mysql', 'mariadb']],
            ['any', 'name_contains_any'   , ['foo', 'bar'], []                                                           ],
            ['any', 'name_contains_any'   , 'foo bar'     , []                                                           ],
            ['all', 'name_contains_all'   , ['foo', 'bar'], []                                                           ],
            ['all', 'name_contains_all'   , 'foo bar'     , []                                                           ],
            ['any', 'name_contains_any_cs', ['foo', 'bar'], []                                     , ['mysql', 'mariadb']],
            ['any', 'name_contains_any_ci', ['foo', 'bar'], ['name' => ['last_name', 'first_name']], ['mysql', 'mariadb']],
        ];
        return $data;
    }

    /**
     * @dataProvider dataCompounds
     */
    public function test_compound($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertSame($expect, $ransack->compound());
        }, ...$dbs);
    }

    public function dataOptions() : array
    {
        $this->setUp();
        $data = [];
        $this->eachDb(function (Database $db) use (&$data) {
            foreach ($db->driver()->ransackOptions() as $option => $template) {
                $data[] = [$template, "name_{$option}", 'foo', [], [$db->name()]];
            }
        });
        return $data;
    }

    /**
     * @dataProvider dataOptions
     */
    public function test_option($expect, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertSame($expect, $ransack->option());
        }, ...$dbs);
    }

    public function dataColumns() : array
    {
        $data = [
            [['?name?'                                   ], true , 'name'                 , 'foo'         , []                                                                     ],
            [['?name?'                                   ], true , 'name'                 , ['foo', 'bar'], []                                                                     ],
            [['name'                                     ], false, 'name_bin'             , 'foo'         , []                                     , ['sqlite', 'mysql', 'mariadb']],
            [['BINARY ?name?'                            ], true , 'name_bin'             , 'foo'         , []                                     , ['sqlite', 'mysql', 'mariadb']],
            [['name_bin'                                 ], false, 'name_bin'             , 'foo'         , []                                     , ['pgsql'                     ]],
            [['?name?'                                   ], true , 'name_contains'        , 'foo'         , []                                                                     ],
            [['name'                                     ], false, 'name_contains_bin'    , 'foo'         , []                                     , ['sqlite', 'mysql', 'mariadb']],
            [['BINARY ?name?'                            ], true , 'name_contains_bin'    , 'foo'         , []                                     , ['sqlite', 'mysql', 'mariadb']],
            [['?name? COLLATE utf8mb4_bin'               ], true , 'name_contains_cs'     , 'foo'         , []                                     , ['mysql', 'mariadb'          ]],
            [['?name?'                                   ], true , 'name_contains_any'    , 'foo bar'     , []                                                                     ],
            [['?name?'                                   ], true , 'name_contains_all'    , 'foo bar'     , []                                                                     ],
            [['?name? COLLATE utf8mb4_bin'               ], true , 'name_contains_any_cs' , 'foo'         , []                                     , ['mysql', 'mariadb'          ]],
            [['?name? COLLATE utf8mb4_general_ci'        ], true , 'name_contains_any_ci' , 'foo'         , []                                     , ['mysql', 'mariadb'          ]],
            [['?name? COLLATE nocase'                    ], true , 'name_contains_any_ci' , 'foo'         , []                                     , ['sqlite'                    ]],
            [['?name? COLLATE utf8mb4_unicode_ci'        ], true , 'name_contains_any_fs' , 'foo'         , []                                     , ['mysql', 'mariadb'          ]],

            [['?last_name?', '?first_name?'              ], true , 'name'                 , 'foo'         , ['name' => ['last_name', 'first_name']], [                            ]],
            [['?last_name?', '?first_name?'              ], true , 'name'                 , ['foo', 'bar'], ['name' => ['last_name', 'first_name']], [                            ]],
            [['BINARY ?last_name?', 'BINARY ?first_name?'], true , 'name_bin'             , 'foo'         , ['name' => ['last_name', 'first_name']], ['sqlite', 'mysql', 'mariadb']],
            [['last_name', 'first_name'                  ], false, 'name_bin'             , 'foo'         , ['name' => ['last_name', 'first_name']], ['sqlite', 'mysql', 'mariadb']],
            [
                ['?last_name? COLLATE nocase', '?first_name? COLLATE nocase']
                , true , 'name_contains_any_ci', 'foo',
                ['name' => ['last_name', 'first_name']],
                ['sqlite']
            ],
            [
                ['?name? COLLATE nocase', '?name_ruby? COLLATE nocase']
                , true , 'name_contains_any_ci', 'foo',
                ['name' => ['name', 'name_ruby']],
                ['sqlite']
            ],
            [
                ['CONCAT(last_name,first_name) COLLATE nocase']
                , true , 'name_contains_any_ci', 'foo',
                ['name' => "CONCAT(last_name,first_name)"],
                ['sqlite']
            ],
            [
                [
                    '?last_name? COLLATE nocase',
                    '?first_name? COLLATE nocase',
                    '?last_name_ruby? COLLATE nocase',
                    '?first_name_ruby? COLLATE nocase',
                ]
                , true , 'full_name_contains_any_ci', 'foo',
                ['full_name' => ['@name', '@name_ruby'], 'name' => ['last_name', 'first_name'], 'name_ruby' => ['last_name_ruby', 'first_name_ruby']],
                ['sqlite']
            ],
            [
                [
                    'last_name',
                    'first_name',
                    'last_name_ruby',
                    'first_name_ruby',
                ]
                , false, 'full_name_contains_any_ci', 'foo',
                ['full_name' => ['@name', '@name_ruby'], 'name' => ['last_name', 'first_name'], 'name_ruby' => ['last_name_ruby', 'first_name_ruby']],
                ['sqlite']
            ],
            [
                [
                    'CONCAT(last_name,first_name) COLLATE nocase',
                    'CONCAT(last_name_ruby,first_name_ruby) COLLATE nocase',
                ]
                , true , 'full_name_contains_any_ci', 'foo',
                ['full_name' => ['@name', '@name_ruby'], 'name' => "CONCAT(last_name,first_name)", 'name_ruby' => "CONCAT(last_name_ruby,first_name_ruby)"],
                ['sqlite']
            ],
        ];
        return $data;
    }

    /**
     * @dataProvider dataColumns
     */
    public function test_columns($expect, $apply_option, $ransack_predicate, $value, $alias, $dbs = [])
    {
        $this->eachDb(function (Database $db) use ($expect, $apply_option, $ransack_predicate, $value, $alias) {
            $ransack = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $this->assertWildcardEach($expect, $ransack->columns($apply_option));
        }, ...$dbs);
    }

    public function dataConverts() : array
    {
        return [
            [
                '?name? = :name',
                ['name' => 'foo'],
                'name', 'foo', [],
                []
            ],
            [
                '?name? IN (:name)',
                ['name' => ['foo', 'bar']],
                'name', ['foo', 'bar'], [],
                []
            ],
            // --------------
            [
                '?name? COLLATE nocase = :name_ci',
                ['name_ci' => 'foo'],
                'name_ci', 'foo', [],
                ['sqlite']
            ],
            [
                '?name? COLLATE nocase IN (:name_ci)',
                ['name_ci' => ['foo', 'bar']],
                'name_ci', ['foo', 'bar'], [],
                ['sqlite']
            ],
            // --------------
            [
                '?name? = :name_eq',
                ['name_eq' => 'foo'],
                'name_eq', 'foo', [],
                []
            ],
            [
                '?name? COLLATE nocase = :name_eq_ci',
                ['name_eq_ci' => 'foo'],
                'name_eq_ci', 'foo', [],
                ['sqlite']
            ],
            // --------------
            [
                '?name? <> :name_not_eq',
                ['name_not_eq' => 'foo'],
                'name_not_eq', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? IN (:name_in)',
                ['name_in' => ['foo', 'bar']],
                'name_in', ['foo', 'bar'], [],
                []
            ],
            // --------------
            [
                '?name? NOT IN (:name_not_in)',
                ['name_not_in' => ['foo', 'bar']],
                'name_not_in', ['foo', 'bar'], [],
                []
            ],
            // --------------
            [
                '?name? < :name_lt',
                ['name_lt' => 'foo'],
                'name_lt', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? <= :name_lteq',
                ['name_lteq' => 'foo'],
                'name_lteq', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? >= :name_gteq',
                ['name_gteq' => 'foo'],
                'name_gteq', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? > :name_gt',
                ['name_gt' => 'foo'],
                'name_gt', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? >= :name_from',
                ['name_from' => 'foo'],
                'name_from', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? <= :name_to',
                ['name_to' => 'foo'],
                'name_to', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? > :name_after',
                ['name_after' => 'foo'],
                'name_after', 'foo', [],
                []
            ],
            // --------------
            [
                '?name? < :name_before',
                ['name_before' => 'foo'],
                'name_before', 'foo', [],
                []
            ],
            // --------------
            [
                "?name? LIKE :name_contains ESCAPE '|'",
                ['name_contains' => '%100|%%'],
                'name_contains', '100%', [],
                []
            ],
            [
                "?name? LIKE :name_contains_any ESCAPE '|'",
                ['name_contains_any' => '%100|%%'],
                'name_contains_any', '100%', [],
                []
            ],
            [
                "(?name? LIKE :name_contains_any_0 ESCAPE '|' OR ?name? LIKE :name_contains_any_1 ESCAPE '|')",
                [
                    'name_contains_any_0' => '%100|%%',
                    'name_contains_any_1' => '%foo%'
                ],
                'name_contains_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?name? LIKE :name_contains_all_0 ESCAPE '|' AND ?name? LIKE :name_contains_all_1 ESCAPE '|')",
                [
                    'name_contains_all_0' => '%100|%%',
                    'name_contains_all_1' => '%foo%'
                ],
                'name_contains_all', ['100%', 'foo'], [],
                []
            ],
            [
                "(?name? COLLATE nocase LIKE :name_contains_any_ci_0 ESCAPE '|' OR ?name? COLLATE nocase LIKE :name_contains_any_ci_1 ESCAPE '|')",
                [
                    'name_contains_any_ci_0' => '%100|%%',
                    'name_contains_any_ci_1' => '%foo%'
                ],
                'name_contains_any_ci', ['100%', 'foo'], [],
                ['sqlite']
            ],
            [
                "(?last_name? LIKE :name_contains_0 ESCAPE '|' OR ?first_name? LIKE :name_contains_1 ESCAPE '|')",
                [
                    'name_contains_0' => '%100|%%',
                    'name_contains_1' => '%100|%%',
                ],
                'name_contains', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            [
                "((?last_name? LIKE :name_contains_any_0_0 ESCAPE '|' OR ?first_name? LIKE :name_contains_any_0_1 ESCAPE '|') OR (?last_name? LIKE :name_contains_any_1_0 ESCAPE '|' OR ?first_name? LIKE :name_contains_any_1_1 ESCAPE '|'))",
                [
                    'name_contains_any_0_0' => '%100|%%',
                    'name_contains_any_0_1' => '%100|%%',
                    'name_contains_any_1_0' => '%foo%',
                    'name_contains_any_1_1' => '%foo%',
                ],
                'name_contains_any', ['100%', 'foo'], ['name' => ['last_name', 'first_name']],
                []
            ],
            [
                "((?last_name? LIKE :name_contains_any_0_0 ESCAPE '|' OR ?first_name? LIKE :name_contains_any_0_1 ESCAPE '|') OR (?last_name? LIKE :name_contains_any_1_0 ESCAPE '|' OR ?first_name? LIKE :name_contains_any_1_1 ESCAPE '|'))",
                [
                    'name_contains_any_0_0' => '%100|%%',
                    'name_contains_any_0_1' => '%100|%%',
                    'name_contains_any_1_0' => '%foo%',
                    'name_contains_any_1_1' => '%foo%',
                ],
                'name_contains_any', '100% foo', ['name' => ['last_name', 'first_name']],
                []
            ],
            [
                "((?last_name? COLLATE nocase LIKE :name_contains_all_ci_0_0 ESCAPE '|' OR ?first_name? COLLATE nocase LIKE :name_contains_all_ci_0_1 ESCAPE '|') AND (?last_name? COLLATE nocase LIKE :name_contains_all_ci_1_0 ESCAPE '|' OR ?first_name? COLLATE nocase LIKE :name_contains_all_ci_1_1 ESCAPE '|'))",
                [
                    'name_contains_all_ci_0_0' => '%100|%%',
                    'name_contains_all_ci_0_1' => '%100|%%',
                    'name_contains_all_ci_1_0' => '%foo%',
                    'name_contains_all_ci_1_1' => '%foo%',
                ],
                'name_contains_all_ci', ['100%', 'foo'], ['name' => ['last_name', 'first_name']],
                ['sqlite']
            ],
            // --------------
            [
                "?name? NOT LIKE :name_not_contains ESCAPE '|'",
                ['name_not_contains' => '%100|%%'],
                'name_not_contains', '100%', [],
                []
            ],
            [
                "(?name? NOT LIKE :name_not_contains_any_0 ESCAPE '|' OR ?name? NOT LIKE :name_not_contains_any_1 ESCAPE '|')",
                [
                    'name_not_contains_any_0' => '%100|%%',
                    'name_not_contains_any_1' => '%foo%'
                ],
                'name_not_contains_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?last_name? NOT LIKE :name_not_contains_0 ESCAPE '|' AND ?first_name? NOT LIKE :name_not_contains_1 ESCAPE '|')",
                [
                    'name_not_contains_0' => '%100|%%',
                    'name_not_contains_1' => '%100|%%',
                ],
                'name_not_contains', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? LIKE :name_starts ESCAPE '|'",
                ['name_starts' => '100|%%'],
                'name_starts', '100%', [],
                []
            ],
            [
                "(?name? LIKE :name_starts_any_0 ESCAPE '|' OR ?name? LIKE :name_starts_any_1 ESCAPE '|')",
                [
                    'name_starts_any_0' => '100|%%',
                    'name_starts_any_1' => 'foo%'
                ],
                'name_starts_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?last_name? LIKE :name_starts_0 ESCAPE '|' OR ?first_name? LIKE :name_starts_1 ESCAPE '|')",
                [
                    'name_starts_0' => '100|%%',
                    'name_starts_1' => '100|%%',
                ],
                'name_starts', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? NOT LIKE :name_not_starts ESCAPE '|'",
                ['name_not_starts' => '100|%%'],
                'name_not_starts', '100%', [],
                []
            ],
            [
                "(?name? NOT LIKE :name_not_starts_any_0 ESCAPE '|' OR ?name? NOT LIKE :name_not_starts_any_1 ESCAPE '|')",
                [
                    'name_not_starts_any_0' => '100|%%',
                    'name_not_starts_any_1' => 'foo%'
                ],
                'name_not_starts_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?last_name? NOT LIKE :name_not_starts_0 ESCAPE '|' AND ?first_name? NOT LIKE :name_not_starts_1 ESCAPE '|')",
                [
                    'name_not_starts_0' => '100|%%',
                    'name_not_starts_1' => '100|%%',
                ],
                'name_not_starts', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? LIKE :name_ends ESCAPE '|'",
                ['name_ends' => '%100|%'],
                'name_ends', '100%', [],
                []
            ],
            [
                "(?name? LIKE :name_ends_any_0 ESCAPE '|' OR ?name? LIKE :name_ends_any_1 ESCAPE '|')",
                [
                    'name_ends_any_0' => '%100|%',
                    'name_ends_any_1' => '%foo'
                ],
                'name_ends_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?last_name? LIKE :name_ends_0 ESCAPE '|' OR ?first_name? LIKE :name_ends_1 ESCAPE '|')",
                [
                    'name_ends_0' => '%100|%',
                    'name_ends_1' => '%100|%',
                ],
                'name_ends', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? NOT LIKE :name_not_ends ESCAPE '|'",
                ['name_not_ends' => '%100|%'],
                'name_not_ends', '100%', [],
                []
            ],
            [
                "(?name? NOT LIKE :name_not_ends_any_0 ESCAPE '|' OR ?name? NOT LIKE :name_not_ends_any_1 ESCAPE '|')",
                [
                    'name_not_ends_any_0' => '%100|%',
                    'name_not_ends_any_1' => '%foo'
                ],
                'name_not_ends_any', ['100%', 'foo'], [],
                []
            ],
            [
                "(?last_name? NOT LIKE :name_not_ends_0 ESCAPE '|' AND ?first_name? NOT LIKE :name_not_ends_1 ESCAPE '|')",
                [
                    'name_not_ends_0' => '%100|%',
                    'name_not_ends_1' => '%100|%',
                ],
                'name_not_ends', '100%', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? IS NULL",
                [],
                'name_null', '1', [],
                []
            ],
            [
                "(?last_name? IS NULL AND ?first_name? IS NULL)",
                [],
                'name_null', '1', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? IS NOT NULL",
                [],
                'name_not_null', '1', [],
                []
            ],
            [
                "(?last_name? IS NOT NULL OR ?first_name? IS NOT NULL)",
                [],
                'name_not_null', '1', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "(?name? IS NULL OR ?name? = '')",
                [],
                'name_blank', '1', [],
                []
            ],
            [
                "((?last_name? IS NULL OR ?last_name? = '') AND (?first_name? IS NULL OR ?first_name? = ''))",
                [],
                'name_blank', '1', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "(?name? IS NOT NULL AND ?name? <> '')",
                [],
                'name_not_blank', '1', [],
                []
            ],
            [
                "((?last_name? IS NOT NULL AND ?last_name? <> '') OR (?first_name? IS NOT NULL AND ?first_name? <> ''))",
                [],
                'name_not_blank', '1', ['name' => ['last_name', 'first_name']],
                []
            ],
            // --------------
            [
                "?name? REGEXP :name_matches",
                ['name_matches' => '^fo+'],
                'name_matches', '^fo+', [],
                ['sqlite', 'mysql', 'mariadb']
            ],
            [
                "?name? ~ :name_matches",
                ['name_matches' => '^fo+'],
                'name_matches', '^fo+', [],
                ['pgsql']
            ],
            // --------------
            [
                "?name? NOT REGEXP :name_not_matches",
                ['name_not_matches' => '^fo+'],
                'name_not_matches', '^fo+', [],
                ['sqlite', 'mysql', 'mariadb']
            ],
            [
                "?name? !~ :name_not_matches",
                ['name_not_matches' => '^fo+'],
                'name_not_matches', '^fo+', [],
                ['pgsql']
            ],
            // --------------
            [
                "?name? MATCH :name_search",
                ['name_search' => 'foo'],
                'name_search', 'foo', [],
                ['sqlite']
            ],
            [
                "MATCH(?name?) AGAINST(:name_search)",
                ['name_search' => 'foo'],
                'name_search', 'foo', [],
                ['mysql', 'mariadb']
            ],
            [
                "to_tsvector(?name?) @@ to_tsquery(:name_search)",
                ['name_search' => 'foo'],
                'name_search', 'foo', [],
                ['pgsql']
            ],
            [
                "CONTAINS(?name?, :name_search)",
                ['name_search' => 'foo'],
                'name_search', 'foo', [],
                ['sqlsrv']
            ],
            // --------------
            [
                "FREETEXT(?name?, :name_meaning)",
                ['name_meaning' => 'foo'],
                'name_meaning', 'foo', [],
                ['sqlsrv']
            ],
        ];
    }

    /**
     * @dataProvider dataConverts
     */
    public function test_convert($expect_sql, $expect_params, $ransack_predicate, $value, $alias, $dbs, $rantaime_template = null, $rantime_vallue_converter = null)
    {
        $this->eachDb(function (Database $db) use ($expect_sql, $expect_params, $ransack_predicate, $value, $alias, $rantaime_template, $rantime_vallue_converter) {
            $ransack   = Ransack::analyze($db->driver(), $ransack_predicate, $value, $alias);
            $condition = $ransack->convert($rantaime_template, $rantime_vallue_converter);
            $this->assertWildcardString($expect_sql, $condition->sql());
            $this->assertEquals($expect_params, $condition->params());
        }, ...$dbs);
    }
}
