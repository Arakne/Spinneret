<?php

namespace Arakne\Tests\Spinneret\Form\Transformer;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Transformer\EmptyStringToNull;
use FilesystemIterator;
use Quatrevieux\Form\DefaultRegistry;
use Quatrevieux\Form\Transformer\Generator\FormTransformerGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Quatrevieux\Form\ContainerRegistry;
use Quatrevieux\Form\DefaultFormFactory;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use Quatrevieux\Form\Util\Functions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function is_dir;
use function rmdir;
use function unlink;

class EmptyStringToNullTest extends TestCase
{
    protected const GENERATED_DIR = __DIR__ . '/_tmp_empty_string';

    protected FormFactoryInterface $runtimeFormFactory;
    protected FormFactoryInterface $generatedFormFactory;
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder()->build();

        $this->runtimeFormFactory = DefaultFormFactory::runtime(
            new ContainerRegistry($this->container)
        );
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
        foreach ($files as $fileinfo) {
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

    #[Test]
    public function transformFromHttpDirectly(): void
    {
        $transformer = new EmptyStringToNull();

        // Chaîne vide → null
        $this->assertNull($transformer->transformFromHttp(''));

        // Autres valeurs passées telles quelles
        $this->assertNull($transformer->transformFromHttp(null));
        $this->assertSame('hello', $transformer->transformFromHttp('hello'));
        $this->assertSame('0', $transformer->transformFromHttp('0'));
        $this->assertSame(42, $transformer->transformFromHttp(42));
        $this->assertSame(0, $transformer->transformFromHttp(0));
        $this->assertSame(false, $transformer->transformFromHttp(false));
        $this->assertSame([], $transformer->transformFromHttp([]));
    }

    #[Test]
    public function transformToHttpDirectly(): void
    {
        $transformer = new EmptyStringToNull();

        // transformToHttp est une identité
        $this->assertNull($transformer->transformToHttp(null));
        $this->assertSame('', $transformer->transformToHttp(''));
        $this->assertSame('hello', $transformer->transformToHttp('hello'));
        $this->assertSame(42, $transformer->transformToHttp(42));
        $this->assertSame(false, $transformer->transformToHttp(false));
    }

    #[Test]
    public function canThrowError(): void
    {
        $this->assertFalse((new EmptyStringToNull())->canThrowError());
    }

    #[Test]
    public function generateTransformFromHttp(): void
    {
        $generator = new FormTransformerGenerator(new DefaultRegistry());
        $transformer = new EmptyStringToNull();

        $this->assertSame(
            '(($__tmp_eb7b5e55dd138bac2661a1ef7bafa458 = $data["field"]) === \'\' ? null : $__tmp_eb7b5e55dd138bac2661a1ef7bafa458)',
            $transformer->generateTransformFromHttp($transformer, '$data["field"]', $generator)
        );
    }

    #[Test]
    public function generateTransformToHttp(): void
    {
        $generator = new FormTransformerGenerator(new DefaultRegistry());
        $transformer = new EmptyStringToNull();

        // transformToHttp est une identité : l'expression précédente est retournée telle quelle
        $this->assertSame(
            '$data["field"]',
            $transformer->generateTransformToHttp($transformer, '$data["field"]', $generator)
        );
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitEmptyStringBecomesNull(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestEmptyStringToNullForm::class)
            : $this->generatedForm(TestEmptyStringToNullForm::class);

        $result = $form->submit(['field' => '']);
        $this->assertTrue($result->valid());
        $this->assertNull($result->value()->field);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitNonEmptyStringIsPreserved(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestEmptyStringToNullForm::class)
            : $this->generatedForm(TestEmptyStringToNullForm::class);

        $result = $form->submit(['field' => 'hello']);
        $this->assertTrue($result->valid());
        $this->assertSame('hello', $result->value()->field);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitNullIsPreserved(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestEmptyStringToNullForm::class)
            : $this->generatedForm(TestEmptyStringToNullForm::class);

        $result = $form->submit(['field' => null]);
        $this->assertTrue($result->valid());
        $this->assertNull($result->value()->field);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function importExportRoundTrip(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestEmptyStringToNullForm::class)
            : $this->generatedForm(TestEmptyStringToNullForm::class);

        $dto = new TestEmptyStringToNullForm();
        $dto->field = 'foo bar';

        $httpValue = $form->import($dto)->httpValue();
        $this->assertSame('foo bar', $httpValue['field']);

        $result = $form->submit($httpValue);
        $this->assertTrue($result->valid());
        $this->assertSame('foo bar', $result->value()->field);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function importNullFieldExportsToNull(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestEmptyStringToNullForm::class)
            : $this->generatedForm(TestEmptyStringToNullForm::class);

        $dto = new TestEmptyStringToNullForm();
        $dto->field = null;

        $httpValue = $form->import($dto)->httpValue();
        $this->assertNull($httpValue['field']);
    }

    private function runtimeForm(string $dataClass): FormInterface
    {
        return $this->runtimeFormFactory->create($dataClass);
    }

    private function generatedForm(string $dataClass): FormInterface
    {
        // Force la génération puis recharge le formulaire généré
        $this->generatedFormFactory->create($dataClass);

        return $this->generatedFormFactory->create($dataClass);
    }
}

class TestEmptyStringToNullForm
{
    #[EmptyStringToNull]
    public ?string $field = null;
}
