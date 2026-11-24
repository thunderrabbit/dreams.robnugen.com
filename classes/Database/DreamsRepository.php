<?php

namespace Database;

/**
 * Queries against the `dreams` table.
 *
 * Counts and date parts are whole numbers, so they come back as ints
 * whether PDO uses native prepares (ints) or emulated ones (strings).
 */
class DreamsRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    /**
     * Count every dream in the database
     */
    public function countAll(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM dreams");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        if (!is_numeric($count)) {
            // COUNT(*) with no GROUP BY always gives one row.
            throw new \RuntimeException("Counting dreams gave no row");
        }

        return (int) $count;
    }

    /**
     * Count dated dreams per year, oldest year first
     *
     * @return list<array{year: int, count: int}>
     */
    public function countByYear(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT YEAR(date_created) as year, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY YEAR(date_created)
            ORDER BY year
        ");
        $stmt->execute();

        $counts = [];
        foreach ($this->rows($stmt) as $row) {
            $counts[] = ['year' => $this->toInt($row['year'] ?? null), 'count' => $this->toInt($row['count'] ?? null)];
        }
        return $counts;
    }

    /**
     * Count dated dreams per month of the year (across all years), January first
     *
     * @return list<array{month: int, count: int}>
     */
    public function countByMonth(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT MONTH(date_created) as month, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY MONTH(date_created)
            ORDER BY month
        ");
        $stmt->execute();

        $counts = [];
        foreach ($this->rows($stmt) as $row) {
            $counts[] = ['month' => $this->toInt($row['month'] ?? null), 'count' => $this->toInt($row['count'] ?? null)];
        }
        return $counts;
    }

    /**
     * Count dated dreams per day of the month (1-31, across all months)
     *
     * @return list<array{day: int, count: int}>
     */
    public function countByDay(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DAY(date_created) as day, COUNT(*) as count
            FROM dreams
            WHERE date_created IS NOT NULL
            GROUP BY DAY(date_created)
            ORDER BY day
        ");
        $stmt->execute();

        $counts = [];
        foreach ($this->rows($stmt) as $row) {
            $counts[] = ['day' => $this->toInt($row['day'] ?? null), 'count' => $this->toInt($row['count'] ?? null)];
        }
        return $counts;
    }

    /**
     * @return list<array<mixed>>
     */
    private function rows(\PDOStatement $stmt): array
    {
        return array_values(array_filter($stmt->fetchAll(), is_array(...)));
    }

    /**
     * A date part or count from MySQL; WHERE date_created IS NOT NULL keeps
     * them from being NULL.
     */
    private function toInt(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new \UnexpectedValueException("Expected a whole number from MySQL, got " . get_debug_type($value));
        }
        return (int) $value;
    }
}
