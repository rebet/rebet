<?php
namespace Rebet\Tests\Database\Converter;

use Rebet\Common\Decimal;
use Rebet\Common\Reflector;
use Rebet\Database\Converter\BuiltinConverter;
use Rebet\Database\Database;
use Rebet\Database\Expression;
use Rebet\Database\PdoParameter;
use Rebet\DateTime\Date;
use Rebet\DateTime\DateTime;
use Rebet\DateTime\DateTimeZone;
use Rebet\Foundation\App;
use Rebet\Tests\Mock\Enum\Gender;
use Rebet\Tests\RebetDatabaseTestCase;

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
            1,                                                                                                       // type_tinyint
            1,                                                                                                       // type_smallint
            1,                                                                                                       // type_mediumint
            1,                                                                                                       // type_int
            1,                                                                                                       // type_integer
            1,                                                                                                       // type_bigint
            true,                                                                                                    // type_bool
            true,                                                                                                    // type_boolean
            true,                                                                                                    // type_tinyint_one
            0b111,                                                                                                   // type_bit
            123.45,                                                                                                  // type_decimal
            123.45,                                                                                                  // type_dec
            123.45,                                                                                                  // type_numeric
            123.45,                                                                                                  // type_float
            123.45,                                                                                                  // type_double
            '2010-01-02',                                                                                            // type_date
            '2010-01-02 10:20:30',                                                                                   // type_datetime
            '2010-01-02 10:20:30',                                                                                   // type_timestamp
            '10:20:30',                                                                                              // type_time
            2010,                                                                                                    // type_year
            'abc',                                                                                                   // type_char
            'abc',                                                                                                   // type_varchar
            'abc',                                                                                                   // type_binary
            'abc',                                                                                                   // type_varbinary
            'abc',                                                                                                   // type_tinyblob
            'abc',                                                                                                   // type_blob
            'abc',                                                                                                   // type_mediumblob
            'abc',                                                                                                   // type_longblob
            'abc',                                                                                                   // type_tinytext
            'abc',                                                                                                   // type_text
            'abc',                                                                                                   // type_mediumtext
            'b',                                                                                                     // type_enum
            'a,b',                                                                                                   // type_set
            Expression::of('GeomFromText(?)', 'POINT(1 1)'),                                                         // type_geometry
            Expression::of('GeomFromText(?)', 'POINT(1 1)'),                                                         // type_point
            Expression::of('GeomFromText(?)', 'LINESTRING(0 0,1 1,2 2)'),                                            // type_linestring
            Expression::of('GeomFromText(?)', 'POLYGON((0 0,10 0,10 10,0 10,0 0),(5 5,7 5,7 7,5 7,5 5))'),           // type_polygon
            Expression::of('GeomFromText(?)', 'MULTIPOINT(1 1,2 2,3 3)'),                                            // type_multipoint
            Expression::of('GeomFromText(?)', 'MULTILINESTRING((0 0,1 1,2 2), (0 2,1 1,2 0))'),                      // type_multilinestring
            Expression::of('GeomFromText(?)', 'MULTIPOLYGON(((0 0,10 0,10 10,0 10,0 0)),((5 5,7 5,7 7,5 7, 5 5)))'), // type_multipolygon
            Expression::of('GeomFromText(?)', 'GEOMETRYCOLLECTION(POINT(1 1),LINESTRING(0 0,1 1,2 2,3 3,4 4))'),     // type_geometrycollection
            Expression::of('GeomFromText(?)', null),                                                                 // type_geometry_null
            null,                                                                                                    // type_text_null
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
            1,                                                                                                // type_smallint
            1,                                                                                                // type_integer
            1,                                                                                                // type_bigint
            123.45,                                                                                           // type_real
            123.45,                                                                                           // type_double_precision
            123.45,                                                                                           // type_numeric
            123.45,                                                                                           // type_money
            Expression::of('nextval(?)', 'native_types_type_smallserial_seq'),                                // type_smallserial
            Expression::of('nextval(?)', 'native_types_type_serial_seq'),                                     // type_serial
            Expression::of('nextval(?)', 'native_types_type_bigserial_seq'),                                  // type_bigserial
            '111',                                                                                            // type_bit
            '11111111111111111111111111111111111111111111111111111111111111111',                              // type_bit_over_64
            '111',                                                                                            // type_bit_varying
            true,                                                                                             // type_boolean
            '{a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11}',                                                         // type_uuid
            'abc',                                                                                            // type_character
            'abc',                                                                                            // type_character_varying
            'abc',                                                                                            // type_text
            'abc',                                                                                            // type_bytea
            '192.168.1.0/24',                                                                                 // type_cidr
            '192.168.1.0/24',                                                                                 // type_inet
            '08:00:2b:01:02:03',                                                                              // type_macaddr
            '2010-01-02',                                                                                     // type_date
            '2010-01-02 10:20:30',                                                                            // type_timestamp
            '2010-01-02 10:20:30+09',                                                                         // type_timestamp_with_tz
            '10:20:30',                                                                                       // type_time
            '10:20:30+09',                                                                                    // type_time_with_tz
            '1 day',                                                                                          // type_interval
            '1 hour',                                                                                         // type_interval_hour
            '{"name":"John", "tags":["PHP","Rebet"]}',                                                        // type_json
            '{"name":"John", "tags":["PHP","Rebet"]}',                                                        // type_jsonb
            Expression::of('XMLPARSE(CONTENT ?)', '<book><title>Manual</title><author>John</author></book>'), // type_xml
            '((0,0),(1,1))',                                                                                  // type_box
            '<(0,0),1>',                                                                                      // type_circle
            '((0,0),(1,1))',                                                                                  // type_line
            '((0,0),(1,1))',                                                                                  // type_lseg
            '((0,0),(0,1),(1,1),(1,0),(0,0))',                                                                // type_path_close
            '[(0,0),(0,1),(1,1),(1,0)]',                                                                      // type_path_open
            '(0,1)',                                                                                          // type_point
            '((0,0),(0,1),(1,1),(1,0),(0,0))',                                                                // type_polygon
            '16/B374D848',                                                                                    // type_pg_lsn
            'fat & rat',                                                                                      // type_tsquery
            'a fat cat sat on a mat and ate a fat rat',                                                       // type_tsvector
            Expression::of('txid_current_snapshot()'),                                                        // type_txid_snapshot
            null                                                                                              // type_text_null
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
            $this->assertSame($php_type, Reflector::getType($rs->$col), "Failed {$col} => {$native_type} actual {$meta_native_type} {$rs->$col}");
        }
    }

    public function dataToPdoTypes() : array
    {
        $this->setUp();
        $file = fopen(App::path('/resources/image/72x72.png'), 'r');
        return [
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::int(1), PdoParameter::int(1)],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::int(1), 1],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::str('a'), 'a'],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::int(1), Gender::MALE()],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::str('POINT(1 1)'), Expression::of('GeomFromText(?)', 'POINT(1 1)')],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::int(1), Expression::of('SUM(?)', 1)],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::null(), null],
            [['sqlite', 'pgsql'], PdoParameter::bool(true), true],
            [['mysql'], PdoParameter::int(1), true],
            [['sqlite', 'pgsql'], PdoParameter::bool(false), false],
            [['mysql'], PdoParameter::int(0), false],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::lob($file), $file],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::str('2001-02-03'), Date::today()],
            [['sqlite', 'mysql'], PdoParameter::str('2001-02-03 04:05:06'), DateTime::now()],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0000'), DateTime::now()],
            [['sqlite', 'mysql'], PdoParameter::str('2001-02-03 04:05:06'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['pgsql'], PdoParameter::str('2001-02-03 04:05:06+0900'), new \DateTime('2001-02-03 04:05:06', new DateTimeZone('Asia/Tokyo'))],
            [['sqlite', 'mysql', 'pgsql'], PdoParameter::str('1234.5678'), new Decimal('1,234.5678')],

        ];
    }

    /**
     * @dataProvider dataToPdoTypes
     */
    public function test_toPdoType(array $target_db, PdoParameter $expect, $value)
    {
        $converter = new BuiltinConverter();
        $this->eachDb(function (Database $db) use ($converter, $target_db, $expect, $value) {
            if (!in_array($db->name(), $target_db)) {
                return;
            }
            $this->assertEquals($expect, $converter->toPdoType($db, $value));
        });
    }
}
