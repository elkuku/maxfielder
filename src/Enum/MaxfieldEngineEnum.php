<?php

declare(strict_types=1);

namespace App\Enum;

enum MaxfieldEngineEnum: string
{
    use ForSelectTrait;

    case php = 'php';
    case python = 'python';
    case docker = 'docker';
}