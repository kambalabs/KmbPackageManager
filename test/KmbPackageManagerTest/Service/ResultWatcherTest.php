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
        $resultList = $handler->watchFor('b1ddad6cb8233287f8087dc36074b80a',1,10);
        $this->assertEquals(1, count($resultList));

    }

    /** @test */
    public function canWatchForActionResult(){
        $serviceManager = Bootstrap::getServiceManager();
        $handler = $serviceManager->get('ResultWatcher');
        $resultList = $handler->watchFor('b1ddad6cb8233287f8087dc36074b80a',1,10,'deadbeefb8233287f8087dc36074b80a');
        $this->assertEquals([], $resultList);
        $resultList = $handler->watchFor('b1ddad6cb8233287f8087dc36074b80a',1,10,'b1ddad6cb8233287f8087dc36074b80a');
        $this->assertEquals(1,count($resultList));

    }

}
