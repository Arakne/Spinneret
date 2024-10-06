<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

use Quatrevieux\Form\Transformer\Field\Trim;
use Quatrevieux\Form\Validator\Constraint\Length;
use Quatrevieux\Form\Validator\Constraint\Regex;

final class DownloadRequest
{
    public int $seed;
    public string $filename;
    public int $size;
}
