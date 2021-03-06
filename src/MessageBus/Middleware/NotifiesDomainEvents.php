<?php

declare (strict_types = 1);

namespace HMLB\DDDBundle\MessageBus\Middleware;

use HMLB\DDDBundle\EventListener\CollectsEventsFromEntities;
use Psr\Log\LoggerInterface;
use SimpleBus\Message\Bus\MessageBus;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;

/**
 * NotifiesDomainEvents.
 *
 * @author Hugues Maignol <hugues@hmlb.fr>
 */
class NotifiesDomainEvents implements MessageBusMiddleware
{
    /**
     * @var MessageBus
     */
    private $eventBus;
    /**
     * @var CollectsEventsFromEntities
     */
    private $collectsEventsFromEntities;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface            $logger
     * @param MessageBus                 $eventBus
     * @param CollectsEventsFromEntities $collectsEventsFromEntities
     */
    public function __construct(
        LoggerInterface $logger,
        MessageBus $eventBus,
        CollectsEventsFromEntities $collectsEventsFromEntities
    ) {
        $this->logger = $logger;
        $this->eventBus = $eventBus;
        $this->collectsEventsFromEntities = $collectsEventsFromEntities;
    }

    /**
     * @param object   $message
     * @param callable $next
     */
    public function handle($message, callable $next)
    {
        $this->logger->debug('NotifiesDomainEvents calls next before collect events');
        $next($message);
        $events = $this->collectsEventsFromEntities->recordedMessages();
        $this->collectsEventsFromEntities->eraseMessages();

        $this->logger->debug('NotifiesDomainEvents notifies '.count($events).' events');
        foreach ($events as $event) {
            $this->eventBus->handle($event);
        }
    }
}
