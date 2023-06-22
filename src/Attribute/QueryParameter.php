<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class QueryParameter
{
    public function __construct(public ?string $name = null)
    {
    }
}
