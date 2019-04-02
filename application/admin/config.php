<?php

return [
    
    'view_replace_str' => [
        '__STATIC__' => 'public/static/admin',
        '__HOST__' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/public/static/admin',
    ],


];