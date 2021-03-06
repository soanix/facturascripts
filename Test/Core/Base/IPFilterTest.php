<?php
namespace FacturaScripts\Core\Base;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2017-07-20 at 11:48:14.
 */
class IPFilterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var IPFilter
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new IPFilter(PHPUNIT_PATH);
        $this->object->clear();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @covers FacturaScripts\Core\Base\IPFilter::setAttempt
     */
    public function testSetAttempt()
    {
        $this->object->clear();
        $this->object->setAttempt('192.168.1.1');

        /// leemos directamente del archivo para ver si hay algo
        $data = file_get_contents(PHPUNIT_PATH . '/Cache/ip.list');
        $this->assertNotEmpty($data);
    }

    /**
     * @covers FacturaScripts\Core\Base\IPFilter::isBanned
     */
    public function testIsBanned()
    {
        /// forzamos el baneo de la IP
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');

        $this->assertTrue($this->object->isBanned('192.168.1.1'));
    }
}
