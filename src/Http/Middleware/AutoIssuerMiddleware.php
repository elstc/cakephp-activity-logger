<?php
declare(strict_types=1);

namespace Elastic\ActivityLogger\Http\Middleware;

use Authentication\IdentityInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Elastic\ActivityLogger\Lib\AutoIssuerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AutoIssuer middleware
 *
 * Get authentication information from the Authentication plugin and set it to each Table as Issuer.
 *
 * Config:
 *  'userModel': Set Identifiers 'userModel'.
 *  'identityAttribute': The request attribute used to store the identity.
 */
class AutoIssuerMiddleware implements MiddlewareInterface, EventListenerInterface
{
    use AutoIssuerTrait;
    use InstanceConfigTrait;

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
     * Constructor
     *
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        EventManager::instance()->on($this);
    }

    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
            'Model.initialize' => 'onInitializeModel',
        ];
    }

    /**
     * Process an incoming server request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get a logged-in user from the request identity attribute
        $identity = $request->getAttribute($this->getConfig('identityAttribute'));
        if ($identity instanceof IdentityInterface) {
            $this->issuer = $this->getIssuerFromUserArray($identity->getOriginalData());
        }

        if ($this->issuer) {
            $this->tables = $this->getInitializedTables();

            // register issuer to the model
            $this->setIssuerToAllModel($this->issuer);
        }

        return $handler->handle($request);
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
