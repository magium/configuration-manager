<?php

namespace Magium\Configuration\Tests\Storage;

use Magium\Configuration\Config\Config;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\Config\Storage\RelationalDatabase;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\File\Context\XmlFile;
use PHPUnit\Framework\TestCase;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;

class CrudTest extends TestCase
{

    protected $sqlite;

    public function testRelationalCreate()
    {
        $db = $this->createDatabase();
        $db->setValue('path', 'value');
        $value = $db->getValue('path');
        self::assertEquals('value', $value);
    }

    public function testRelationalCreateOnInvalidContextWithoutConfigThrowsException()
    {
        $this->expectException(InvalidContextException::class);
        $db = $this->createDatabase();
        $db->setValue('path', 'value', 'boogers');
    }

    public function testRelationalCreateOnInvalidContextThrowsException()
    {
        $this->expectException(InvalidContextException::class);
        $db = $this->createDatabase(new XmlFile(__DIR__ . '/xml/context.xml'));
        $db->setValue('path', 'value', 'boogers');
    }

    public function testGetContexts()
    {
        $contextFile = new XmlFile(__DIR__ . '/xml/context.xml');
        $db = $this->createDatabase($contextFile);
        $contexts = $db->getContexts();
        self::assertContains(Config::CONTEXT_DEFAULT, $contexts);
        self::assertContains('primary', $contexts);
        self::assertContains('secondary', $contexts);
    }

    public function testRelationalContextBasedCreate()
    {
        $contextFile = new XmlFile(__DIR__ . '/xml/context.xml');
        $db = $this->createDatabase($contextFile);
        $db->setValue('path', 'value');
        $defaultValue = $db->getValue('path');
        $primaryValue = $db->getValue('path', 'primary');
        $secondaryValue = $db->getValue('path', 'secondary');
        self::assertEquals('value', $defaultValue);
        self::assertEquals($defaultValue, $primaryValue);
        self::assertEquals($primaryValue, $secondaryValue);
    }

    public function testRelationalContextBasedCreateWithMultipleContexts()
    {
        $contextFile = new XmlFile(__DIR__ . '/xml/context.xml');
        $db = $this->createDatabase($contextFile);
        $db->setValue('path', 'value');
        $db->setValue('path', 'value 2', 'secondary');
        $defaultValue = $db->getValue('path');
        $primaryValue = $db->getValue('path', 'primary');
        $secondaryValue = $db->getValue('path', 'secondary');
        self::assertEquals('value', $defaultValue);
        self::assertEquals($defaultValue, $primaryValue);
        self::assertEquals('value 2', $secondaryValue);
    }

    public function testGetValueFromDatabaseWithPreviousValues()
    {
        $adapter = $this->getAdapter();
        $db = $this->createDatabase(new XmlFile(__DIR__ . '/xml/context.xml'));
        $sql = new Sql($adapter);
        $insert = $sql->insert(RelationalDatabase::TABLE);
        $values = [
            'path'       => 'path',
            'value'      => 'value',
            'context'   => Config::CONTEXT_DEFAULT
        ];
        $insert->columns(array_keys($values));
        $insert->values($values);
        $adapter->query($insert->getSqlString($adapter->getPlatform()), Adapter::QUERY_MODE_EXECUTE);
        $values['value'] = 'primary';
        $values['context'] = 'primary';
        $insert->values($values);
        $adapter->query($insert->getSqlString($adapter->getPlatform()), Adapter::QUERY_MODE_EXECUTE);

        $default = $db->getValue('path');
        $primary = $db->getValue('path', 'primary');

        self::assertEquals('value', $default);
        self::assertEquals('primary', $primary);

    }

    protected function getAdapter()
    {
        if ($this->sqlite instanceof Adapter) {
            return $this->sqlite;
        }
        $this->sqlite = new Adapter(array(
            'driver'    => 'pdo',
            'dsn'       => 'sqlite::memory:'
        ));
        return $this->sqlite;
    }

    protected function createDatabase(AbstractContextConfigurationFile $contextConfigurationFile = null)
    {
        $sqlite = $this->getAdapter();
        $db = new RelationalDatabase($sqlite, $contextConfigurationFile);
        $db->create();
        return $db;
    }

    protected function tearDown()
    {
        $this->sqlite = null;
        parent::tearDown();
    }

}
