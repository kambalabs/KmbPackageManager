<?php
namespace KmbPackageManagerTest\Service;


use KmbPackageManagerTest\Bootstrap;

class ResultWatcherFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canCreateService()
    {
        $service = Bootstrap::getServiceManager()->get('ResultWatcher');
        $this->assertInstanceOf('KmbPackageManager\Service\ResultWatcher', $service);
        $this->assertInstanceOf('KmbMcollective\Service\ActionLogRepository',$service->getActionLogRepository());

    }
}
