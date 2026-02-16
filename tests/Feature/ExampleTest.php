<?php

test('the application blocks anonymous dashboard access', function () {
    $response = $this->get('/');

    $response->assertUnauthorized();
});
