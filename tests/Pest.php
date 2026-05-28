<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        $this->withoutVite();
    })
    ->in('Feature');
