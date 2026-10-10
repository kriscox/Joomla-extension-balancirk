<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\WaitlistSettlementHelper;
use PHPUnit\Framework\TestCase;

final class WaitlistSettlementHelperTest extends TestCase
{
    public function testIsFifoPrefixAcceptsEmptySelection(): void
    {
        $this->assertTrue(WaitlistSettlementHelper::isFifoPrefix([], [1, 2, 3]));
    }

    public function testIsFifoPrefixAcceptsOldestNInAnyOrder(): void
    {
        $this->assertTrue(WaitlistSettlementHelper::isFifoPrefix([2, 1], [1, 2, 3, 4]));
        $this->assertTrue(WaitlistSettlementHelper::isFifoPrefix([1, 2, 3], [1, 2, 3, 4]));
    }

    public function testIsFifoPrefixRejectsNonPrefix(): void
    {
        $this->assertFalse(WaitlistSettlementHelper::isFifoPrefix([1, 3], [1, 2, 3, 4]));
        $this->assertFalse(WaitlistSettlementHelper::isFifoPrefix([2], [1, 2, 3]));
        $this->assertFalse(WaitlistSettlementHelper::isFifoPrefix([1, 2, 3, 4, 5], [1, 2, 3]));
    }

    public function testPromoteByIdsMarksSelectedAsEnrolledIgnoringCapacity(): void
    {
        $db = new WaitlistSettlementFakeDatabase([
            (object) ['id' => 10, 'student' => 1, 'lesson' => 5],
            (object) ['id' => 11, 'student' => 2, 'lesson' => 5],
            (object) ['id' => 12, 'student' => 3, 'lesson' => 5],
        ]);

        $promoted = WaitlistSettlementHelper::promoteByIds($db, 5, [11, 12]);

        $this->assertCount(2, $promoted);
        $this->assertSame([11, 12], $db->promotedIds);
    }

    public function testDismissByIdsDeletesWaitingRows(): void
    {
        $db = new WaitlistSettlementFakeDatabase([
            (object) ['id' => 10, 'student' => 1, 'lesson' => 5],
            (object) ['id' => 11, 'student' => 2, 'lesson' => 5],
        ]);

        $dismissed = WaitlistSettlementHelper::dismissByIds($db, 5, [10]);

        $this->assertCount(1, $dismissed);
        $this->assertSame([10], $db->dismissedIds);
    }

    // ── listWaitingOrdered ────────────────────────────────────────────────────

    public function testListWaitingOrderedReturnsEmptyForInvalidLessonId(): void
    {
        $db = new WaitlistSettlementFakeDatabase([]);
        $this->assertSame([], WaitlistSettlementHelper::listWaitingOrdered($db, 0));
        $this->assertSame([], WaitlistSettlementHelper::listWaitingOrdered($db, -1));
    }

    public function testListWaitingOrderedAddsSequentialFifoRanks(): void
    {
        $db = new WaitlistSettlementFakeDatabase([
            (object) ['id' => 20, 'student' => 1, 'lesson' => 7],
            (object) ['id' => 25, 'student' => 2, 'lesson' => 7],
            (object) ['id' => 30, 'student' => 3, 'lesson' => 7],
        ]);

        $rows = WaitlistSettlementHelper::listWaitingOrdered($db, 7);

        $this->assertCount(3, $rows);
        $this->assertSame(1, $rows[0]->fifo_rank);
        $this->assertSame(2, $rows[1]->fifo_rank);
        $this->assertSame(3, $rows[2]->fifo_rank);
    }

    public function testListWaitingOrderedSetsSubscriptionIdFromRowId(): void
    {
        $db = new WaitlistSettlementFakeDatabase([
            (object) ['id' => 42, 'student' => 5, 'lesson' => 7],
        ]);

        $rows = WaitlistSettlementHelper::listWaitingOrdered($db, 7);

        $this->assertSame(42, $rows[0]->subscription_id);
    }

    public function testListWaitingOrderedReturnsEmptyWhenNoWaitingRows(): void
    {
        $db = new WaitlistSettlementFakeDatabase([]);
        $rows = WaitlistSettlementHelper::listWaitingOrdered($db, 7);

        $this->assertSame([], $rows);
    }
}

/**
 * Minimal fake database for WaitlistSettlementHelper.
 */
final class WaitlistSettlementFakeDatabase
{
    /** @var object[] */
    public array $waiting;

    /** @var int[] */
    public array $promotedIds = [];

    /** @var int[] */
    public array $dismissedIds = [];

    private ?WaitlistSettlementFakeQuery $lastQuery = null;

    /**
     * @param   object[]  $waiting  Waiting rows.
     */
    public function __construct(array $waiting)
    {
        $this->waiting = $waiting;
    }

    public function getQuery(bool $new = true): WaitlistSettlementFakeQuery
    {
        $this->lastQuery = new WaitlistSettlementFakeQuery();

        return $this->lastQuery;
    }

    public function quoteName($name, $as = null)
    {
        if (is_array($name)) {
            return $name;
        }

        return $name;
    }

    public function setQuery($query, $offset = 0, $limit = 0): self
    {
        $this->lastQuery = $query instanceof WaitlistSettlementFakeQuery ? $query : $this->lastQuery;

        return $this;
    }

    public function loadObjectList(): array
    {
        $ids = $this->lastQuery->inIds ?? [];

        return array_values(array_filter(
            $this->waiting,
            static fn(object $row): bool => $ids === [] || in_array((int) $row->id, $ids, true)
        ));
    }

    public function execute(): bool
    {
        if ($this->lastQuery === null) {
            return true;
        }

        if ($this->lastQuery->type === 'update') {
            $this->promotedIds = $this->lastQuery->inIds ?? [];
        }

        if ($this->lastQuery->type === 'delete') {
            $this->dismissedIds = $this->lastQuery->inIds ?? [];
        }

        return true;
    }
}

final class WaitlistSettlementFakeQuery
{
    public string $type = 'select';

    /** @var int[]|null */
    public ?array $inIds = null;

    public function select($columns): self
    {
        $this->type = 'select';

        return $this;
    }

    public function from($table): self
    {
        return $this;
    }

    public function join($type, $conditions): self
    {
        return $this;
    }

    public function where($conditions): self
    {
        if (is_string($conditions) && preg_match('/IN \(([^)]+)\)/', $conditions, $matches)) {
            $this->inIds = array_map('intval', explode(',', $matches[1]));
        }

        return $this;
    }

    public function order($order): self
    {
        return $this;
    }

    public function update($table): self
    {
        $this->type = 'update';

        return $this;
    }

    public function delete($table): self
    {
        $this->type = 'delete';

        return $this;
    }

    public function set($conditions): self
    {
        return $this;
    }
}
