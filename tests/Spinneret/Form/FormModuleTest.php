<?php

namespace Arakne\Tests\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Form\FormModule;
use Arakne\Tests\Spinneret\Form\Fixtures\SimpleForm;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\Form;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use Quatrevieux\Form\RegistryInterface;
use Quatrevieux\Form\Transformer\RuntimeFormTransformer;

class FormModuleTest extends TestCase
{
    #[Test]
    public function registerNotDevShouldCreateGeneratedForm()
    {
        $app = new Application(isDev: false, env: 'test-form');
        $container = new ContainerBuilder(registerAsPublic: true);

        $routerModule = new FormModule();
        $routerModule->register($container);
        $container = $container->build();
        $container->set(Application::class, $app);

        $this->assertInstanceOf(DefaultFormFactory::class, $container->get(FormFactoryInterface::class));
        $this->assertInstanceOf(CsrfHelper::class, $container->get(CsrfHelper::class));
        $this->assertInstanceOf(ContainerRegistry::class, $container->get(RegistryInterface::class));

        /** @var FormInterface $form */
        $form = $container->get(FormFactoryInterface::class)->create(SimpleForm::class);
        $p = new \ReflectionProperty(Form::class, 'transformer');
        $this->assertSame('Arakne_Tests_Spinneret_Form_Fixtures_SimpleFormTransformer', $p->getValue($form)::class);
    }

    #[Test]
    public function registerDevShouldCreateRuntimeForm()
    {
        $app = new Application(isDev: true, env: 'test-form');
        $container = new ContainerBuilder(registerAsPublic: true);

        $routerModule = new FormModule();
        $routerModule->register($container);
        $container = $container->build();
        $container->set(Application::class, $app);

        $this->assertInstanceOf(DefaultFormFactory::class, $container->get(FormFactoryInterface::class));
        $this->assertInstanceOf(CsrfHelper::class, $container->get(CsrfHelper::class));
        $this->assertInstanceOf(ContainerRegistry::class, $container->get(RegistryInterface::class));

        /** @var FormInterface $form */
        $form = $container->get(FormFactoryInterface::class)->create(SimpleForm::class);
        $p = new \ReflectionProperty(Form::class, 'transformer');
        $this->assertSame(RuntimeFormTransformer::class, $p->getValue($form)::class);
    }
}
