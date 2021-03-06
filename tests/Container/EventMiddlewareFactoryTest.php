<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/psr7-middleware for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/psr7-middleware/blob/master/LICENSE New BSD License
 */

namespace ProophTest\Psr7Middleware\Container;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\Psr7Middleware\Container\EventMiddlewareFactory;
use Prooph\Psr7Middleware\EventMiddleware;
use Prooph\Psr7Middleware\Exception\InvalidArgumentException;
use Prooph\ServiceBus\EventBus;

class EventMiddlewareFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_config_interop()
    {
        $factory = new EventMiddlewareFactory();

        self::assertInstanceOf(\Interop\Config\RequiresConfigId::class, $factory);
        self::assertInstanceOf(\Interop\Config\RequiresMandatoryOptions::class, $factory);
        self::assertInstanceOf(\Interop\Config\ProvidesDefaultOptions::class, $factory);
    }

    /**
     * @test
     */
    public function it_creates_event_middleware()
    {
        $factory = new EventMiddlewareFactory();
        $container = $this->getValidConfiguredContainer('event');

        $factory($container->reveal());
    }

    /**
     * @test
     * @expectedException \Interop\Config\Exception\MandatoryOptionNotFoundException
     */
    public function it_throws_exception_if_option_is_missing()
    {
        $factory = new EventMiddlewareFactory();
        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'middleware' => [
                    'event' => [
                    ]
                ]
            ]
        ]);

        $factory($container->reveal());
    }

    /**
     * @test
     */
    public function it_creates_event_middleware_from_static_call()
    {
        $container = $this->getValidConfiguredContainer('other_config_id');

        $factory = [EventMiddlewareFactory::class, 'other_config_id'];
        self::assertInstanceOf(EventMiddleware::class, $factory($container->reveal()));
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_without_container_on_static_call()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'The first argument must be of type Interop\Container\ContainerInterface'
        );
        EventMiddlewareFactory::other_config_id();
    }

    /**
     * @param string $configId
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getValidConfiguredContainer($configId)
    {
        $container = $this->prophesize(ContainerInterface::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'prooph' => [
                'middleware' => [
                    $configId => [
                        'message_factory' => 'custom_message_factory'
                    ]
                ]
            ]
        ]);

        $container->has('custom_message_factory')->willReturn(true);
        $container->get('custom_message_factory')->willReturn($messageFactory);
        $container->has(EventBus::class)->willReturn(true);
        $container->get(EventBus::class)->willReturn($this->prophesize(EventBus::class));

        return $container;
    }
}
