<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * Utilities::getSchemaFilePath() is the guard between a migration name from
 * the admin page and a file the app reads and runs as SQL.
 */
class SchemaFilePathTest extends Unit
{
    private function appPath(): string
    {
        return dirname(__DIR__, 2);
    }

    public function testRejectsEmptyVersion(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be empty');
        \Utilities::getSchemaFilePath($this->appPath(), '');
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('traversal not allowed');
        \Utilities::getSchemaFilePath($this->appPath(), '../00_bedrock/create_users.sql');
    }

    public function testRejectsNameWithoutNumberPrefix(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid migration path format');
        \Utilities::getSchemaFilePath($this->appPath(), 'ab_bedrock/create_users.sql');
    }

    public function testRejectsFileNotNamedCreate(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid migration path format');
        \Utilities::getSchemaFilePath($this->appPath(), '04_stop_words/modify_dream_keywords_add_stop_word_flag.sql');
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('escapes base directory');
        \Utilities::getSchemaFilePath($this->appPath(), '00_bedrock/create_nothing_here.sql');
    }

    public function testResolvesRealMigration(): void
    {
        $result = \Utilities::getSchemaFilePath($this->appPath(), '02_dreams/create_dreams.sql');

        $this->assertSame(realpath($this->appPath() . '/db_schemas/02_dreams/create_dreams.sql'), $result);
    }

    public function testRandomStringUsesOnlyGivenCharacters(): void
    {
        $result = \Utilities::randomString(40, 'ab');

        $this->assertSame(40, strlen($result));
        $this->assertMatchesRegularExpression('/^[ab]+$/', $result);
    }
}
