<?php
namespace Rebet\Tests\Database\Driver;

use Rebet\Database\Dao;
use Rebet\Database\Expression;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Strings;

class PgsqlDriverTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function test_toPhpType()
    {
        $db = Dao::db('pgsql');
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
}
