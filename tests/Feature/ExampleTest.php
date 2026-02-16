<?php

test('the application redirects anonymous users to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
