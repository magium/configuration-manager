<?php

namespace Magium\Configuration\Tests\Storage;

use Magium\Configuration\Config\Repository\ConfigurationRepository;
use Magium\Configuration\Config\InvalidContextException;
use Magium\Configuration\Config\Storage\RelationalDatabase;
use Magium\Configuration\File\Context\AbstractContextConfigurationFile;
use Magium\Configuration\File\Context\XmlFile;
use PHPUnit\Framework\TestCase;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Platform\Sqlite;
use Zend\Db\Sql\Sql;

class CrudTest extends TestCase
{

    protected $sqlite;

    public function testRelationalCreate()
    {
        $db = $this->createDatabase($this->createMock(XmlFile::class));
        $db->setValue('path', 'value');
        $value = $db->getValue('path');
        self::assertEquals('value', $value);
    }

    public function testRelationalCreateOnInvalidContextWithoutConfigThrowsException()
    {
        $this->expectException(InvalidContextException::class);
        $mock = $this->createMock(XmlFile::class);
        $mock->expects(self::once())->method('getContexts')->willReturn([ConfigurationRepository::CONTEXT_DEFAULT]);
        $db = $this->createDatabase($mock);
        $db->setValue('path', 'value', 'boogers');
    }

    public function testMakeSureUpdateAndInsertAreCalledAppropriately()
    {
        $adapter = $this->getMockBuilder(Adapter::class)->setConstructorArgs(
            [['driver' => 'pdo_sqlite', 'database' => ':memory:']]
        )->getMock();
        $adapter->expects(self::any())->method('getPlatform')->willReturn(
            new Sqlite(new \PDO('sqlite::memory:'))
        );

        $connection = $this->createMock(ConnectionInterface::class);

        $driver = $this->createMock(DriverInterface::class);
        $driver->expects(self::once())->method('getConnection')->willReturn($connection);

        $adapter->expects(self::once())->method('getDriver')->willReturn($driver);
        $me = $this;
        $adapter->expects(self::exactly(4))->method('query')->willReturnCallback(function($param) use ($me) {
            static $state = -1;
            $state++;
            $result = $me->createMock(ResultInterface::class);
            switch ($state) {
                case 0:
                    TestCase::assertContains('SELECT', $param);
                    $result->expects(self::once())->method('current')->willReturn(['cnt' => 0]);
                    $mock = $me->createMock(StatementInterface::class);
                    $mock->expects(TestCase::once())->method('execute')->willReturn($result);
                    return $mock;
                case 1:
                    TestCase::assertContains('INSERT', $param);
                    $mock = $me->createMock(StatementInterface::class);
                    $mock->expects(TestCase::once())->method('execute');
                    return $mock;
                case 2:
                    TestCase::assertContains('SELECT', $param);
                    $result->expects(self::once())->method('current')->willReturn(['cnt' => 1]);
                    $mock = $me->createMock(StatementInterface::class);
                    $mock->expects(TestCase::once())->method('execute')->willReturn($result);
                    return $mock;
                case 3:
                    TestCase::assertContains('UPDATE', $param);
                    $mock = $me->createMock(StatementInterface::class);
                    $mock->expects(TestCase::once())->method('execute');
                    return $mock;
            }
        });

        $relational = new RelationalDatabase($adapter, $this->createMock(XmlFile::class));
        $relational->setValue('path', 'value', ConfigurationRepository::CONTEXT_DEFAULT);
        $relational->setValue('path', 'value', ConfigurationRepository::CONTEXT_DEFAULT);
    }


    public function testNullReturnedForNonExistentPath()
    {
        $mock = $this->createMock(XmlFile::class);
        $mock->expects(self::once())->method('getContexts')->willReturn([ConfigurationRepository::CONTEXT_DEFAULT]);
        $db = $this->createDatabase($mock);
        $result = $db->getValue('no-existe');
        self::assertNull($result);
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
        self::assertContains(ConfigurationRepository::CONTEXT_DEFAULT, $contexts);
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
            'context'   => ConfigurationRepository::CONTEXT_DEFAULT
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

    protected function createDatabase(AbstractContextConfigurationFile $contextConfigurationFile)
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
