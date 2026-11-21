<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\WaitlistPromotionHelper;
use PHPUnit\Framework\TestCase;

final class WaitlistPromotionHelperTest extends TestCase
{
    public function testSeatsToPromoteUsesFreeSeatsAndLimit(): void
    {
        $this->assertSame(2, WaitlistPromotionHelper::seatsToPromote(10, 8, 5, 10));
        $this->assertSame(1, WaitlistPromotionHelper::seatsToPromote(10, 8, 5, 1));
        $this->assertSame(1, WaitlistPromotionHelper::seatsToPromote(10, 9, 1, 3));
    }

    public function testSeatsToPromoteIsZeroWhenLessonIsFull(): void
    {
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, 10, 4, 5));
    }

    public function testSeatsToPromoteIsZeroWhenOverEnrolled(): void
    {
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, 12, 4, 5));
    }

    public function testSeatsToPromoteIsZeroWhenWaitingListIsEmpty(): void
    {
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, 7, 0, 5));
    }

    public function testSeatsToPromoteIsZeroForNegativeInputs(): void
    {
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(-1, 2, 2, 2));
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, -1, 2, 2));
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, 2, -1, 2));
        $this->assertSame(0, WaitlistPromotionHelper::seatsToPromote(10, 2, 2, -1));
    }

    public function testPromoteFromWaitingListSelectsOldestIdsAndMarksEnrolled(): void
    {
        $db = new WaitlistPromotionFakeDatabase(
            maxStudents: 10,
            enrolled: 9,
            waiting: [
                (object) ['id' => 4, 'student' => 21, 'lesson' => 3],
                (object) ['id' => 7, 'student' => 22, 'lesson' => 3],
            ]
        );

        $promoted = WaitlistPromotionHelper::promoteFromWaitingList($db, 3, 1);

        $this->assertCount(1, $promoted);
        $this->assertSame(4, (int) $promoted[0]->id);
        $this->assertSame([4], $db->promotedIds);
    }

    public function testPromoteFromWaitingListDoesNothingWhenThereAreNoFreeSeats(): void
    {
        $db = new WaitlistPromotionFakeDatabase(
            maxStudents: 10,
            enrolled: 10,
            waiting: [
                (object) ['id' => 4, 'student' => 21, 'lesson' => 3],
            ]
        );

        $promoted = WaitlistPromotionHelper::promoteFromWaitingList($db, 3, 1);

        $this->assertSame([], $promoted);
        $this->assertSame([], $db->promotedIds);
    }

    public function testPromoteFromWaitingListCapsBatchToFreeSeats(): void
    {
        $db = new WaitlistPromotionFakeDatabase(
            maxStudents: 10,
            enrolled: 8,
            waiting: [
                (object) ['id' => 4, 'student' => 21, 'lesson' => 3],
                (object) ['id' => 5, 'student' => 22, 'lesson' => 3],
                (object) ['id' => 6, 'student' => 23, 'lesson' => 3],
            ]
        );

        $promoted = WaitlistPromotionHelper::promoteFromWaitingList($db, 3, 10);

        $this->assertCount(2, $promoted);
        $this->assertSame([4, 5], $db->promotedIds);
    }

    public function testPromoteFromWaitingListReturnsEmptyForInvalidLesson(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 5, waiting: []);

        $this->assertSame([], WaitlistPromotionHelper::promoteFromWaitingList($db, 0, 1));
    }

    // -------------------------------------------------------------------------
    // loadMaxStudents() — direct tests
    // -------------------------------------------------------------------------

    public function testLoadMaxStudentsReturnsNullForZeroLessonId(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 0, waiting: []);

        $this->assertNull(WaitlistPromotionHelper::loadMaxStudents($db, 0));
    }

    public function testLoadMaxStudentsReturnsNullForNegativeLessonId(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 0, waiting: []);

        $this->assertNull(WaitlistPromotionHelper::loadMaxStudents($db, -1));
    }

    public function testLoadMaxStudentsReturnsIntegerForValidLesson(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 25, enrolled: 10, waiting: []);

        $this->assertSame(25, WaitlistPromotionHelper::loadMaxStudents($db, 3));
    }

    public function testLoadMaxStudentsReturnsNullWhenDbReturnsNull(): void
    {
        $db = new class {
            public function getQuery(bool $new): object
            {
                return new class {
                    public function select(mixed $c): static
                    {
                        return $this;
                    }

                    public function from(mixed $t): static
                    {
                        return $this;
                    }

                    public function where(mixed $w): static
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(mixed $name): string
            {
                return "`$name`";
            }

            public function setQuery(mixed $query): static
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }
        };

        $this->assertNull(WaitlistPromotionHelper::loadMaxStudents($db, 5));
    }

    // -------------------------------------------------------------------------
    // countByStatus() — direct tests
    // -------------------------------------------------------------------------

    public function testCountByStatusReturnsZeroForZeroLessonId(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 5, waiting: []);

        $this->assertSame(0, WaitlistPromotionHelper::countByStatus($db, 0, 0));
    }

    public function testCountByStatusReturnsZeroForNegativeLessonId(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 5, waiting: []);

        $this->assertSame(0, WaitlistPromotionHelper::countByStatus($db, -1, 0));
    }

    public function testCountByStatusReturnsEnrolledCountForSubscribedZero(): void
    {
        $db = new WaitlistPromotionFakeDatabase(maxStudents: 10, enrolled: 7, waiting: []);

        $this->assertSame(7, WaitlistPromotionHelper::countByStatus($db, 3, 0));
    }

    public function testCountByStatusReturnsWaitingCountForSubscribedOne(): void
    {
        $db = new WaitlistPromotionFakeDatabase(
            maxStudents: 10,
            enrolled: 7,
            waiting: [
                (object) ['id' => 1, 'student' => 10, 'lesson' => 3],
                (object) ['id' => 2, 'student' => 11, 'lesson' => 3],
            ]
        );

        $this->assertSame(2, WaitlistPromotionHelper::countByStatus($db, 3, 1));
    }
}

