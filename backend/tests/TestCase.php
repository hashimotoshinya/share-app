<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // FIREBASE_CREDENTIALS をテスト用ファイルに設定
        putenv('FIREBASE_CREDENTIALS=' . __DIR__ . '/../firebase-test.json');
        parent::setUp();
    }
}
