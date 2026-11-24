<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Database\DreamsRepository;

/**
 * DreamsRepository holds the SQL the admin pages run against `dreams`.
 * Counts and date parts are whole numbers, so every value comes back an int
 * whether PDO hands over an int (native prepares) or a string (emulated).
 */
class DreamsRepositoryTest extends Unit
{
    /** @var list<string> SQL each prepare() received, whitespace collapsed */
    private array $prepared = [];

    /**
     * A PDO whose every prepare() gives back $stmt, recording the SQL.
     */
    private function pdoReturning(\PDOStatement $stmt): \PDO
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('prepare')->willReturnCallback(
            function (string $sql) use ($stmt): \PDOStatement {
                $this->prepared[] = trim((string) preg_replace('/\s+/', ' ', $sql));
                return $stmt;
            }
        );
        return $pdo;
    }

    private function statementWithColumn(mixed $value): \PDOStatement
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn($value);
        return $stmt;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function statementWithRows(array $rows): \PDOStatement
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);
        return $stmt;
    }

    public function testCountAllRunsTheCount(): void
    {
        (new DreamsRepository($this->pdoReturning($this->statementWithColumn(5))))->countAll();

        $this->assertSame(['SELECT COUNT(*) FROM dreams'], $this->prepared);
    }

    public function testCountAllReturnsAnIntFromNativePrepares(): void
    {
        $repository = new DreamsRepository($this->pdoReturning($this->statementWithColumn(1234)));

        $this->assertSame(1234, $repository->countAll());
    }

    public function testCountAllReturnsAnIntFromEmulatedPrepares(): void
    {
        $repository = new DreamsRepository($this->pdoReturning($this->statementWithColumn('1234')));

        $this->assertSame(1234, $repository->countAll());
    }

    public function testCountAllReturnsZeroForNoDreams(): void
    {
        $repository = new DreamsRepository($this->pdoReturning($this->statementWithColumn(0)));

        $this->assertSame(0, $repository->countAll());
    }

    public function testCountAllThrowsWhenTheCountGivesNoRow(): void
    {
        $repository = new DreamsRepository($this->pdoReturning($this->statementWithColumn(false)));

        $this->expectException(\RuntimeException::class);
        $repository->countAll();
    }

    public function testCountByYearRunsItsQueryAndGivesInts(): void
    {
        $stmt = $this->statementWithRows([
            ['year' => '1985', 'count' => '3'],
            ['year' => 2025, 'count' => 40],
        ]);

        $rows = (new DreamsRepository($this->pdoReturning($stmt)))->countByYear();

        $this->assertSame(
            ['SELECT YEAR(date_created) as year, COUNT(*) as count FROM dreams'
                . ' WHERE date_created IS NOT NULL GROUP BY YEAR(date_created) ORDER BY year'],
            $this->prepared
        );
        $this->assertSame([['year' => 1985, 'count' => 3], ['year' => 2025, 'count' => 40]], $rows);
    }

    public function testCountByMonthRunsItsQueryAndGivesInts(): void
    {
        $stmt = $this->statementWithRows([['month' => '1', 'count' => '7'], ['month' => 12, 'count' => 9]]);

        $rows = (new DreamsRepository($this->pdoReturning($stmt)))->countByMonth();

        $this->assertSame(
            ['SELECT MONTH(date_created) as month, COUNT(*) as count FROM dreams'
                . ' WHERE date_created IS NOT NULL GROUP BY MONTH(date_created) ORDER BY month'],
            $this->prepared
        );
        $this->assertSame([['month' => 1, 'count' => 7], ['month' => 12, 'count' => 9]], $rows);
    }

    public function testCountByDayRunsItsQueryAndGivesInts(): void
    {
        $stmt = $this->statementWithRows([['day' => '31', 'count' => '2']]);

        $rows = (new DreamsRepository($this->pdoReturning($stmt)))->countByDay();

        $this->assertSame(
            ['SELECT DAY(date_created) as day, COUNT(*) as count FROM dreams'
                . ' WHERE date_created IS NOT NULL GROUP BY DAY(date_created) ORDER BY day'],
            $this->prepared
        );
        $this->assertSame([['day' => 31, 'count' => 2]], $rows);
    }

    public function testCountsGiveAnEmptyListWhenNoDreamHasADate(): void
    {
        $repository = new DreamsRepository($this->pdoReturning($this->statementWithRows([])));

        $this->assertSame([], $repository->countByYear());
    }
}
