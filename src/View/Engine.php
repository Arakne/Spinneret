<?php

namespace Arakne\Spinneret\View;

use LogicException;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;
use function var_dump;

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
        private ?TranslatorInterface $translator,
        private ?ViewLocaleResolverInterface $localeResolver,
        private ?ViewThemeResolverInterface $themeResolver,

        /**
         * Renderers map
         *
         * The key is the response DTO class name, the value is the renderer class name.
         * The renderer class must implement interface {@see ViewRendererInterface} or {@see ResponseConfiguratorInterface} (or both).
         *
         * @var array<class-string, class-string<ViewRendererInterface|ResponseConfiguratorInterface>>
         */
        private array $renderers,

        /**
         * Renderers maps by theme id
         * Will be used to override the default renderer if a theme is resolved.
         *
         * @var array<string, array<class-string, class-string<ViewRendererInterface|ResponseConfiguratorInterface>>>
         */
        private array $themeRenderers = [],
    ) {}

    #[Override]
    public function response(object $data, ?ServerRequestInterface $psrRequest = null, ?object $routedRequest = null): ResponseInterface
    {
        $view = new View(
            $this,
            $data,
            $psrRequest,
            $routedRequest,
            $this->translator,
            $this->localeResolver?->resolve($data, $psrRequest),
            $theme = $this->themeResolver?->resolveThemeId($data, $psrRequest),
        );
        $renderer = $this->renderer($data, $theme);

        $response = $this->responseFactory->createResponse();

        if ($renderer instanceof ViewRendererInterface) {
            $content = $renderer->render($view, $data);
            $view->content = $content;

            if ($parent = $view->parent()) {
                $content = $this->render($parent, $view);
            }

            $response = $response->withBody($this->streamFactory->createStream($content));
        }

        if ($renderer instanceof ResponseConfiguratorInterface) {
            $response = $renderer->configureResponse($view, $data, $response);
        }

        return $response;
    }

    #[Override]
    public function render(object $data, ?View $view = null): string
    {
        $view ??= new View(
            $this,
            $data,
            translator: $this->translator,
            locale: $this->localeResolver?->resolve($data, null),
            theme: $this->themeResolver?->resolveThemeId($data, null),
        );
        $renderer = $this->renderer($data, $view->theme);

        if (!($renderer instanceof ViewRendererInterface)) {
            throw new LogicException(sprintf('View %s cannot be rendered', $data::class));
        }

        return $renderer->render($view, $data);
    }

    #[Override]
    public function display(object $data, ?View $view = null): void
    {
        $view ??= new View(
            $this,
            $data,
            translator: $this->translator,
            locale: $this->localeResolver?->resolve($data, null),
            theme: $this->themeResolver?->resolveThemeId($data, null),
        );
        $renderer = $this->renderer($data, $view->theme);

        if (!($renderer instanceof ViewRendererInterface)) {
            throw new LogicException(sprintf('View %s cannot be rendered', $data::class));
        }

        $renderer->display($view, $data);
    }

    /**
     * Resolve the renderer object for the given data object
     *
     * @param D $data
     * @param string|null $theme The theme ID to use for renderer resolution. If null, the default renderer will be used.
     * @return ViewRendererInterface<D>|ResponseConfiguratorInterface<D>
     *
     * @template D as object
     */
    private function renderer(object $data, ?string $theme): ViewRendererInterface|ResponseConfiguratorInterface
    {
        if ($theme !== null) {
            $rendererClassName = $this->themeRenderers[$theme][$data::class] ?? $this->renderers[$data::class] ?? null;
        } else {
            $rendererClassName = $this->renderers[$data::class] ?? null;
        }

        if ($rendererClassName === null) {
            throw new RuntimeException('No renderer found for ' . $data::class);
        }

        /** @var ViewRendererInterface<D>|ResponseConfiguratorInterface<D> */
        return $this->container->get($rendererClassName);
    }
}
