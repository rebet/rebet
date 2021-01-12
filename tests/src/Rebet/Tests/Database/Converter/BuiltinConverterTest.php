<?php
namespace Rebet\Tests\Database\Converter;

use Rebet\Application\App;
use Rebet\Database\Database;
use Rebet\Database\Expression;
use Rebet\Database\PdoParameter;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\DateTime\DateTimeZone;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Strings;
use SimpleXMLElement;

class BuiltinConverterTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function test_toPhpType_sqlite()
    {
        if (!($db = $this->connect('sqlite'))) {
            return;
        }
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
    }

    public function test_toPhpType_mysql()
    {
        if (!($db = $this->connect('mysql'))) {
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
            1,                                                                                                            // type_tinyint
            1,                                                                                                            // type_smallint
            1,                                                                                                            // type_mediumint
            1,                                                                                                            // type_int
            1,                                                                                                            // type_integer
            1,                                                                                                            // type_bigint
            true,                                                                                                         // type_bool
            true,                                                                                                         // type_boolean
            true,                                                                                                         // type_tinyint_one
            0b111,                                                                                                        // type_bit
            123.45,                                                                                                       // type_decimal
            123.45,                                                                                                       // type_dec
            123.45,                                                                                                       // type_numeric
            123.45,                                                                                                       // type_float
            123.45,                                                                                                       // type_double
            '2010-01-02',                                                                                                 // type_date
            '2010-01-02 10:20:30',                                                                                        // type_datetime
            '2010-01-02 10:20:30',                                                                                        // type_timestamp
            '10:20:30',                                                                                                   // type_time
            2010,                                                                                                         // type_year
            'abc',                                                                                                        // type_char
            'abc',                                                                                                        // type_varchar
            'abc',                                                                                                        // type_binary
            'abc',                                                                                                        // type_varbinary
            'abc',                                                                                                        // type_tinyblob
            'abc',                                                                                                        // type_blob
            'abc',                                                                                                        // type_mediumblob
            'abc',                                                                                                        // type_longblob
            'abc',                                                                                                        // type_tinytext
            'abc',                                                                                                        // type_text
            'abc',                                                                                                        // type_mediumtext
            'b',                                                                                                          // type_enum
            'a,b',                                                                                                        // type_set
            Expression::of('ST_GeomFromText({0})', 'POINT(1 1)'),                                                         // type_geometry
            Expression::of('ST_GeomFromText({0})', 'POINT(1 1)'),                                                         // type_point
            Expression::of('ST_GeomFromText({0})', 'LINESTRING(0 0,1 1,2 2)'),                                            // type_linestring
            Expression::of('ST_GeomFromText({0})', 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))'),           // type_polygon
            Expression::of('ST_GeomFromText({0})', 'MULTIPOINT(1 1,2 2,3 3)'),                                            // type_multipoint
            Expression::of('ST_GeomFromText({0})', 'MULTILINESTRING((0 0,1 1,2 2), (0 2,1 1,2 0))'),                      // type_multilinestring
            Expression::of('ST_GeomFromText({0})', 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0)),((5 5,7 5,7 7,5 7, 5 5)))'), // type_multipolygon
            Expression::of('ST_GeomFromText({0})', 'GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(0 0,1 1,2 2,3 3,4 4))'),     // type_geometrycollection
            Expression::of('ST_GeomFromText({0})', null),                                                                 // type_geometry_null
            null,                                                                                                         // type_text_null
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

    public function test_toPhpType_mariadb()
    {
        if (!($db = $this->connect('mariadb'))) {
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
            1,                                                                                                            // type_tinyint
            1,                                                                                                            // type_smallint
            1,                                                                                                            // type_mediumint
            1,                                                                                                            // type_int
            1,                                                                                                            // type_integer
            1,                                                                                                            // type_bigint
            true,                                                                                                         // type_bool
            true,                                                                                                         // type_boolean
            true,                                                                                                         // type_tinyint_one
            0b111,                                                                                                        // type_bit
            123.45,                                                                                                       // type_decimal
            123.45,                                                                                                       // type_dec
            123.45,                                                                                                       // type_numeric
            123.45,                                                                                                       // type_float
            123.45,                                                                                                       // type_double
            '2010-01-02',                                                                                                 // type_date
            '2010-01-02 10:20:30',                                                                                        // type_datetime
            '2010-01-02 10:20:30',                                                                                        // type_timestamp
            '10:20:30',                                                                                                   // type_time
            2010,                                                                                                         // type_year
            'abc',                                                                                                        // type_char
            'abc',                                                                                                        // type_varchar
            'abc',                                                                                                        // type_binary
            'abc',                                                                                                        // type_varbinary
            'abc',                                                                                                        // type_tinyblob
            'abc',                                                                                                        // type_blob
            'abc',                                                                                                        // type_mediumblob
            'abc',                                                                                                        // type_longblob
            'abc',                                                                                                        // type_tinytext
            'abc',                                                                                                        // type_text
            'abc',                                                                                                        // type_mediumtext
            'b',                                                                                                          // type_enum
            'a,b',                                                                                                        // type_set
            Expression::of('ST_GeomFromText({0})', 'POINT(1 1)'),                                                         // type_geometry
            Expression::of('ST_GeomFromText({0})', 'POINT(1 1)'),                                                         // type_point
            Expression::of('ST_GeomFromText({0})', 'LINESTRING(0 0,1 1,2 2)'),                                            // type_linestring
            Expression::of('ST_GeomFromText({0})', 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))'),           // type_polygon
            Expression::of('ST_GeomFromText({0})', 'MULTIPOINT(1 1,2 2,3 3)'),                                            // type_multipoint
            Expression::of('ST_GeomFromText({0})', 'MULTILINESTRING((0 0,1 1,2 2), (0 2,1 1,2 0))'),                      // type_multilinestring
            Expression::of('ST_GeomFromText({0})', 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0)),((5 5,7 5,7 7,5 7, 5 5)))'), // type_multipolygon
            Expression::of('ST_GeomFromText({0})', 'GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(0 0,1 1,2 2,3 3,4 4))'),     // type_geometrycollection
            Expression::of('ST_GeomFromText({0})', null),                                                                 // type_geometry_null
            null,                                                                                                         // type_text_null
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

    public function test_toPhpType_pgsql()
    {
        if (!($db = $this->connect('pgsql'))) {
            return;
        }
        $db->execute("DROP TABLE IF EXISTS native_types;");
        $dml = <<<EOS
            CREATE TABLE IF NOT EXISTS native_types (
                type_smallint          SMALLINT,
                type_integer           INTEGER,
                type_bigint            BIGINT,

                type_real              REAL,
                type_double_precision  DOUBLE PRECISION,

                type_numeric           NUMERIC(5, 2),

                type_money             MONEY,

                type_smallserial       SMALLSERIAL,
                type_serial            SERIAL,
                type_bigserial         BIGSERIAL,

                type_bit               BIT(3),
                type_bit_over_64       BIT(65),
                type_bit_varying       BIT VARYING (3),

                type_boolean           BOOLEAN,

                type_uuid              UUID,

                type_character         CHARACTER(20),
                type_character_varying CHARACTER VARYING (20),
                type_text              TEXT,

                type_bytea             BYTEA,

                type_cidr              CIDR,
                type_inet              INET,
                type_macaddr           MACADDR,

                type_date              DATE,
                type_timestamp         TIMESTAMP,
                type_timestamp_with_tz TIMESTAMP WITH TIME ZONE,
                type_time              TIME,
                type_time_with_tz      TIME WITH TIME ZONE,

                type_interval          INTERVAL,
                type_interval_hour     INTERVAL HOUR,

                type_json              JSON,
                type_jsonb             JSONB,
                type_xml               XML,

                type_box               BOX,
                type_circle            CIRCLE,
                type_line              LINE,
                type_lseg              LSEG,
                type_path_close        PATH,
                type_path_open         PATH,
                type_point             POINT,
                type_polygon           POLYGON,
                type_pg_lsn            PG_LSN,

                type_tsquery           TSQUERY,
                type_tsvector          TSVECTOR,
                type_txid_snapshot     TXID_SNAPSHOT,

                type_text_null         TEXT
            );
EOS;
        $db->execute($dml);
        $db->begin();
        $db->execute("INSERT INTO native_types VALUES (:values)", ['values' => [
            1,                                                                                                  // type_smallint
            1,                                                                                                  // type_integer
            1,                                                                                                  // type_bigint
            123.45,                                                                                             // type_real
            123.45,                                                                                             // type_double_precision
            123.45,                                                                                             // type_numeric
            123.45,                                                                                             // type_money
            Expression::of('nextval({0})', 'native_types_type_smallserial_seq'),                                // type_smallserial
            Expression::of('nextval({0})', 'native_types_type_serial_seq'),                                     // type_serial
            Expression::of('nextval({0})', 'native_types_type_bigserial_seq'),                                  // type_bigserial
            '111',                                                                                              // type_bit
            '11111111111111111111111111111111111111111111111111111111111111111',                                // type_bit_over_64
            '111',                                                                                              // type_bit_varying
            true,                                                                                               // type_boolean
            '{a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11}',                                                           // type_uuid
            'abc',                                                                                              // type_character
            'abc',                                                                                              // type_character_varying
            'abc',                                                                                              // type_text
            'abc',                                                                                              // type_bytea
            '192.168.1.0/24',                                                                                   // type_cidr
            '192.168.1.0/24',                                                                                   // type_inet
            '08:00:2b:01:02:03',                                                                                // type_macaddr
            '2010-01-02',                                                                                       // type_date
            '2010-01-02 10:20:30',                                                                              // type_timestamp
            '2010-01-02 10:20:30+09',                                                                           // type_timestamp_with_tz
            '10:20:30',                                                                                         // type_time
            '10:20:30+09',                                                                                      // type_time_with_tz
            '1 day',                                                                                            // type_interval
            '1 hour',                                                                                           // type_interval_hour
            '{"name":"John", "tags":["PHP","Rebet"]}',                                                          // type_json
            '{"name":"John", "tags":["PHP","Rebet"]}',                                                          // type_jsonb
            Expression::of('XMLPARSE(CONTENT {0})', '<book><title>Manual</title><author>John</author></book>'), // type_xml
            '((0,0),(1,1))',                                                                                    // type_box
            '<(0,0),1>',                                                                                        // type_circle
            '((0,0),(1,1))',                                                                                    // type_line
            '((0,0),(1,1))',                                                                                    // type_lseg
            '((0,0),(0,1),(1,1),(1,0),(0,0))',                                                                  // type_path_close
            '[(0,0),(0,1),(1,1),(1,0)]',                                                                        // type_path_open
            '(0,1)',                                                                                            // type_point
            '((0,0),(0,1),(1,1),(1,0),(0,0))',                                                                  // type_polygon
            '16/B374D848',                                                                                      // type_pg_lsn
            'fat & rat',                                                                                        // type_tsquery
            'a fat cat sat on a mat and ate a fat rat',                                                         // type_tsvector
            Expression::of('txid_current_snapshot()'),                                                          // type_txid_snapshot
            null                                                                                                // type_text_null
        ]]);
        $db->commit();
        $stmt = $db->query('SELECT * FROM native_types');
        $meta = $stmt->meta();
        $rs   = $stmt->first();
        foreach ([
            'type_smallint'          => ['int2'         , 'int'],
            'type_integer'           => ['int4'         , 'int'],
            'type_bigint'            => ['int8'         , 'int'],
            'type_real'              => ['float4'       , 'float'],
            'type_double_precision'  => ['float8'       , 'float'],
            'type_numeric'           => ['numeric'      , Decimal::class],
            'type_money'             => ['money'        , 'string'],
            'type_smallserial'       => ['int2'         , 'int'],
            'type_serial'            => ['int4'         , 'int'],
            'type_bigserial'         => ['int8'         , 'int'],
            'type_bit'               => ['bit'          , 'int'],
            'type_bit_over_64'       => ['bit'          , 'string'],
            'type_bit_varying'       => ['varbit'       , 'int'],
            'type_boolean'           => ['bool'         , 'bool'],
            'type_uuid'              => ['uuid'         , 'string'],
            'type_character'         => ['bpchar'       , 'string'],
            'type_character_varying' => ['varchar'      , 'string'],
            'type_text'              => ['text'         , 'string'],
            'type_bytea'             => ['bytea'        , 'resource'],
            'type_cidr'              => ['cidr'         , 'string'],
            'type_inet'              => ['inet'         , 'string'],
            'type_macaddr'           => ['macaddr'      , 'string'],
            'type_date'              => ['date'         , Date::class],
            'type_timestamp'         => ['timestamp'    , DateTime::class],
            'type_timestamp_with_tz' => ['timestamptz'  , DateTime::class],
            'type_time'              => ['time'         , 'string'],
            'type_time_with_tz'      => ['timetz'       , 'string'],
            'type_interval'          => ['interval'     , 'string'],
            'type_interval_hour'     => ['interval'     , 'string'],
            'type_json'              => ['json'         , 'array'],
            'type_jsonb'             => ['jsonb'        , 'array'],
            'type_xml'               => ['xml'          , \SimpleXMLElement::class],
            'type_box'               => ['box'          , 'string'],
            'type_circle'            => ['circle'       , 'string'],
            'type_line'              => ['line'         , 'string'],
            'type_lseg'              => ['lseg'         , 'string'],
            'type_path_open'         => ['path'         , 'string'],
            'type_path_close'        => ['path'         , 'string'],
            'type_point'             => ['point'        , 'string'],
            'type_polygon'           => ['polygon'      , 'string'],
            'type_pg_lsn'            => ['pg_lsn'       , 'string'],
            'type_tsquery'           => ['tsquery'      , 'string'],
            'type_tsvector'          => ['tsvector'     , 'string'],
            'type_txid_snapshot'     => ['txid_snapshot', 'string'],
            'type_text_null'         => ['text'         , null],
        ] as $col => [$native_type, $php_type]) {
            $meta_native_type = $meta[$col]['native_type'] ?? null;
            $this->assertSame($native_type, $meta_native_type, "Failed {$col} => {$native_type} actual {$meta_native_type}");
            $value = Strings::stringify($rs->$col);
            $this->assertSame($php_type, Reflector::getType($rs->$col), "Failed {$col} => {$native_type} actual {$meta_native_type} {$value}");
        }
    }

    public function test_toPhpType_sqlsrv()
    {
        if (!($db = $this->connect('sqlsrv'))) {
            return;
        }
        $db->execute("DROP TABLE IF EXISTS native_types;");
        $dml = <<<EOS
            CREATE TABLE native_types (
                type_bigint               bigint,
                type_int                  int,
                type_smallint             smallint,
                type_tinyint              tinyint,
                type_bit                  bit,
                type_bit_bool             bit,

                type_numeric              numeric(5, 2),
                type_decimal              decimal(5, 2),

                type_money                money,
                type_smallmoney           smallmoney,

                type_float                float,
                type_real                 real,

                type_date                 date,
                type_datetime2            datetime2,         -- (7)
                type_datetime2_3          datetime2(3),
                type_datetime2_6          datetime2(6),
                type_datetime             datetime,
                type_datetime_milli       datetime,
                type_datetimeoffset       datetimeoffset,    -- (7)
                type_datetimeoffset_3     datetimeoffset(3),
                type_datetimeoffset_6     datetimeoffset(6),
                type_smalldatetime        smalldatetime,
                type_time                 time,              -- (7)
                type_time_3               time(3),
                type_time_6               time(6),

                type_char                 char(5),
                type_varchar              varchar(5),
                type_varchar_max          varchar(max),
                type_text                 text,               -- Deprecated, use varchar(max)

                type_nchar                nchar(5),
                type_nvarchar             nvarchar(5),
                type_nvarchar_max         nvarchar(max),
                type_ntext                ntext,              -- Deprecated, use nvarchar(max)

                type_binary               binary(500),
                type_varbinary            varbinary(500),
                type_varbinary_max        varbinary(max),
                type_image                image,              -- Deprecated, use varbinary(max)

                type_rowversion           rowversion,
                type_hierarchyid          hierarchyid,
                type_uniqueidentifier     uniqueidentifier,
                type_sql_variant_int      sql_variant,
                type_sql_variant_float    sql_variant,
                type_sql_variant_string   sql_variant,
                type_xml                  xml,
                type_geometry             geometry,
                type_geography            geography
            );
EOS;
        $db->execute($dml);

        $binary = file_get_contents(App::structure()->public('/assets/img/72x72.png'), 'r');

        $db->begin();
        $db->execute("INSERT INTO native_types VALUES (:values)", ['values' => [
            1,                                                                                                            // type_bigint
            1,                                                                                                            // type_int
            1,                                                                                                            // type_smallint
            1,                                                                                                            // type_tinyint
            1,                                                                                                            // type_bit
            true,                                                                                                         // type_bit_bool
            123.45,                                                                                                       // type_numeric
            123.45,                                                                                                       // type_decimal
            123.45,                                                                                                       // type_money
            123.45,                                                                                                       // type_smallmoney
            123.45,                                                                                                       // type_float
            123.45,                                                                                                       // type_real
            '2010-01-02',                                                                                                 // type_date
            '2010-01-02 10:20:30',                                                                                        // type_datetime2
            '2010-01-02 10:20:30.456',                                                                                    // type_datetime2_3
            '2010-01-02 10:20:30.456789',                                                                                 // type_datetime2_6
            '2010-01-02 10:20:30',                                                                                        // type_datetime
            '2010-01-02 10:20:30.456',                                                                                    // type_datetime_milli
            '2010-01-02 10:20:30 +09:00',                                                                                 // type_datetimeoffset (7)
            '2010-01-02 10:20:30.456 +09:00',                                                                             // type_datetimeoffset_3
            '2010-01-02 10:20:30.456789 +09:00',                                                                          // type_datetimeoffset_6
            '2010-01-02 10:20',                                                                                           // type_smalldatetime
            '10:20:30',                                                                                                   // type_time
            '10:20:30',                                                                                                   // type_time_3
            '10:20:30',                                                                                                   // type_time_6
            'abc',                                                                                                        // type_char
            'abc',                                                                                                        // type_varchar
            'abc',                                                                                                        // type_varchar_max
            null,                                                                                                         // -- type_text (Deprecated)
            'abc',                                                                                                        // type_nchar
            'abc',                                                                                                        // type_nvarchar
            'abc',                                                                                                        // type_nvarchar_max
            null,                                                                                                         // -- type_ntext (Deprecated)
            PdoParameter::lob($binary, \PDO::SQLSRV_ENCODING_BINARY),                                                     // type_binary
            PdoParameter::lob($binary, \PDO::SQLSRV_ENCODING_BINARY),                                                     // type_varbinary
            PdoParameter::lob($binary, \PDO::SQLSRV_ENCODING_BINARY),                                                     // type_varbinary_max
            null,                                                                                                         // -- type_image (Deprecated)
            Expression::of('DEFAULT'),                                                                                    // type_rowversion
            Expression::of('CAST({0} AS hierarchyid)', '/3/1/'),                                                          // type_hierarchyid
            '6F9619FF-8B86-D011-B42D-00C04FC964FF',                                                                       // type_uniqueidentifier
            1,                                                                                                            // type_sql_variant_int
            123.45,                                                                                                       // type_sql_variant_float
            'abc',                                                                                                        // type_sql_variant_string
            '<book><title>Manual</title><author>John</author></book>',                                                    // type_xml
            Expression::of('geometry::STGeomFromText({0}, {1})', 'LINESTRING (100 100, 20 180, 180 180)', 0),             // type_geometry
            Expression::of('geography::STGeomFromText({0}, {1})', 'LINESTRING (-122.361 47.656, -122.343 47.656)', 4326), // type_geography
        ]]);
        $db->commit();
        $stmt = $db->query('SELECT *, CAST(type_hierarchyid as varchar(max)) AS type_hierarchyid_varchar, CAST(type_geometry as varchar(max)) AS type_geometry_varchar, CAST(type_geography as varchar(max)) AS type_geography_varchar FROM native_types');
        $meta = $stmt->meta();
        $rs   = $stmt->first();
        foreach ([
            'type_bigint'               => ['bigint'          , 'int'                   , 1],
            'type_int'                  => ['int'             , 'int'                   , 1],
            'type_smallint'             => ['smallint'        , 'int'                   , 1],
            'type_tinyint'              => ['tinyint'         , 'int'                   , 1],
            'type_bit'                  => ['bit'             , 'bool'                  , true],
            'type_bit_bool'             => ['bit'             , 'bool'                  , true],
            'type_numeric'              => ['numeric'         , Decimal::class          , Decimal::of('123.45')],
            'type_decimal'              => ['decimal'         , Decimal::class          , Decimal::of('123.45')],
            'type_money'                => ['money'           , 'string'                , '123.4500'],
            'type_smallmoney'           => ['smallmoney'      , 'string'                , '123.4500'],
            'type_float'                => ['float'           , 'float'                 , 123.45],
            'type_real'                 => ['real'            , 'float'                 , 123.45],
            'type_date'                 => ['date'            , Date::class             , Date::createDateTime('2010-01-02')],
            'type_datetime2'            => ['datetime2'       , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30')],
            'type_datetime2_3'          => ['datetime2'       , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30.456')],
            'type_datetime2_6'          => ['datetime2'       , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30.456789')],
            'type_datetime'             => ['datetime'        , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30')],
            'type_datetime_milli'       => ['datetime'        , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30.457')], // value of millisec rounded to .000, .003 or .007
            'type_datetimeoffset'       => ['datetimeoffset'  , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30 +09:00', ['Y-m-d H:i:s P'])],
            'type_datetimeoffset_3'     => ['datetimeoffset'  , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30.456 +09:00', ['Y-m-d H:i:s.u P'])],
            'type_datetimeoffset_6'     => ['datetimeoffset'  , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20:30.456789 +09:00', ['Y-m-d H:i:s.u P'])],
            'type_smalldatetime'        => ['smalldatetime'   , DateTime::class         , DateTime::createDateTime('2010-01-02 10:20')],
            'type_time'                 => ['time'            , 'string'                , '10:20:30.0000000'],
            'type_time_3'               => ['time'            , 'string'                , '10:20:30.000'],
            'type_time_6'               => ['time'            , 'string'                , '10:20:30.000000'],
            'type_char'                 => ['char'            , 'string'                , 'abc  '],
            'type_varchar'              => ['varchar'         , 'string'                , 'abc'],
            'type_varchar_max'          => ['varchar'         , 'string'                , 'abc'],
            'type_text'                 => ['text'            , null                    , null], // Deprecated
            'type_nchar'                => ['nchar'           , 'string'                , 'abc  '],
            'type_nvarchar'             => ['nvarchar'        , 'string'                , 'abc'],
            'type_nvarchar_max'         => ['nvarchar'        , 'string'                , 'abc'],
            'type_ntext'                => ['ntext'           , null                    , null], // Deprecated
            'type_binary'               => ['binary'          , 'string'                , str_pad($binary, 500, "\0")],
            'type_varbinary'            => ['varbinary'       , 'string'                , $binary],
            'type_varbinary_max'        => ['varbinary'       , 'string'                , $binary],
            'type_image'                => ['image'           , null                    , null], // Deprecated
            'type_rowversion'           => ['timestamp'       , 'string'                , null], // not check value
            'type_hierarchyid'          => ['udt'             , null                    , null],
            'type_uniqueidentifier'     => ['uniqueidentifier', 'string'                , '6F9619FF-8B86-D011-B42D-00C04FC964FF'],
            'type_sql_variant_int'      => ['sql_variant'     , 'string'                , '1'],
            'type_sql_variant_float'    => ['sql_variant'     , 'string'                , '123.45'],
            'type_sql_variant_string'   => ['sql_variant'     , 'string'                , 'abc'],
            'type_xml'                  => ['xml'             , \SimpleXMLElement::class, new SimpleXMLElement('<book><title>Manual</title><author>John</author></book>')],
            'type_geometry'             => ['udt'             , null                    , null],
            'type_geography'            => ['udt'             , null                    , null],
            'type_hierarchyid_varchar'  => ['varchar'         , 'string'                , '/3/1/'],
            'type_geometry_varchar'     => ['varchar'         , 'string'                , 'LINESTRING (100 100, 20 180, 180 180)'],
            'type_geography_varchar'    => ['varchar'         , 'string'                , 'LINESTRING (-122.361 47.656, -122.343 47.656)'],
        ] as $col => [$native_type, $php_type, $value]) {
            $meta_native_type = $meta[$col]['sqlsrv:decl_type'] ?? null;
            $this->assertSame($native_type, $meta_native_type, "Native Type Failed {$col} => {$native_type} actual {$meta_native_type}");
            $this->assertSame($php_type, $actual_php_type = Reflector::getType($rs->$col), "PHP Type Failed {$col} => {$php_type} actual {$actual_php_type}");
            if ($value) {
                $this->assertEquals($value, $rs->$col, "PHP Value Failed {$col} => {$value} actual {$rs->$col}");
            }
        }
    }

    public function dataToPdoTypes() : array
    {
        $this->setUp();
        $path = App::structure()->public('/assets/img/72x72.png');
        $file = file_get_contents($path, 'r');
        return [
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), PdoParameter::int(1)],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), 1],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('a'), 'a'],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::int(1), Gender::MALE()],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::null(), null],
            [['sqlite', 'pgsql', 'sqlsrv'], PdoParameter::bool(true), true],
            [['mysql', 'mariadb'], PdoParameter::int(1), true],
            [['sqlite', 'pgsql', 'sqlsrv'], PdoParameter::bool(false), false],
            [['mysql', 'mariadb'], PdoParameter::int(0), false],
            [['sqlite', 'mysql', 'mariadb', 'pgsql'], PdoParameter::lob($file), function () use ($path) { return fopen($path, 'r'); }],
            [['sqlsrv'], PdoParameter::lob($file, \PDO::SQLSRV_ENCODING_BINARY), function () use ($path) { return fopen($path, 'r'); }],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('2001-02-03'), Date::today()],
            [['sqlite', 'mysql', 'mariadb', 'sqlsrv'], PdoParameter::str('2001-02-03 04:05:06'), DateTime::now()],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0000'), DateTime::now()],
            [['sqlite', 'mysql', 'mariadb', 'sqlsrv'], PdoParameter::str('2001-02-03 04:05:06'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0900'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'], PdoParameter::str('1234.5678'), new Decimal('1,234.5678')],
        ];
    }

    /**
     * @dataProvider dataToPdoTypes
     */
    public function test_toPdoType(array $target_db, PdoParameter $expect, $value)
    {
        $this->eachDb(function (Database $db) use ($target_db, $expect, $value) {
            if (!in_array($db->name(), $target_db)) {
                return;
            }
            if (is_callable($value)) {
                $value = $value();
            }
            $this->assertEquals($expect, $db->converter()->toPdoType($value));
        });
    }
}
