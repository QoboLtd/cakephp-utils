<?php
namespace Qobo\Utils\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use Qobo\Utils\Model\Behavior\EncryptedFieldsBehavior;
use Qobo\Utils\Test\App\Model\Table\UsersTable;
use RuntimeException;
use Webmozart\Assert\Assert;

/**
 * Qobo\Utils\Model\Behavior\EncryptedFieldsBehavior Test Case
 */
class EncryptedFieldsBehaviorTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Qobo/Utils.Users',
    ];

    /**
     * Test subject
     *
     * @var \Qobo\Utils\Model\Behavior\EncryptedFieldsBehavior
     */
    public $EncryptedFields;

    /**
     * Test table
     *
     * @var \Qobo\Utils\Test\App\Model\Table\UsersTable
     */
    public $Users;

    /**
     * Encryption key
     *
     * @var string
     */
    protected $key;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->key = Configure::readOrFail('Qobo/Utils.encryptionKey');
        $table = TableRegistry::getTableLocator()->get('Users');
        Assert::isInstanceOf($table, UsersTable::class);

        $this->Users = $table;
        $this->Users->setTable('users');

        $config = [
            'encryptionKey' => $this->key,
            'fields' => [
                'name' => [
                    'decrypt' => true,
                ],
            ],
        ];
        $this->EncryptedFields = new EncryptedFieldsBehavior($this->Users, $config);
        $this->Users->addBehavior('Qobo/Utils.EncryptedFields', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->EncryptedFields);
        unset($this->key);
        unset($this->Users);

        parent::tearDown();
    }

    /**
     * Test encryption key validation during initialization
     *
     * @return void
     */
    public function testInitializeEncryptionKeyValidation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->EncryptedFields->setConfig(['encryptionKey' => '']);
        $this->EncryptedFields->initialize([]);
    }

    /**
     * Test `enabled` validation during initialization
     *
     * @return void
     */
    public function testInitializeEnabledValidationInvalidType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->EncryptedFields->initialize(['enabled' => 'foobar']);
    }

    /**
     * Test `enabled` callback fails when non-boolean value is returned.
     *
     * @return void
     */
    public function testInitializeEnabledCallbackReturnedNonBoolean(): void
    {
        $this->expectException(RuntimeException::class);
        $this->EncryptedFields->initialize(['enabled' => function () {
            return null;
        }]);
        $entity = $this->Users->newEntity([]);
        $this->EncryptedFields->isEncryptable($entity);
    }

    /**
     * Test `enabled` validation during initialization
     *
     * @return void
     */
    public function testInitializeEnabledValidationValidType(): void
    {
        $this->EncryptedFields->initialize(['enabled' => false]);
        $this->assertFalse($this->EncryptedFields->getConfig('enabled'));

        $this->EncryptedFields->setConfig(['enabled' => function () {
            return false;
        }]);
        $this->EncryptedFields->initialize([]);
        $this->assertTrue(is_callable($this->EncryptedFields->getConfig('enabled')));
    }

    /**
     * Test no encryption is done if behavior is disabled.
     *
     * @return void
     */
    public function testEncryptionDisabled(): void
    {
        $this->EncryptedFields->setConfig([
            'enabled' => false,
        ]);
        $this->EncryptedFields->initialize([]);

        $name = 'foobar';
        $entity = $this->Users->newEntity([
            'name' => $name,
        ]);
        $expected = clone $entity;
        $actualEntity = $this->EncryptedFields->encryptEntity($entity);
        $this->assertEquals($expected, $actualEntity);
        $this->assertEquals($name, $actualEntity->get('name'));
    }

    /**
     * Test success field encryption
     *
     * @return void
     */
    public function testEncryptSuccess(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity([
            'name' => $name,
        ]);

        // Assert name was changed
        $actualEntity = $this->EncryptedFields->encryptEntity($entity);
        $this->assertTrue($actualEntity->isDirty('name'));
        $this->assertNotEquals($name, $actualEntity->get('name'));
    }

    /**
     * Test missing fields are skipped
     *
     * @return void
     */
    public function testEncryptMissingFieldsAreSkipped(): void
    {
        $this->EncryptedFields->setConfig(['fields' => [
                'invalid_field' => [
                    'decrypt' => true,
                ],
            ],
        ]);

        $entity = $this->Users->newEntity();
        $expected = clone $entity;
        $actualEntity = $this->EncryptedFields->encryptEntity($entity);
        $this->assertEquals($expected, $actualEntity);
    }

    /**
     * Test decrypt entity.
     *
     * @return void
     */
    public function testDecryptEntity(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);

        $decrypted = $this->EncryptedFields->decryptEntity($encrypted, ['name']);
        $this->assertEquals($name, $decrypted->get('name'));
    }

    /**
     * Test decryption skips missing fields
     *
     * @return void
     */
    public function testDecryptMissingFieldsAreSkipped(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);
        $decrypted = $this->EncryptedFields->decryptEntity($encrypted, ['missing_field']);
        // No errors should be produced, and `name` should still be encrypted
        $decodedName = (string)base64_decode($decrypted->get('name'));
        $decryptedName = Security::decrypt($decodedName, $this->key);
        $this->assertEquals($name, $decryptedName);
    }

    /**
     * Test decryption failure
     *
     * @return void
     */
    public function testDecryptFailure(): void
    {
        $this->expectException(RuntimeException::class);

        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);

        $this->EncryptedFields->setConfig([
            // Has to be long otherwirse Security class will throw an exception.
            'encryptionKey' => 'badkeybadkeybadkeybadkeybadkeybadkeybadkeybadkey',
        ]);

        try {
            $decrypted = $this->EncryptedFields->decryptEntity($encrypted, ['name']);
        } catch (RuntimeException $e) {
            $this->assertContains('Unable to decypher `name`', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Test decryption of entity return early if disabled.
     *
     * @return void
     */
    public function testDecryptEntityWhenDisabled(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $this->EncryptedFields->encryptEntity($entity);
        $encrypted = clone $entity;

        $this->EncryptedFields->setConfig(['enabled' => false]);
        $decrypted = $this->EncryptedFields->decryptEntity($encrypted, ['name']);
        $this->assertEquals($entity, $decrypted);
    }

    /**
     * Test decryption of entity when fields are not allowed to be decrypted.
     *
     * @return void
     */
    public function testDecryptEntityFieldsCannotBeDecrypted(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);

        $this->EncryptedFields->setConfig(
            [
                'decryptAll' => false,
                'fields' => [
                    'name' => [
                        'decrypt' => function () {
                            return false;
                        },
                    ],
                ],
            ]
        );

        $decrypted = $this->EncryptedFields->decryptEntityField($encrypted, 'name');
        $this->assertNull($decrypted);

        $decrypted = $this->EncryptedFields->decryptEntityField($encrypted, 'invalid_field');
        $this->assertNull($decrypted);
    }

    /**
     * Test decryption of entity fields automatically marked as non decryptable.
     *
     * @return void
     */
    public function testDencryptEntityFieldsNonDecryptableByDefault(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);

        $this->EncryptedFields->setConfig(
            [
                'decryptAll' => false,
                'fields' => [
                    'name', // should be marked as non decryptable by default.
                ],
            ]
        );

        $decrypted = $this->EncryptedFields->decryptEntityField($encrypted, 'name');
        $this->assertNull($decrypted);
    }

    /**
     * Test custom finder method
     *
     * @return void
     */
    public function testFinder(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $this->Users->save($entity);

        $query = $this->Users->find('decrypt', ['decryptFields' => ['name']]);
        $this->assertEquals(3, $query->count());
        $users = $query->toArray();
        $this->assertEquals('user1', $users[0]->get('name'));
        $this->assertEquals('user2', $users[1]->get('name'));
        $this->assertEquals($name, $users[2]->get('name'));
    }

    /**
     * Test custom finder method when decryption is disabled.
     *
     * @return void
     */
    public function testFinderWithoutFieldsDecryptAllDisabled(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $this->Users->save($entity);

        $this->Users->getBehavior('EncryptedFields')->setConfig(
            [
                'decryptAll' => false,
                'fields' => [
                    'name' => [
                        'decrypt' => false,
                    ],
                ],
            ]
        );

        $query = $this->Users->find('decrypt');
        $this->assertEquals(3, $query->count());
        $users = $query->toArray();
        $this->assertEquals('user1', $users[0]->get('name'));
        $this->assertEquals('user2', $users[1]->get('name'));
        $this->assertNotEquals($name, $users[2]->get('name'));
    }

    /**
     * Test custom finder method when decryption is enabled.
     *
     * @return void
     */
    public function testFinderWithoutFieldsDecryptAllEnabled(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $this->Users->save($entity);

        $this->Users->getBehavior('EncryptedFields')->setConfig(
            [
                'decryptAll' => true,
                'fields' => [
                    'name' => [
                        'decrypt' => false,
                    ],
                ],
            ]
        );

        $query = $this->Users->find('decrypt');
        $this->assertEquals(3, $query->count());
        $users = $query->toArray();
        $this->assertEquals('user1', $users[0]->get('name'));
        $this->assertEquals('user2', $users[1]->get('name'));
        $this->assertEquals($name, $users[2]->get('name'));
    }

    /**
     * Test custom finder method when decryption is disabled.
     *
     * @return void
     */
    public function testEncryptWithInaccessibleField(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity([
            'name' => $name,
        ]);
        $entity->setAccess('name', false);

        // Assert name was changed
        $actualEntity = $this->EncryptedFields->encryptEntity($entity);
        $this->assertTrue($actualEntity->isDirty('name'));
        $this->assertNotEquals($name, $actualEntity->get('name'));
    }

    /**
     * Test entity decryption when you pass a valid field, but it's not present
     * in the behavior config.
     *
     * @return void
     */
    public function testDecryptWithEmptyFieldReturnsNull(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity(['name' => $name]);
        $encrypted = $this->EncryptedFields->encryptEntity($entity);

        $this->EncryptedFields->setConfig(
            [
                'decryptAll' => false,
                'fields' => [],
            ],
            null,
            false
        );

        $actual = $this->EncryptedFields->decryptEntityField($entity, 'name');
        $this->assertNull($actual);
    }

    /**
     * Test custom finder method when decryption is disabled.
     *
     * @return void
     */
    public function testDecryptExistingFieldWhichIsMissingFromConfig(): void
    {
        $name = 'foobar';
        $entity = $this->Users->newEntity([
            'name' => '',
        ]);
        $actual = $this->EncryptedFields->decryptEntityField($entity, 'name');
        $this->assertNull($actual);
    }
}
