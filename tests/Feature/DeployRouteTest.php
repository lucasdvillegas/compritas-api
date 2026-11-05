<?php

use Illuminate\Support\Facades\Artisan;

test('deploy route can run without an app key', function () {
    config(['app.key' => null]);

    Artisan::shouldReceive('call')->andReturn(0);
    Artisan::shouldReceive('output')->andReturn('ok');

    $this->get('/deploy')
        ->assertOk()
        ->assertJsonPath('success', true);
});
