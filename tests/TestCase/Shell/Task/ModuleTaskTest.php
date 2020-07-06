<?php
namespace Qobo\Utils\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestCase;
use Cake\Utility\Hash;
use Qobo\Utils\Module\ModuleRegistry;
use Qobo\Utils\Shell\Task\ModuleTask;
use Qobo\Utils\Test\App\Module\FooModule;
use Qobo\Utils\Test\App\SampleModuleDecorator;
use Webmozart\Assert\Assert;

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
        $this->assumeDecoratorConfigured();

        $path = TESTS . 'data' . DS . 'Modules' . DS;
        $this->exec('generate_modules module Foo -f --module-path=' . $path);
        $this->assertOutputContains('<success>');
        $this->assertFileExists(TESTS . 'App' . DS . 'Module' . DS . 'FooModule.php');

        // Test `Foobar` alias decorator
        $foobar = ModuleRegistry::getModule('Foobar', ['className' => FooModule::class]);
        $this->assertEquals('Decorated Foobar', Hash::get($foobar->getConfig(), 'table.alias'));
    }

    /**
     * Update config with SampleModuleDecorator if it doesn't exist.
     *
     * @return void
     */
    protected function assumeDecoratorConfigured(): void
    {
        $decorators = Configure::consume('Module.decorators');
        if (empty($decorators)) {
            $decorators = [];
        }
        Assert::isArray($decorators);
        if (!in_array(SampleModuleDecorator::class, $decorators)) {
            $decorators[] = SampleModuleDecorator::class;
        }

        Configure::write('Module.decorators', $decorators);
    }
}
