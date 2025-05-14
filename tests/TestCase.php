<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Filament\Infolists\Testing\TestsInfolists;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use TestsInfolists;
}
