<?php

namespace Arakne\Tests\Spinneret\Form;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Form\Csrf\CsrfHelper;
use Arakne\Spinneret\Form\FormModule;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouteCollection;

class FormModuleTest extends TestCase
{
    #[Test]
    public function emptyMethods()
    {
        $routerModule = new FormModule();
        $routes = new RouteCollectionBuilder();
        $routerModule->configureRoutes($routes);

        $this->assertEquals(new RouteCollection(), $routes->routes);
    }

    #[Test]
    public function registerNotDevShouldCreateGeneratedForm()
    {
        $app = new Application(isDev: false, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);

        $routerModule = new FormModule();
        $routerModule->register($container);

        $this->assertInstanceOf(DefaultFormFactory::class, $container->get(FormFactoryInterface::class));
        $this->assertInstanceOf(CsrfHelper::class, $container->get(CsrfHelper::class));
        $this->assertInstanceOf(ContainerRegistry::class, $container->get(RegistryInterface::class));

        /** @var FormInterface $form */
        $form = $container->get(FormFactoryInterface::class)->create(SimpleForm::class);
        $p = new \ReflectionProperty(Form::class, 'transformer');
        $p->setAccessible(true);
        $this->assertSame('Arakne_Tests_Spinneret_Form_Fixtures_SimpleFormTransformer', $p->getValue($form)::class);
    }

    #[Test]
    public function registerDevShouldCreateRuntimeForm()
    {
        $app = new Application(isDev: true, env: 'test');
        $container = new ContainerBuilder();

        $container->set(Application::class, $app);

        $routerModule = new FormModule();
        $routerModule->register($container);

        $this->assertInstanceOf(DefaultFormFactory::class, $container->get(FormFactoryInterface::class));
        $this->assertInstanceOf(CsrfHelper::class, $container->get(CsrfHelper::class));
        $this->assertInstanceOf(ContainerRegistry::class, $container->get(RegistryInterface::class));

        /** @var FormInterface $form */
        $form = $container->get(FormFactoryInterface::class)->create(SimpleForm::class);
        $p = new \ReflectionProperty(Form::class, 'transformer');
        $p->setAccessible(true);
        $this->assertSame(RuntimeFormTransformer::class, $p->getValue($form)::class);
    }
}
