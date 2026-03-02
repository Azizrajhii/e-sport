<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        // Align PHP timezone with MySQL (SET time_zone = '+00:00' in doctrine.yaml)
        // to prevent subtle datetime bugs in queries and comparisons.
        date_default_timezone_set('UTC');
    }
}
