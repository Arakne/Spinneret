<?php

namespace Arakne\Spinneret\Router\Field;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Base type for attributes used to extract HTTP fields from PSR-7 requests
 * The attribute must be able to target properties and classes.
 */
interface RequestFieldInterface
{
    /**
     * Extract a single field from the request
     *
     * @param ServerRequestInterface $request The request to extract from
     * @param string $name The name of the field to extract
     *
     * @return mixed The field value. Must be null if the field is not present.
     */
    public function extract(ServerRequestInterface $request, string $name): mixed;

    /**
     * Extract all fields as an associative array
     *
     * If the request doesn't have values (e.g. empty body), an empty array must be returned.
     * If the request value is not an associative array (e.g. a JSON object or plain text), it must be casted to an associative array.
     *
     * @param ServerRequestInterface $request The request to extract from
     *
     * @return array<string, mixed> The extracted fields
     */
    public function extractAll(ServerRequestInterface $request): array;

    /**
     * Compile the {@see RequestFieldInterface::extract()} method as PHP expression
     *
     * This method will generate a code like : `$request->getQueryParams()['name'] ?? null`
     * When $requestVarName is `$request` and $name is `name`
     *
     * The compiled expression must behave like {@see RequestFieldInterface::extract()}.
     *
     * @param string $requestVarName The request variable name to extract from
     * @param string $name The name of the field to extract
     *
     * @return string The PHP expression
     */
    public function compileExtract(string $requestVarName, string $name): string;

    /**
     * Compile the {@see RequestFieldInterface::extractAll()} method as PHP expression
     *
     * This method will generate a code like : `$request->getQueryParams()`
     * When $requestVarName is `$request`.
     *
     * The compiled expression must behave like {@see RequestFieldInterface::extractAll()}.
     *
     * @param string $requestVarName The request variable name to extract from
     *
     * @return string The PHP expression
     */
    public function compileExtractAll(string $requestVarName): string;
}
