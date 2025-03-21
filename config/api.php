<?php

return [
    'access_token_expiration'  => (int) env( 'ACCESS_TOKEN_EXPIRATION_TIME', 60),
    'refresh_token_expiration' => (int) env( 'REFRESH_TOKEN_EXPIRATION_TIME', 60 * 24 * 30),
    'reAuth_token_expiration'  => (int) env( 'REAUTH_TOKEN_EXPIRATION_TIME', 5),
];