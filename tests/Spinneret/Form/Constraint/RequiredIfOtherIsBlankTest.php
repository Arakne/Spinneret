<?php

namespace Arakne\Tests\Spinneret\Form\Constraint;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Constraint\RequiredIfOtherIsBlank;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\DummyTranslator;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use Quatrevieux\Form\Util\Functions;
use Quatrevieux\Form\Validator\FieldError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Translation\IdentityTranslator;

use function is_dir;
use function rmdir;
use function unlink;

class RequiredIfOtherIsBlankTest extends TestCase
{
    protected const GENERATED_DIR = __DIR__ . '/_tmp';

    protected FormFactoryInterface $runtimeFormFactory;
    protected FormFactoryInterface $generatedFormFactory;
    protected ContainerInterface $container;
    protected IdentityTranslator $translator;
    protected ContainerRegistry $registry;

    protected function setUp(): void
    {
        $this->translator = new IdentityTranslator();
        $this->container = new ContainerBuilder()->build();
        $this->registry = new ContainerRegistry($this->container);

        $this->runtimeFormFactory = DefaultFormFactory::runtime($this->registry);
        $this->generatedFormFactory = DefaultFormFactory::generated(
            registry: new ContainerRegistry($this->container),
            savePathResolver: Functions::savePathResolver(self::GENERATED_DIR),
        );
    }

    protected function tearDown(): void
    {
        if (!is_dir(self::GENERATED_DIR)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::GENERATED_DIR, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $fileinfo */
        foreach($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        if (is_dir(self::GENERATED_DIR)) {
            rmdir(self::GENERATED_DIR);
        }
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function notExclusive(bool $runtime)
    {
        $form = $runtime ? $this->runtimeForm(TestRequiredIfOtherIsBlank::class) : $this->generatedForm(TestRequiredIfOtherIsBlank::class);

        $this->assertFalse($form->submit(['field' => null, 'other' => null])->valid());
        $this->assertFalse($form->submit(['field' => '', 'other' => null])->valid());
        $this->assertFalse($form->submit(['field' => null, 'other' => ''])->valid());
        $this->assertFalse($form->submit(['field' => '', 'other' => ''])->valid());
        $this->assertEquals(new FieldError('This field is required if {{ field }} is not provided.', ['field' => 'other'], RequiredIfOtherIsBlank::CODE, new DummyTranslator()), $form->submit(['field' => null, 'other' => null])->errors()['field']);

        $this->assertTrue($form->submit(['field' => 'value', 'other' => null])->valid());
        $this->assertTrue($form->submit(['field' => 'value', 'other' => 'value'])->valid());
        $this->assertTrue($form->submit(['field' => null, 'other' => 'value'])->valid());
        $this->assertTrue($form->submit(['field' => '', 'other' => 'value'])->valid());
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function exclusive(bool $runtime)
    {
        $form = $runtime ? $this->runtimeForm(TestRequiredIfOtherIsBlankExclusive::class) : $this->generatedForm(TestRequiredIfOtherIsBlankExclusive::class);

        $this->assertFalse($form->submit(['field' => null, 'other' => null])->valid());
        $this->assertFalse($form->submit(['field' => '', 'other' => null])->valid());
        $this->assertFalse($form->submit(['field' => null, 'other' => ''])->valid());
        $this->assertFalse($form->submit(['field' => '', 'other' => ''])->valid());
        $this->assertFalse($form->submit(['field' => 'value', 'other' => 'value'])->valid());
        $this->assertEquals(new FieldError('This field must be provided if {{ field }} is not provided.', ['field' => 'other'], RequiredIfOtherIsBlank::CODE, new DummyTranslator()), $form->submit(['field' => null, 'other' => null])->errors()['field']);

        $this->assertTrue($form->submit(['field' => 'value', 'other' => null])->valid());
        $this->assertTrue($form->submit(['field' => null, 'other' => 'value'])->valid());
        $this->assertTrue($form->submit(['field' => '', 'other' => 'value'])->valid());
    }

    private function runtimeForm(string $dataClass): FormInterface
    {
        return $this->runtimeFormFactory->create($dataClass);
    }

    private function generatedForm(string $dataClass): FormInterface
    {
        // Ensure that generated classes will be used
        $this->generatedFormFactory->create($dataClass);

        return $this->generatedFormFactory->create($dataClass);
    }
}

class TestRequiredIfOtherIsBlank
{
    #[RequiredIfOtherIsBlank('other')]
    public ?string $field = null;

    public ?string $other = null;
}

class TestRequiredIfOtherIsBlankExclusive
{
    #[RequiredIfOtherIsBlank('other', exclusive: true)]
    public ?string $field = null;

    public ?string $other = null;
}