/**
 * Minimal Joomla-like database for WaitlistPromotionHelper tests.
 */
final class WaitlistPromotionFakeDatabase
{
    /** @var int[] */
    public array $promotedIds = [];

    private ?object $query = null;

    /**
     * @param  object[]  $waiting
     */
    public function __construct(
        private readonly int $maxStudents,
        private readonly int $enrolled,
        private readonly array $waiting
    ) {
    }

    public function getQuery(bool $new): object
    {
        return new class {
            public string $type = 'select';
            public array $select = [];
            public string $table = '';
            public array $where = [];
            public array $order = [];
            public array $set = [];
            public int $limit = 0;

            public function select(mixed $columns): static
            {
                $this->type = 'select';
                $this->select[] = $columns;

                return $this;
            }

            public function from(mixed $table): static
            {
                $this->table = (string) $table;

                return $this;
            }

            public function update(mixed $table): static
            {
                $this->type = 'update';
                $this->table = (string) $table;

                return $this;
            }

            public function set(mixed $assignment): static
            {
                $this->set[] = (string) $assignment;

                return $this;
            }

            public function where(mixed $condition): static
            {
                $this->where[] = (string) $condition;

                return $this;
            }

            public function order(mixed $order): static
            {
                $this->order[] = (string) $order;

                return $this;
            }
        };
    }

    public function quoteName(mixed $name): string
    {
        if (is_array($name)) {
            return implode(', ', array_map(static fn($item) => '`' . $item . '`', $name));
        }

        return '`' . $name . '`';
    }

    public function setQuery(mixed $query, int $offset = 0, int $limit = 0): static
    {
        if (is_object($query) && $limit > 0) {
            $query->limit = $limit;
        }

        $this->query = is_object($query) ? $query : null;

        return $this;
    }

    public function execute(): void
    {
        if ($this->query === null || ($this->query->type ?? '') !== 'update') {
            return;
        }

        foreach ($this->query->where as $condition) {
            if (preg_match('/IN \((.+)\)/', $condition, $matches) === 1) {
                $this->promotedIds = array_map('intval', explode(',', $matches[1]));
            }
        }
    }

    public function loadResult(): mixed
    {
        if ($this->query === null) {
            return null;
        }

        $select = $this->flatten($this->query->select);

        if (str_contains($select, 'max_students')) {
            return $this->maxStudents;
        }

        if (str_contains($select, 'COUNT')) {
            return str_contains(implode(' ', $this->query->where), 'subscribed` = 0')
                || str_contains(implode(' ', $this->query->where), 'subscribed = 0')
                ? $this->enrolled
                : count($this->waiting);
        }

        return null;
    }

    public function loadObjectList(): array
    {
        $limit = (int) ($this->query->limit ?? 0);

        if ($limit > 0) {
            return array_slice($this->waiting, 0, $limit);
        }

        return $this->waiting;
    }

    private function flatten(array $select): string
    {
        $parts = [];

        foreach ($select as $item) {
            if (is_array($item)) {
                $parts[] = implode(' ', $item);
                continue;
            }

            $parts[] = (string) $item;
        }

        return implode(' ', $parts);
    }
}
