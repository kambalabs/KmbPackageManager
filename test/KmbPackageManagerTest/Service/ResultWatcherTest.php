<?php
namespace KmbPackageManagerTest\Service;

use KmbMcollective\Model\ActionLog;
use KmbMcollective\Model\CommandLog;
use KmbPackageManagerTest\Bootstrap;
use KmbPackageManagerTest\DatabaseInitTrait;
use Zend\Db\Adapter\AdapterInterface;

class ResultWatcherTest extends \PHPUnit_Framework_TestCase
{

    use DatabaseInitTrait;

    /** @var \PDO */
    protected static $connection;

    /** @var ActionLogRepository */
    protected static $repository;

    public static function setUpBeforeClass()
    {
        $serviceManager = Bootstrap::getServiceManager();
        static::$repository = $serviceManager->get('ActionLogRepository');

        /** @var $dbAdapter AdapterInterface */
        $dbAdapter = $serviceManager->get('Zend\Db\Adapter\Adapter');
        static::$connection = $dbAdapter->getDriver()->getConnection()->getResource();

        static::initSchema(static::$connection);
    }

    protected function setUp()
    {
        static::initFixtures(static::$connection);
    }

    /** @test */
    public function canWatchForResults(){
        $serviceManager = Bootstrap::getServiceManager();
        $handler = $serviceManager->get('ResultWatcher');
        $this->assertTrue(true);

    }

}
