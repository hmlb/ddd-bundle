<?php

declare (strict_types = 1);

namespace HMLB\DDDBundle\MessageBus\Middleware;

use Doctrine\Common\Persistence\ObjectManager;
use HMLB\DDD\Message\Message;
use Psr\Log\LoggerInterface;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use SimpleBus\Message\Name\NamedMessage;

/**
 * Commits Object Manager transaction for persisted domain models.
 *
 * @author Hugues Maignol <hugues@hmlb.fr>
 */
class CommitsObjectManagerTransaction implements MessageBusMiddleware
{
    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, ObjectManager $om)
    {
        $this->logger = $logger;
        $this->om = $om;
    }

    /**
     * @param Message  $message
     * @param callable $next
     */
    public function handle($message, callable $next)
    {
        $next($message);

        $this->logger->debug(
            sprintf(
                'CommitsObjectManagerTransaction flushes Object manager after handling command "%s"',
                $message instanceof NamedMessage ? $message::messageName() : get_class($message)
            )
        );

        $this->om->flush();

        $this->logger->debug(
            sprintf(
                'CommitsObjectManagerTransaction finished handling "%s"',
                $message instanceof NamedMessage ? $message::messageName() : get_class($message)
            )
        );
    }
}
