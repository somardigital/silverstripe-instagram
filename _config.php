<?php

define('INSTAGRAM_DIR', ltrim(Director::makeRelative(realpath(__DIR__)), DIRECTORY_SEPARATOR));

// Sets the cache to one day.
SS_Cache::set_cache_lifetime('InstagramPage', 86400);
