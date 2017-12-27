<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class RelationalDatabase implements StorageInterface
{

    const TABLE = 'magium_configuration_values';

    protected $adapter;
    protected $configurationFile;
    protected $data = [];
    protected $table;

    public function __construct(
        Adapter $adapter,
        AbstractContextConfigurationFile $context,
        $table = self::TABLE
    )
    {
        $adapter->getDriver()->getConnection()->connect(); // Force a consistent connect point
        $this->adapter = $adapter;
        $this->configurationFile = $context;
        $this->table = $table;
    }

    public function getContexts()
    {
        return $this->configurationFile->getContexts();
    }

    /**
     * @param $requestedContext
     * @throws InvalidContextException
     * @return array an array of the contexts that are in the requested context's path
     */

    public function getPathForContext($requestedContext)
    {
        $names = [];

        if ($requestedContext !== ConfigurationRepository::CONTEXT_DEFAULT) {
            $contexts = $this->getContexts();
            $xml = $this->configurationFile->toXml();
            if (!in_array($requestedContext, $contexts)) {
                throw new InvalidContextException('Unable to find context: ' . $requestedContext);
            }

            $xml->registerXPathNamespace('s', 'http://www.magiumlib.com/ConfigurationContext');
            $contexts = $xml->xpath(sprintf('//s:context[@id="%s"]', $requestedContext));

            $context = array_shift($contexts);
            do {

                if (!$context instanceof \SimpleXMLElement) {
                    break;
                }
                $names[] = (string)$context['id'];
            } while ($context = $context->xpath('..'));
        }
        $names[] = ConfigurationRepository::CONTEXT_DEFAULT;

        return $names;
    }

    public function getValue($path, $requestedContext = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        if (empty($this->data)) {
            $contexts = $this->getContexts();
            foreach ($contexts as $context) {
                $sql = new Sql($this->adapter);
                $select = $sql->select(self::TABLE);
                $select->where(['context'=> $context]);
                $statement = $sql->prepareStatementForSqlObject($select);
                $resultSet = $statement->execute();
                $this->data[$context] = [];
                foreach ($resultSet as $result) {
                    $this->data[$context][$result['path']] = $result['value'];
                }
            }
        }
        $contextPaths = $this->getPathForContext($requestedContext);
        foreach ($contextPaths as $contextPath) {
            if (isset($this->data[$contextPath][$path])) {
                return $this->data[$contextPath][$path];
            }
        }
        return null;
    }

    public function setValue($path, $value, $requestedContext = ConfigurationRepository::CONTEXT_DEFAULT)
    {
        $contexts = $this->getPathForContext($requestedContext);
        if (!in_array($requestedContext, $contexts)) {
            throw new InvalidContextException('Could not find the context: ' . $requestedContext);
        }

        $sql = new Sql($this->adapter);

        /*
         * We can't do an ON DUPLICATE UPDATE because not all adapters support that.  So we need to do a select
         * followed by an insert or update
         */

        if ($this->exists($sql, $path, $requestedContext)) {
            $this->doUpdate($sql, $path, $value, $requestedContext);
        } else {
            $this->doInsert($sql, $path, $value, $requestedContext);
        }

        $this->data[$requestedContext][$path] = $value;
    }

    protected function exists(Sql $sql, $path, $context)
    {
        $select = $sql->select(self::TABLE);
        $select->columns(['cnt' => new Expression('COUNT(*)')]);
        $select->where(['path' => $path, 'context' => $context]);
        $select = $select->getSqlString($this->adapter->getPlatform());

        $result = $this->adapter->query($select)->execute(  );
        $check = $result->current();
        return $check && $check['cnt'] > 0;
    }

    protected function doInsert(Sql $sql, $path, $value, $context)
    {
        $insert = $sql->insert(self::TABLE);
        $insert->values([
            'path'      => $path,
            'value'     => $value,
            'context'   => $context
        ]);
        $insert = $insert->getSqlString($this->adapter->getPlatform());

        $this->adapter->query($insert)->execute();
    }

    protected function doUpdate(Sql $sql, $path, $value, $context)
    {
        $update = $sql->update(self::TABLE);
        $update->set([
            'value' => $value
        ])->where(['path' => $path, 'context' => $context]);

        $update = $update->getSqlString($this->adapter->getPlatform());
        $this->adapter->query($update)->execute();
    }

    public function create()
    {
        $table = new CreateTable($this->table);
        $table->addColumn(new Varchar('path', 255));
        $table->addColumn(new Text('value'));
        $table->addColumn(new Varchar('context', 255));
        $table->addConstraint(
            new UniqueKey(['path','context'], 'configuration_uniqueness_index')
        );

        $sql = new Sql($this->adapter);

        $this->adapter->query(
            $sql->buildSqlString($table),
            Adapter::QUERY_MODE_EXECUTE
        );
    }

}
