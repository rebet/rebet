<?php
namespace Rebet\Tests\Database\Driver;

use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Rebet\Common\Decimal;
use Rebet\Common\Reflector;
use Rebet\Config\Config;
use Rebet\Database\Dao;
use Rebet\Database\Driver\PdoDriver;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\Tests\RebetDatabaseTestCase;

class BuiltinConverterTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        Config::application([
            Dao::class => [
                'dbs' => [
                    'sqlite' => [
                        'driver'   => self::$pdo,
                    ],

                    // CREATE DATABASE rebet_unittest DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_bin;
                    'mysql' => [
                        'driver'   => PdoDriver::class,
                        'dsn'      => 'mysql:host=localhost;dbname=rebet_unittest;charset=utf8mb4',
                        'user'     => 'root',
                        'password' => '',
                        'options'  => [
                            \PDO::ATTR_AUTOCOMMIT => false,
                        ],
                        // 'log_handler' => function ($name, $sql, $params =[]) { echo $sql; }
                    ],
                ]
            ]
        ]);
    }

    protected function getDataSet()
    {
        return new ArrayDataSet([
        ]);
    }

    public function test_toPhpType_sqlite()
    {
        try {
            $dml = <<<EOS
                CREATE TABLE IF NOT EXISTS native_types (
                    type_integer INTEGER,
                    type_text TEXT,
                    type_none NONE,
                    type_real REAL,
                    type_numeric NUMERIC,
                    type_tinyint TINYINT(3),   -- INTEGER
                    type_varchar VARCHAR(10),  -- TEXT
                    type_blob BLOB,            -- NONE
                    type_float FLOAT,          -- REAL
                    type_decimal DECIMAL(5,2), -- NUMERIC
                    type_boolean BOOLEAN,      -- NUMERIC
                    type_date DATE,            -- NUMERIC
                    type_datetime DATETIME,    -- NUMERIC
                    type_null                  -- NULL
                );
EOS;
            $db = Dao::db('sqlite');
            $db->execute($dml);
            $db->execute("INSERT INTO native_types VALUES (:values)", [ 'values' => [
                1,
                'a',
                'noen',
                12.34,
                '1.2',
                1,
                'varchar',
                'blob',
                123.45,
                '1e-2',
                true,
                '2010-01-02',
                '2010-01-02 10:20:30',
                null
            ]]);
            $stmt = $db->query('SELECT * FROM native_types');
            $meta = $stmt->meta();
            $rs   = $stmt->first();
            foreach ([
                'type_integer'  => ['integer', 'int'   ],
                'type_text'     => ['string' , 'string'],
                'type_none'     => ['string' , 'string'],
                'type_real'     => ['double' , 'float' ],
                'type_numeric'  => ['double' , 'float' ],
                'type_tinyint'  => ['integer', 'int'   ],
                'type_varchar'  => ['string' , 'string'],
                'type_blob'     => ['string' , 'string'],
                'type_float'    => ['double' , 'float' ],
                'type_decimal'  => ['double' , 'float' ],
                'type_boolean'  => ['integer', 'int'   ],
                'type_date'     => ['string' , 'string'],
                'type_datetime' => ['string' , 'string'],
                'type_null'     => ['null'   , null    ]
            ] as $col => [$native_type, $php_type]) {
                $this->assertSame($native_type, $meta[$col]['native_type'] ?? null, "Failed {$col} => {$native_type}");
                $this->assertSame($php_type, Reflector::getType($rs->$col));
            }
        } catch (\Exception $e) {
            $this->markTestSkipped("There is no SQLite database for test environment : {$e}");
        }
    }

    public function test_toPhpType_mysql()
    {
        try {
            $db = Dao::db('mysql');
        } catch (\Exception $e) {
            $this->markTestSkipped("There is no MySQL database for test environment : {$e}");
            return;
        }
        $db->execute("DROP TABLE IF EXISTS native_types;");
        $dml = <<<EOS
            CREATE TABLE IF NOT EXISTS native_types (
                type_tinyint            TINYINT,
                type_smallint           SMALLINT,
                type_mediumint          MEDIUMINT,
                type_int                INT,
                type_integer            INTEGER,
                type_bigint             BIGINT,

                type_bool               BOOL,
                type_boolean            BOOLEAN,
                type_tinyint_one        TINYINT(1),

                type_bit                BIT(3),

                type_decimal            DECIMAL(5, 2),
                type_dec                DEC(5, 2),
                type_numeric            NUMERIC,

                type_float              FLOAT,
                type_double             DOUBLE,

                type_date               DATE,
                type_datetime           DATETIME,
                type_timestamp          TIMESTAMP,
                type_time               TIME,
                type_year               YEAR,

                type_char               CHAR(5),
                type_varchar            VARCHAR(5),
                type_binary             BINARY(5),
                type_varbinary          VARBINARY(5),
                type_tinyblob           TINYBLOB,
                type_blob               BLOB,
                type_mediumblob         MEDIUMBLOB,
                type_longblob           LONGBLOB,
                type_tinytext           TINYTEXT,
                type_text               TEXT,
                type_mediumtext         MEDIUMTEXT,

                type_enum               ENUM('a','b','c'),
                type_set                SET('a','b','c'),

                type_geometry           GEOMETRY,
                type_point              POINT,
                type_linestring         LINESTRING,
                type_polygon            POLYGON,
                type_multipoint         MULTIPOINT,
                type_multilinestring    MULTILINESTRING,
                type_multipolygon       MULTIPOLYGON,
                type_geometrycollection GEOMETRYCOLLECTION,
                type_geometry_null      GEOMETRY,

                type_text_null          TEXT
            );
EOS;
        $db->execute($dml);
        $db->begin();
        $db->execute("INSERT INTO native_types VALUES (:values)", ['values' => [
            1,                                                                                         // type_tinyint
            1,                                                                                         // type_smallint
            1,                                                                                         // type_mediumint
            1,                                                                                         // type_int
            1,                                                                                         // type_integer
            1,                                                                                         // type_bigint
            true,                                                                                      // type_bool
            true,                                                                                      // type_boolean
            true,                                                                                      // type_tinyint_one
            0b111,                                                                                     // type_bit
            123.45,                                                                                    // type_decimal
            123.45,                                                                                    // type_dec
            123.45,                                                                                    // type_numeric
            123.45,                                                                                    // type_float
            123.45,                                                                                    // type_double
            '2010-01-02',                                                                              // type_date
            '2010-01-02 10:20:30',                                                                     // type_datetime
            '2010-01-02 10:20:30',                                                                     // type_timestamp
            '10:20:30',                                                                                // type_time
            2010,                                                                                      // type_year
            'abc',                                                                                     // type_char
            'abc',                                                                                     // type_varchar
            'abc',                                                                                     // type_binary
            'abc',                                                                                     // type_varbinary
            'abc',                                                                                     // type_tinyblob
            'abc',                                                                                     // type_blob
            'abc',                                                                                     // type_mediumblob
            'abc',                                                                                     // type_longblob
            'abc',                                                                                     // type_tinytext
            'abc',                                                                                     // type_text
            'abc',                                                                                     // type_mediumtext
            'b',                                                                                       // type_enum
            'a,b',                                                                                     // type_set
            ['GeomFromText(?)', 'POINT(1 1)'],                                                         // type_geometry
            ['GeomFromText(?)', 'POINT(1 1)'],                                                         // type_point
            ['GeomFromText(?)', 'LINESTRING(0 0,1 1,2 2)'],                                            // type_linestring
            ['GeomFromText(?)', 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))'],           // type_polygon
            ['GeomFromText(?)', 'MULTIPOINT(1 1,2 2,3 3)'],                                            // type_multipoint
            ['GeomFromText(?)', 'MULTILINESTRING((0 0,1 1,2 2), (0 2,1 1,2 0))'],                      // type_multilinestring
            ['GeomFromText(?)', 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0)),((5 5,7 5,7 7,5 7, 5 5)))'], // type_multipolygon
            ['GeomFromText(?)', 'GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(0 0,1 1,2 2,3 3,4 4))'],     // type_geometrycollection
            ['GeomFromText(?)', null],                                                                 // type_geometry_null
            null,                                                                                      // type_text_null
        ]]);
        $db->commit();
        $stmt = $db->query('SELECT * FROM native_types');
        $meta = $stmt->meta();
        $rs   = $stmt->first();
        foreach ([
            'type_tinyint'            => ['TINY',       'int'],
            'type_smallint'           => ['SHORT',      'int'],
            'type_mediumint'          => ['INT24',      'int'],
            'type_int'                => ['LONG',       'int'],
            'type_integer'            => ['LONG',       'int'],
            'type_bigint'             => ['LONGLONG',   'int'],
            'type_bool'               => ['TINY',       'bool'],
            'type_boolean'            => ['TINY',       'bool'],
            'type_tinyint_one'        => ['TINY',       'bool'],
            'type_bit'                => ['BIT',        'int'],
            'type_decimal'            => ['NEWDECIMAL', Decimal::class],
            'type_dec'                => ['NEWDECIMAL', Decimal::class],
            'type_numeric'            => ['NEWDECIMAL', Decimal::class],
            'type_float'              => ['FLOAT',      'float'],
            'type_double'             => ['DOUBLE',     'float'],
            'type_date'               => ['DATE',       Date::class],
            'type_datetime'           => ['DATETIME',   DateTime::class],
            'type_timestamp'          => ['TIMESTAMP',  DateTime::class],
            'type_time'               => ['TIME',       'string'],
            'type_year'               => ['YEAR',       'int'],
            'type_char'               => ['STRING',     'string'],
            'type_varchar'            => ['VAR_STRING', 'string'],
            'type_binary'             => ['STRING',     'string'],
            'type_varbinary'          => ['VAR_STRING', 'string'],
            'type_tinyblob'           => ['BLOB',       'string'],
            'type_blob'               => ['BLOB',       'string'],
            'type_mediumblob'         => ['BLOB',       'string'],
            'type_longblob'           => ['BLOB',       'string'],
            'type_tinytext'           => ['BLOB',       'string'],
            'type_text'               => ['BLOB',       'string'],
            'type_mediumtext'         => ['BLOB',       'string'],
            'type_enum'               => ['STRING',     'string'],
            'type_set'                => ['STRING',     'string'],
            'type_geometry'           => ['GEOMETRY',   'string'],
            'type_point'              => ['GEOMETRY',   'string'],
            'type_linestring'         => ['GEOMETRY',   'string'],
            'type_polygon'            => ['GEOMETRY',   'string'],
            'type_multipoint'         => ['GEOMETRY',   'string'],
            'type_multilinestring'    => ['GEOMETRY',   'string'],
            'type_multipolygon'       => ['GEOMETRY',   'string'],
            'type_geometrycollection' => ['GEOMETRY',   'string'],
            'type_geometry_null'      => ['GEOMETRY',    null],
            'type_text_null'          => ['BLOB',        null],
        ] as $col => [$native_type, $php_type]) {
            $meta_native_type = $meta[$col]['native_type'] ?? null;
            $this->assertSame($native_type, $meta_native_type, "Failed {$col} => {$native_type} actual {$meta_native_type}");
            $this->assertSame($php_type, Reflector::getType($rs->$col), "Failed {$col} => {$native_type} actual {$meta_native_type} {$rs->$col}");
        }
    }
}
