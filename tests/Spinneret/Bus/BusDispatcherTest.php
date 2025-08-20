<?php

namespace Arakne\Tests\Spinneret\Bus;

use Arakne\Spinneret\Bus\BusDispatcher;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Router\Result\NotFound;
use Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommand;
use Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommandHandler;
use Arakne\Tests\Spinneret\Bus\Fixtures\FooCommand;
use Arakne\Tests\Spinneret\Bus\Fixtures\FooCommandHandler;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObjectInternal;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class BusDispatcherTest extends TestCase
{
    private ContainerInterface $container;
    private LoggerInterface&MockObjectInternal $logger;
    private BusDispatcher $dispatcher;

    protected function setUp(): void
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set(FooCommandHandler::class, new FooCommandHandler());
        $container->set(ErrorCommandHandler::class, new ErrorCommandHandler());
        $this->container = $container->build();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = new BusDispatcher(
            $this->container,
            [
                FooCommand::class => FooCommandHandler::class,
                ErrorCommand::class => ErrorCommandHandler::class,
            ],
            $this->logger,
        );
    }

    #[Test]
    public function dispatchSuccess()
    {
        $command = new FooCommand(42);
        $this->logger->expects($this->once())->method('debug')->with(
            "Dispatching message Arakne\Tests\Spinneret\Bus\Fixtures\FooCommand to handler Arakne\Tests\Spinneret\Bus\Fixtures\FooCommandHandler",
            ['message' => $command],
        );
        $this->dispatcher->dispatch($command);

        $this->assertSame($command, $this->container->get(FooCommandHandler::class)->lastCommand);
    }

    #[Test]
    public function dispatchHandlerError()
    {
        $command = new ErrorCommand();

        $this->logger->expects($this->once())->method('debug')->with(
            "Dispatching message Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommand to handler Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommandHandler",
            ['message' => $command],
        );

        $this->logger->expects($this->once())->method('error')->with(
            $this->stringContains("Error while dispatching message Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommand to handler Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommandHandler : DomainException"),
            $this->callback(fn ($value) => is_array($value) && $value['message'] === $command && $value['exception'] instanceof \DomainException),
        );

        $this->dispatcher->dispatch($command);
    }

    #[Test, DoesNotPerformAssertions]
    public function dispatchHandlerErrorWithoutLogger()
    {
        $this->dispatcher = new BusDispatcher(
            $this->container,
            [
                FooCommand::class => FooCommandHandler::class,
                ErrorCommand::class => ErrorCommandHandler::class,
            ],
        );
        $command = new ErrorCommand();

        $this->dispatcher->dispatch($command);
    }

    #[Test]
    public function dispatchHandlerNotFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No handler for message Arakne\Spinneret\Router\Result\NotFound");
        $command = new NotFound();

        $this->logger->expects($this->never())->method('debug');
        $this->dispatcher->dispatch($command);
    }

    #[Test]
    public function processSuccess()
    {
        $command = new FooCommand(42);
        $this->logger->expects($this->exactly(2))->method('debug');

        $ret = $this->dispatcher->process($command, fn ($value) => $value + 5);

        $this->assertSame($command, $this->container->get(FooCommandHandler::class)->lastCommand);
        $this->assertSame(89, $ret);
    }

    #[Test]
    public function processHandlerError()
    {
        $command = new ErrorCommand();
        $this->logger->expects($this->once())->method('debug')->with(
            "Dispatching message Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommand to handler Arakne\Tests\Spinneret\Bus\Fixtures\ErrorCommandHandler",
            ['message' => $command],
        );

        try {
            $this->dispatcher->process($command, fn ($value) => $value + 5);
            $this->fail('An exception should have been thrown');
        } catch (\DomainException $e) {
            $this->assertEquals('Error', $e->getMessage());
        }
    }

    #[Test]
    public function processHandlerNotFound()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No handler for message Arakne\Spinneret\Router\Result\NotFound");
        $command = new NotFound();

        $this->logger->expects($this->never())->method('debug');
        $this->dispatcher->process($command, fn () => null);
    }
}
