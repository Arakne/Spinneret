<?php

namespace Arakne\Spinneret\View;

use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * Default view engine implementation
 *
 * Resolve the {@see ViewRendererInterface} from the object class, instantiate it from the container,
 * and call {@see ViewRendererInterface::render()} on it.
 *
 * The response can be configured by the renderer if it implements {@see ResponseConfiguratorInterface}.
 */
final readonly class Engine implements ViewEngineInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,

        /**
         * @var array<class-string, class-string<ViewRendererInterface>>
         */
        private array $renderers,
    ) {
    }

    #[Override]
    public function response(object $data): ResponseInterface
    {
        $view = new View($data);

        $renderer = $this->renderers[$data::class] ?? null;

        if ($renderer === null) {
            throw new RuntimeException('No renderer found for ' . $data::class);
        }

        $renderer = $this->container->get($renderer);

        // @todo handle layout : use $view->parent property
        $content = $renderer->render($view, $data);

        // @todo allow null render ? or allow ViewRendererInterface|ResponseConfiguratorInterface union type instead
        $response = $this->responseFactory->createResponse();
        $response = $response->withBody($this->streamFactory->createStream($content));

        if ($renderer instanceof ResponseConfiguratorInterface) {
            $response = $renderer->configureResponse($view, $data, $response);
        }

        return $response;
    }
}
