<?php
namespace Qobo\Utils\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Qobo\Utils\Utility\User;

class FootprintBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'created_by' => 'created_by',
        'modified_by' => 'modified_by',
        'callback' => [User::class, 'getCurrentUser'],
    ];

    /**
     * Initialize method.
     *
     * @param array $config Behavior configuration
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * BeforeSave callback method.
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Cake\Datasource\EntityInterface $entity Entity instance
     * @param \ArrayObject $options Query options
     * @return void
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (! is_callable($this->getConfig('callback'))) {
            return;
        }

        $user = call_user_func($this->getConfig('callback'));
        if (empty($user['id'])) {
            return;
        }

        // Set created_by only if that field is not set during entity creation
        if ($entity->isNew() && empty($entity->get($this->getConfig('created_by')))) {
            $entity->set($this->getConfig('created_by'), $user['id']);
        }

        // Set modified_by if that field is not set during update
        $userId = $entity->isDirty($this->getConfig('modified_by')) && !empty($this->getConfig('modified_by')) ? $entity->get($this->getConfig('modified_by')) : $user['id'];
        $entity->set($this->getConfig('modified_by'), $userId);
    }
}
