<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Override;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * @implements PresenterInterface<DownloadRequest>
 */
final class DownloadPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): DownloadResponse
    {
        $random = new Randomizer(new Xoshiro256StarStar($request->seed));
        $content = $random->getBytes($request->size);

        return new DownloadResponse($request->filename, $content);
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        var_dump($request, $routedRequest->form->errors());
    }
}
