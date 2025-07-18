<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

use Arakne\Spinneret\Application\AbstractConfigurableModule;
use Arakne\Spinneret\Application\Application;
use Override;

/**
 * @extends AbstractConfigurableModule<TestConfig>
 */
final class ConfigurableModule extends AbstractConfigurableModule
{
    #[Override]
    protected function configure(): void
    {
        $this->get('/config', ShowConfigRequest::class, ShowConfigPresenter::class);
        $this->renderer(ShowConfigResponse::class, ShowConfigRenderer::class);

        $this->service(Parameters::class, parameters: [
            '%app.dev%',
            '%app.project_dir%',
            '%app.log_dir%',
            '%app.cache_dir%',
            '%app.config_dir%',
        ], public: true);
    }

    #[Override]
    protected static function defaultConfiguration(Application $app): object
    {
        return new TestConfig();
    }
}
