<?php
namespace Qobo\Utils\Test\TestCase\Shell\Task;

use Cake\TestSuite\ConsoleIntegrationTestCase;
use Qobo\Utils\Shell\Task\ModuleTask;

/**
 * Qobo\Utils\Shell\Task\ModuleTask Test Case
 */
class ModuleTaskTest extends ConsoleIntegrationTestCase
{

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|null
     */
    public $io;

    /**
     * Test subject
     *
     * @var \Qobo\Utils\Shell\Task\ModuleTask
     */
    public $ModuleTask;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        /** @var \Cake\Console\ConsoleIo|null */
        $io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->io = $io;
        $this->ModuleTask = new ModuleTask($this->io);
        $this->useCommandRunner();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ModuleTask);

        parent::tearDown();
    }

    /**
     * Test name method
     *
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('module', $this->ModuleTask->name());
    }

    /**
     * Test fileName method
     *
     * @return void
     */
    public function testFileName(): void
    {
        $this->assertEquals('ThingsModule.php', $this->ModuleTask->fileName('Things'));
    }

    /**
     * Test template method
     *
     * @return void
     */
    public function testTemplate(): void
    {
        $this->assertEquals('Qobo/Utils.module', $this->ModuleTask->template());
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain(): void
    {
        $path = TESTS . 'data' . DS . 'Modules' . DS;
        $this->exec('generate_modules module Foo -f -n "Qobo\Utils\Test\App\Module" --module-path=' . $path);
        $this->assertOutputContains('<success>');
        $this->assertFileExists(TESTS . 'App' . DS . 'Module' . DS . 'FooModule.php');
    }
}
