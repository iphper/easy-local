<?php

namespace Core\Config;

function config_set(string $key, $value) : Config {
    return Config::instance()->set($key, $value);
}

function config_get(string $key, $def = null) : mixed {
    return Config::instance()->get($key, $def);
}

function config(string $key, $value = null) {
    return is_null($value) ? config_get($key) : config_set($key, $value);
}