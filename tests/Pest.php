<?php

uses(Tests\TestCase::class)->in(__DIR__);

function testThemePath()
{
    return realpath(__DIR__.'/_fixtures/themes/tests-theme');
}
