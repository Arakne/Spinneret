<?php

namespace Arakne\Tests\Spinneret\Form\Transformer;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Transformer\DurationTransformer;
use DateInterval;
use FilesystemIterator;
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

class DurationTransformerTest extends TestCase
{
    protected const GENERATED_DIR = __DIR__ . '/_tmp';

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
        $transformer = new DurationTransformer();

        $this->assertNull($transformer->transformFromHttp(null));
        $this->assertNull($transformer->transformFromHttp(''));
        $this->assertNull($transformer->transformFromHttp(42));
        $this->assertNull($transformer->transformFromHttp([]));

        $result = $transformer->transformFromHttp('P1Y');
        $this->assertInstanceOf(DateInterval::class, $result);
        $this->assertSame(1, $result->y);

        $result = $transformer->transformFromHttp('PT30S');
        $this->assertInstanceOf(DateInterval::class, $result);
        $this->assertSame(30, $result->s);

        $result = $transformer->transformFromHttp('P1Y2M3DT4H5M6S');
        $this->assertInstanceOf(DateInterval::class, $result);
        $this->assertSame(1, $result->y);
        $this->assertSame(2, $result->m);
        $this->assertSame(3, $result->d);
        $this->assertSame(4, $result->h);
        $this->assertSame(5, $result->i);
        $this->assertSame(6, $result->s);
    }

    #[Test]
    public function transformFromHttpWithInvalidStringThrows(): void
    {
        $transformer = new DurationTransformer();

        $this->expectException(\Exception::class);
        $transformer->transformFromHttp('not-a-duration');
    }

    #[Test]
    public function canThrowError(): void
    {
        $this->assertTrue((new DurationTransformer())->canThrowError());
    }

    #[Test]
    public function transformToHttpDirectly(): void
    {
        $transformer = new DurationTransformer();

        $this->assertNull($transformer->transformToHttp(null));
        $this->assertNull($transformer->transformToHttp('P1Y'));
        $this->assertNull($transformer->transformToHttp(42));

        $this->assertSame('P1Y', $transformer->transformToHttp(new DateInterval('P1Y')));
        $this->assertSame('PT30S', $transformer->transformToHttp(new DateInterval('PT30S')));
        $this->assertSame('P1Y2M3DT4H5M6S', $transformer->transformToHttp(new DateInterval('P1Y2M3DT4H5M6S')));
        $this->assertSame('P2M', $transformer->transformToHttp(new DateInterval('P2M')));
        $this->assertSame('P10D', $transformer->transformToHttp(new DateInterval('P10D')));
        $this->assertSame('PT1H', $transformer->transformToHttp(new DateInterval('PT1H')));
        $this->assertSame('PT15M', $transformer->transformToHttp(new DateInterval('PT15M')));
        $this->assertSame('P1YT2H', $transformer->transformToHttp(new DateInterval('P1YT2H')));
    }

    #[Test]
    public function transformToHttpWithAllZerosReturnsP(): void
    {
        $transformer = new DurationTransformer();

        // P0D has all fields at zero
        $this->assertSame('P', $transformer->transformToHttp(new DateInterval('P0D')));
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitValidDuration(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestDurationForm::class)
            : $this->generatedForm(TestDurationForm::class);

        $result = $form->submit(['duration' => 'P1Y']);
        $this->assertTrue($result->valid());
        $this->assertInstanceOf(DateInterval::class, $result->value()->duration);
        $this->assertSame(1, $result->value()->duration->y);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitEmptyDuration(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestDurationForm::class)
            : $this->generatedForm(TestDurationForm::class);

        $result = $form->submit(['duration' => '']);
        $this->assertTrue($result->valid());
        $this->assertNull($result->value()->duration);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitNullDuration(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestDurationForm::class)
            : $this->generatedForm(TestDurationForm::class);

        $result = $form->submit(['duration' => null]);
        $this->assertTrue($result->valid());
        $this->assertNull($result->value()->duration);
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function submitInvalidDurationProducesError(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestDurationForm::class)
            : $this->generatedForm(TestDurationForm::class);

        $result = $form->submit(['duration' => 'not-a-duration']);
        $this->assertFalse($result->valid());
        $this->assertArrayHasKey('duration', $result->errors());
    }

    #[Test, TestWith([false]), TestWith([true])]
    public function importExportRoundTrip(bool $runtime): void
    {
        $form = $runtime
            ? $this->runtimeForm(TestDurationForm::class)
            : $this->generatedForm(TestDurationForm::class);

        $dto = new TestDurationForm();
        $dto->duration = new DateInterval('P1Y2M3DT4H5M6S');

        $httpValue = $form->import($dto)->httpValue();
        $this->assertSame('P1Y2M3DT4H5M6S', $httpValue['duration']);

        $result = $form->submit($httpValue);
        $this->assertTrue($result->valid());
        $this->assertSame(1, $result->value()->duration->y);
        $this->assertSame(2, $result->value()->duration->m);
        $this->assertSame(3, $result->value()->duration->d);
        $this->assertSame(4, $result->value()->duration->h);
        $this->assertSame(5, $result->value()->duration->i);
        $this->assertSame(6, $result->value()->duration->s);
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

class TestDurationForm
{
    #[DurationTransformer]
    public ?DateInterval $duration = null;
}
