<?php
namespace Rebet\Tests\Database\Driver;

use Rebet\Application\App;
use Rebet\Database\Expression;
use Rebet\Database\PdoParameter;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;
use SimpleXMLElement;

class SqlsrvDriverTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function test_toPhpType()
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
}
