<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * populateJunctionTable() links each keyword in a dream to that dream, with
 * how many times the keyword occurs there (keywords_2_dreams.occurrence_count).
 */
class PopulateJunctionTableTest extends Unit
{
    public function testLinksEachKeywordOnceWithItsCount(): void
    {
        $keyword_ids = ['dragon' => 7, 'castle' => 8];
        /** @var list<string> $looked_up */
        $looked_up = [];
        /** @var list<array<int, mixed>> $links */
        $links = [];

        $dreams = $this->createMock(\PDOStatement::class);
        $dreams->method('fetch')->willReturnOnConsecutiveCalls(
            ['dream_id' => 42, 'content_clean' => 'Dragon dragon castle dragon'],
            false
        );

        $database = $this->createMock(\PDO::class);
        $database->method('prepare')->willReturnCallback(
            function (string $sql) use ($dreams, $keyword_ids, &$looked_up, &$links): \PDOStatement {
                if (str_contains($sql, 'FROM dreams')) {
                    return $dreams;
                }
                $stmt = $this->createMock(\PDOStatement::class);
                if (str_contains($sql, 'FROM dream_keywords')) {
                    $found = null;
                    $stmt->method('execute')->willReturnCallback(
                        function (array $params) use ($keyword_ids, &$looked_up, &$found): bool {
                            $keyword = $params[0];
                            $looked_up[] = $keyword;
                            $found = is_string($keyword) ? ($keyword_ids[$keyword] ?? null) : null;
                            return true;
                        }
                    );
                    $stmt->method('fetch')->willReturnCallback(
                        function () use (&$found): array|false {
                            return $found === null ? false : ['dream_keyword_id' => $found];
                        }
                    );
                    return $stmt;
                }
                $stmt->method('execute')->willReturnCallback(
                    function (array $params) use (&$links): bool {
                        $links[] = $params;
                        return true;
                    }
                );
                return $stmt;
            }
        );

        $result = (new \DreamKeywordAnalyzer($database))->populateJunctionTable();

        $this->assertSame(['dragon', 'castle'], $looked_up);
        $this->assertSame([[7, 42, 3], [8, 42, 1]], $links);
        $this->assertSame(['dreams_processed' => 1, 'links_created' => 2], $result);
    }
}
