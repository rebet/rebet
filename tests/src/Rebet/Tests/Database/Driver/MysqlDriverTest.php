<?php
namespace Rebet\Tests\Database\Driver;

use Rebet\Database\Database;
use Rebet\Database\Expression;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\Date;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Math\Decimal;
use Rebet\Tools\Reflection\Reflector;

class MysqlDriverTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function test_toPhpType()
    {
        $this->eachDb(function (Database $db) {
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
        }, 'mysql', 'mariadb');
    }
}
