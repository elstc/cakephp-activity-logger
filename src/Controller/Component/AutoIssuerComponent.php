<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Table;
use Elastic\ActivityLogger\Lib\AutoIssuerTrait;

/**
 * AutoIssuer component
 *
 * Get authentication information from the Authentication plugin (or AuthComponent) and set it to each Table as Issuer.
 *
 * Config:
 *  'userModel': Set Identifiers 'userModel'.
 *  'identityAttribute': The request attribute used to store the identity.
 *
 * @deprecated 3.3.0 Use \Elastic\ActivityLogger\Http\AutoIssuerMiddleware instead.
 */
class AutoIssuerComponent extends Component
{
    use AutoIssuerTrait;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'userModel' => 'Users',
        'identityAttribute' => 'identity',
    ];

    /**
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        return [
            ...parent::implementedEvents(),
            'Model.initialize' => 'onInitializeModel',
            'Authentication.afterIdentify' => 'onAfterIdentifyAtAuthentication',
        ];
    }

    /**
     * on Controller.startup
     *
     * @return void
     */
    public function startup(): void
    {
        // Get a logged-in user from the request identity attribute
        if (!$this->issuer) {
            $identity = $this->getController()->getRequest()
                ->getAttribute($this->getConfig('identityAttribute'));
            if ($identity) {
                $this->issuer = $this->getIssuerFromUserArray($identity->getOriginalData());
            }
        }

        if (!$this->issuer) {
            // not logged in
            return;
        }

        $this->tables = $this->getInitializedTables();

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * on Authentication.afterIdentify
     *
     * - get issuer from event data
     * - register issuer to the model
     *
     * @param \Cake\Event\Event<\Cake\Controller\Component> $event the Event
     * @return void
     * @noinspection PhpUnused
     */
    public function onAfterIdentifyAtAuthentication(Event $event): void
    {
        $identity = $event->getData('identity');
        $this->issuer = $this->getIssuerFromUserArray($identity);

        if (!$this->issuer) {
            // not logged in
            return;
        }

        $this->tables = $this->getInitializedTables();

        // register issuer to the model
        $this->setIssuerToAllModel($this->issuer);
    }

    /**
     * Get Users table class
     *
     * @return \Cake\ORM\Table
     */
    protected function getUserModel(): Table
    {
        return $this->fetchTable($this->getConfig('userModel'));
    }
}
