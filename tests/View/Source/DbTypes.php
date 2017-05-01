<?php

namespace Magium\Configuration\Tests\View\Source;

use Magium\Configuration\Source\SourceInterface;

class DbTypes implements SourceInterface
{

    public function getSourceData()
    {
        return [
            'mysqli' => 'MySqli',
            'sqlsrv' => 'Microsoft SQL Server',
            'oci8' => 'Oracle',
            'pgsql' => 'Postgres',
            'ibmdb2' => 'IBM DB2',
            'pdo_mysql' => 'PDO MySql',
            'pdo_pgsql' => 'PDO Postgres',
            'pdo_oci' => 'PDO Oracle',
            'pdo_dblib' => 'PDO DBlib',
            'pdo_sqlsrv' => 'PDO Microsoft SQL Server',
            'pdo_sqlite' => 'PDO SQLite',
        ];
    }

}
