<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConstantsTest extends TestCase
{
    #[Test]
    public function directories_are_absolute_and_end_with_a_slash(): void {
        foreach ([LOG_DIR, TMP_DIR, TEMPLATE_DIR, UPLOAD_DIR] as $directory) {
            self::assertStringStartsWith('/', $directory);
            self::assertStringEndsWith('/', $directory);
        }
    }

    #[Test]
    public function upload_dir_is_read_from_the_environment_or_defaults_to_the_app_root(): void {
        $expected = (string) ($_ENV['UPLOAD_DIR'] ?? $_SERVER['UPLOAD_DIR'] ?? '');

        if ($expected === '') {
            self::assertSame(ROOT . 'upload/', UPLOAD_DIR);
            return;
        }

        self::assertStringEndsWith(trim($expected, '/') . '/', UPLOAD_DIR);
    }
}
