<?php

namespace Arakne\Tests\Spinneret\Application\Config\Fixtures;

final readonly class PersonsConfig
{
    /**
     * @var PersonConfig[]
     */
    public array $persons;

    public function __construct(PersonConfig ...$persons)
    {
        $this->persons = $persons;
    }

    public function withPerson(PersonConfig $person): self
    {
        $persons = $this->persons;
        $persons[] = $person;

        return new self(...$persons);
    }
}
