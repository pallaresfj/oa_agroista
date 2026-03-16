<?php

it('renders institutional sso login page', function () {
    $this->withoutVite();

    $response = $this->get('/app/login');

    $response->assertStatus(200);
    $response->assertSee('Acceso Institucional');
    $response->assertSee('/sso/login');
    $response->assertDontSee('name="data.email"', false);
    $response->assertDontSee('name="data.password"', false);
});
