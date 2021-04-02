<?php
namespace Rebet\Tests\Database\Driver;

use Rebet\Database\Dao;
use Rebet\Tests\RebetDatabaseTestCase;
use Rebet\Tools\DateTime\DateTime;
use Rebet\Tools\Reflection\Reflector;

class SqliteDriverTest extends RebetDatabaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        DateTime::setTestNow('2001-02-03 04:05:06');
    }

    public function test_toPhpType()
    {
        $db = Dao::db('sqlite');
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
}
