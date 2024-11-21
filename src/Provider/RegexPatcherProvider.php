<?php

namespace lexisother\Composer\Provider;

use lexisother\Composer\Patcher\RegexPatcher;
use cweagans\Composer\Capability\Patcher\BasePatcherProvider;

class RegexPatcherProvider extends BasePatcherProvider
{

    public function getPatchers(): array
    {
        return [
            new RegexPatcher($this->composer, $this->io, $this->plugin)
        ];
    }
}
