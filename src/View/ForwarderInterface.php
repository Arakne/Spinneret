<?php

namespace Arakne\Spinneret\View;

/**
 * Perform HMVC calls to render a view from a presenter response
 */
interface ForwarderInterface
{
    /**
     * Call the presenter of the given request and directly render the view on the output
     *
     * The response object will be directly passed to {@see ViewEngineInterface::display()}.
     * No modification are required on the presenter implementation to supports HMVC calls, only view renderer
     * should check if the view has already a parent (check {@see View::parent()}) to avoid setting the layout on the sub-view.
     *
     * Note: The router will not be called, so the request will not be validated, and form will not be created.
     *       So be sure to perform all necessary checks before calling this method.
     *
     * @param object $request
     * @param View $view
     */
    public function display(object $request, View $view): void;

    /**
     * Call the presenter of the given request and directly render the view as string
     *
     * The response object will be directly passed to {@see ViewEngineInterface::render()}.
     * No modification are required on the presenter implementation to supports HMVC calls, only view renderer
     * should check if the view has already a parent (check {@see View::parent()}) to avoid setting the layout on the sub-view.
     *
     * Note: The router will not be called, so the request will not be validated, and form will not be created.
     *       So be sure to perform all necessary checks before calling this method.
     *
     * @param object $request
     * @param View $view
     *
     * @return string
     */
    public function render(object $request, View $view): string;
}
