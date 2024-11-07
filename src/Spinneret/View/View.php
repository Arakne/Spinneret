<?php

namespace Arakne\Spinneret\View;

use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function htmlentities;

/**
 * Store the context for the view rendering
 */
final class View
{
    /**
     * Translator to use when no translator is provided and {@see View::_()} is called
     */
    private static ?TranslatorInterface $fallbackTranslator = null;

    /**
     * Define the parent view (i.e. layout)
     * Should be set by the renderer
     *
     * Multiple inheritance is not supported
     */
    private ?object $parent = null;

    /**
     * The original renderer view
     * Should be used by the layout renderer to render the content
     */
    public ?string $content = null;

    public function __construct(
        /**
         * The view engine which render the view
         */
        private readonly ViewEngineInterface $engine,

        /**
         * The rendering response object
         */
        public readonly object $data,

        /**
         * The current PSR-7 server request
         *
         * Can be null if the view is not rendered in an HTTP context,
         * or if it's manually called.
         */
        public readonly ?ServerRequestInterface $psrRequest = null,

        /**
         * The request object parsed by the router
         */
        public readonly ?object $routedRequest = null,

        public readonly ?TranslatorInterface $translator = null,
        public readonly ?string $locale = null,
    ) {
    }

    /**
     * Define the parent view (i.e. layout)
     * Should be set by the renderer
     *
     * Multiple inheritance is not supported
     *
     * If the parent is already set, an exception is thrown
     */
    public function extends(object $layout): void
    {
        if ($this->parent) {
            throw new LogicException('Parent view is already set');
        }

        $this->parent = $layout;
    }

    /**
     * Get the parent (i.e. layout) view
     *
     * @return object|null The parent view or null if not set
     */
    public function parent(): ?object
    {
        return $this->parent;
    }

    /**
     * Display a component view
     *
     * The rendered content will be directly written to the output
     *
     * @param object $data The component data
     * @return void
     *
     * @see View::render() To render as string instead of display
     */
    public function display(object $data): void
    {
        $this->engine->display($data, $this);
    }

    /**
     * Render a component view to a string
     *
     * @param object $data The component data
     * @return string The rendered content
     *
     * @see View::display() To display directly to the output
     */
    public function render(object $data): string
    {
        return $this->engine->render($data, $this);
    }

    /**
     * Translate the given message to the current locale
     *
     * @param string|TranslatableInterface|null $message The message pattern, or a translatable object. If null, an empty string is returned.
     * @param array $parameters The parameters to replace in the message. Use {key} or %key% syntax in the message
     *
     * @return string
     *
     * @see View::$translator to define the translator to use
     * @see View::$locale to define the locale to use
     */
    public function _(string|TranslatableInterface|null $message, array $parameters = []): string
    {
        if ($message === null) {
            return '';
        }

        $translator = $this->translator ?? (self::$fallbackTranslator ??= new IdentityTranslator());

        if ($message instanceof TranslatableInterface) {
            return $message->trans($translator, $this->locale);
        }

        return $translator->trans($message, $parameters, locale: $this->locale);
    }

    /**
     * Translate the given message to the current locale and escape HTML entities
     * This is a shortcut for `htmlentities($this->_($message, $parameters))`
     *
     * @param string|TranslatableInterface|null $message The message pattern, or a translatable object. If null, an empty string is returned.
     * @param array $parameters The parameters to replace in the message. Use {key} or %key% syntax in the message
     *
     * @return string
     *
     * @see View::$translator to define the translator to use
     * @see View::$locale to define the locale to use
     */
    public function e_(string|TranslatableInterface|null $message, array $parameters = []): string
    {
        return htmlentities($this->_($message, $parameters));
    }
}
