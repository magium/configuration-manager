<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\Config;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

class RelationalDatabase implements StorageInterface
{

    const TABLE = 'magium_configuration_values';

    protected $adapter;

    protected $data;

    public function __construct(
        Adapter $adapter
    )
    {
        $this->adapter = $adapter;
    }

    public function getValue($location, $context = Config::CONTEXT_DEFAULT)
    {
        $select = new Select(self::TABLE);
        $where = new Where();
        $where->equalTo('path', $location);
        $select->where($where);
    }

    public function setValue($path, $value, $context = Config::CONTEXT_DEFAULT)
    {

    }

    public function create()
    {
        $table = new CreateTable(self::TABLE);
        $table->addColumn(new Varchar('path', 255));
        $table->addColumn(new Text('value'));
        $table->addColumn(new Varchar('context', 255));

        $adapter = $this->getAdapter();
        $sql = new Sql($adapter);

        $adapter->query(
            $sql->buildSqlString($table),
            $adapter::QUERY_MODE_EXECUTE
        );
    }

}
