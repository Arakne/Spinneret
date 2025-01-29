<?php

namespace Arakne\Spinneret\Translation;

use Arakne\Spinneret\Util\Files;
use Override;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_array;
use function is_file;
use function str_replace;

/**
 * Decorates a translator to collect missing translations and dump them into a PHP file.
 */
final class CollectorTranslator implements TranslatorInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $missing = [];

    public function __construct(
        private readonly TranslatorInterface&TranslatorBagInterface $translator,

        /**
         * List of locales to check.
         *
         * @var list<string>
         */
        private readonly array $locales,

        /**
         * The output file to dump missing translations.
         * Takes a single placeholder `{locale}` that will be replaced by the locale.
         */
        private readonly string $outputFile,
    ) {}

    #[Override]
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $translator = $this->translator;

        foreach ($this->locales as $localeToCheck) {
            if (!$translator->getCatalogue($localeToCheck)->defines($id)) {
                $this->missing[$localeToCheck][$id] = $id;
            }
        }

        return $translator->trans($id, $parameters, $domain, $locale);
    }

    #[Override]
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * Dump all missing translations into PHP files.
     */
    public function dump(): void
    {
        foreach ($this->missing as $locale => $missing) {
            $file = str_replace('{locale}', $locale, $this->outputFile);

            if (is_file($file)) {
                try {
                    /** @var mixed $oldContent */
                    $oldContent = require $file;

                    if (is_array($oldContent)) {
                        $missing += $oldContent;
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }

            Files::write($file, '<?php return ' . var_export($missing, true) . ';');
        }
    }

    public function __destruct()
    {
        $this->dump();
    }
}
