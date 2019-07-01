<?php
namespace Rebet\Tests;

use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\Operation\Composite;
use PHPUnit\DbUnit\Operation\Factory;
use PHPUnit\DbUnit\Operation\Operation;
use PHPUnit\DbUnit\TestCaseTrait;
use Rebet\Config\Config;
use Rebet\Database\Dao;
use Rebet\Database\Driver\PdoDriver;

/**
 * Rebet Database Test Case Class
 *
 * We define various helper methods to reduce the labor of testing.
 */
abstract class RebetDatabaseTestCase extends RebetTestCase
{
    use TestCaseTrait;

    protected static $pdo = null;

    private $connection = null;

    final public function getConnection()
    {
        if ($this->connection === null) {
            if (self::$pdo == null) {
                self::$pdo = new PdoDriver('sqlite::memory:');
            }
            $this->connection = $this->createDefaultDBConnection(self::$pdo, ':memory:');
            Config::application([
                Dao::class => [
                    'dbs' => [
                        'main' => [
                            'driver' => self::$pdo,
                            'dsn'    => 'sqlite::memory:',
                        ]
                    ],
                ]
            ]);
        }

        return $this->connection;
    }

    protected function getSetUpOperation()
    {
        return new Composite([
            new class($this) implements Operation {
                private $test_case;

                public function __construct($test_case)
                {
                    $this->test_case = $test_case;
                }

                public function execute(Connection $connection, IDataSet $dataSet)
                {
                    foreach ($this->test_case->getSchemaSet() as $table => $ddl) {
                        $connection->getConnection()->query($ddl);
                    }

                    foreach ($dataSet as $table_name => $table) {
                        $connection->getConnection()->query("CREATE TABLE IF NOT EXISTS {$table_name}(". join(',', $table->getTableMetaData()->getColumns()).");");
                    }
                }
            },
            Factory::TRUNCATE(),
            Factory::INSERT()
        ]);
    }

    protected function getTearDownOperation()
    {
        return new class($this) implements Operation {
            private $test_case;

            public function __construct($test_case)
            {
                $this->test_case = $test_case;
            }

            public function execute(Connection $connection, IDataSet $dataSet)
            {
                foreach ($this->test_case->getSchemaSet() as $table => $ddl) {
                    $connection->getConnection()->query("DROP TABLE IF EXISTS {$table};");
                }

                foreach ($dataSet->getTableNames() as $table) {
                    $connection->getConnection()->query("DROP TABLE IF EXISTS {$table};");
                }
            }
        };
    }

    public function getSchemaSet() : array
    {
        return [];
    }
}
