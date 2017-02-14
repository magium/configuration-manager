<?php

namespace Magium\Configuration\Config\Storage;

use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Sql;

class RelationalDatabase implements StorageInterface
{

    const TABLE = 'magium_configuration_values';

    protected $adapter;
    protected $configurationFile;
    protected $data = [];

    public function __construct(
        Adapter $adapter,
        AbstractContextConfigurationFile $context = null
    )
    {
        $this->adapter = $adapter;
        $this->configurationFile = $context;
        if ($context instanceof AbstractContextConfigurationFile) {
            $this->configurationFile = $context->toXml();
            $this->configurationFile->registerXPathNamespace('s', 'http://www.magiumlib.com/ConfigurationContext');

        }
    }

    public function getContexts()
    {
        $contexts = [Config::CONTEXT_DEFAULT];
        if ($this->configurationFile instanceof \SimpleXMLElement) {
            $configuredContexts = $this->configurationFile->xpath('//s:context');
            foreach ($configuredContexts as $context) {
                $contexts[] = (string)$context['id'];
            }
        }
        return $contexts;
    }

    /**
     * @param $requestedContext
     * @throws InvalidContextException
     * @return array an array of the contexts that are in the requested context's path
     */

    public function getPathForContext($requestedContext)
    {
        $names = [];

        if ($requestedContext !== Config::CONTEXT_DEFAULT
            && $this->configurationFile instanceof \SimpleXMLElement) {
            $xpath = sprintf('//s:context[@id="%s"]', $requestedContext);
            $contexts = $this->configurationFile->xpath($xpath);
            if (!$contexts) {
                throw new InvalidContextException('Unable to find context: ' . $requestedContext);
            }

            $context = array_shift($contexts);
            do {

                if (!$context instanceof \SimpleXMLElement) {
                    break;
                }
                $names[] = (string)$context['id'];
            } while ($context = $context->xpath('..'));
        }
        $names[] = Config::CONTEXT_DEFAULT;

        return $names;
    }

    public function getValue($path, $requestedContext = Config::CONTEXT_DEFAULT)
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
                    $this->data[$context][$result['path']]  = $result['value'];
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

    public function setValue($path, $value, $requestedContext = Config::CONTEXT_DEFAULT)
    {
        $contexts = $this->getPathForContext($requestedContext);
        if (!in_array($requestedContext, $contexts)) {
            throw new InvalidContextException('Could not find the context: ' . $requestedContext);
        }
        $sql = new Sql($this->adapter);
        $insert = $sql->insert(self::TABLE);
        $insert->values([
            'path'      => $path,
            'value'     => $value,
            'context'   => $requestedContext
        ]);
        $this->adapter->query($insert);
        $this->data[$requestedContext][$path] = $value;
    }

    public function create()
    {
        $table = new CreateTable(self::TABLE);
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
