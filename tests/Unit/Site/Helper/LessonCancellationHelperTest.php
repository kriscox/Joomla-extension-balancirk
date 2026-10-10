<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\LessonCancellationHelper;
use PHPUnit\Framework\TestCase;

final class LessonCancellationHelperTest extends TestCase
{
    // ── cancelLesson ──────────────────────────────────────────────────────────

    public function testCancelLessonReturnsFalseForZeroId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertFalse(LessonCancellationHelper::cancelLesson($db, 0));
    }

    public function testCancelLessonReturnsFalseForNegativeId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertFalse(LessonCancellationHelper::cancelLesson($db, -1));
    }

    public function testCancelLessonExecutesUpdateForValidId(): void
    {
        $db = new CancellationFakeDb();
        $result = LessonCancellationHelper::cancelLesson($db, 5);

        $this->assertTrue($result);
        $this->assertContains('update', $db->executedTypes);
    }

    // ── dismissEnrolledByIds ──────────────────────────────────────────────────

    public function testDismissEnrolledByIdsReturnEmptyForInvalidLessonId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertSame([], LessonCancellationHelper::dismissEnrolledByIds($db, 0, [1, 2]));
    }

    public function testDismissEnrolledByIdsReturnEmptyForEmptyIds(): void
    {
        $db = new CancellationFakeDb();
        $this->assertSame([], LessonCancellationHelper::dismissEnrolledByIds($db, 5, []));
    }

    public function testDismissEnrolledByIdsReturnEmptyWhenNoRowsMatch(): void
    {
        $db = new CancellationFakeDb([]);
        $result = LessonCancellationHelper::dismissEnrolledByIds($db, 5, [10, 11]);

        $this->assertSame([], $result);
        $this->assertNotContains('delete', $db->executedTypes);
    }

    public function testDismissEnrolledByIdsDeletesAndReturnsMatchingRows(): void
    {
        $rows = [
            (object) ['id' => 10, 'student' => 1, 'lesson' => 5],
            (object) ['id' => 11, 'student' => 2, 'lesson' => 5],
        ];
        $db = new CancellationFakeDb($rows);

        $result = LessonCancellationHelper::dismissEnrolledByIds($db, 5, [10, 11, 99]);

        $this->assertCount(2, $result);
        $this->assertContains('delete', $db->executedTypes);
    }

    public function testDismissEnrolledByIdsFiltersDuplicateIds(): void
    {
        $rows = [(object) ['id' => 10, 'student' => 1, 'lesson' => 5]];
        $db = new CancellationFakeDb($rows);

        $result = LessonCancellationHelper::dismissEnrolledByIds($db, 5, [10, 10, 10]);

        $this->assertCount(1, $result);
    }

    // ── listEnrolledForCancellation / listWaitingForCancellation ──────────────

    public function testListEnrolledForCancellationReturnsEmptyForInvalidLessonId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertSame([], LessonCancellationHelper::listEnrolledForCancellation($db, 0));
    }

    public function testListWaitingForCancellationReturnsEmptyForInvalidLessonId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertSame([], LessonCancellationHelper::listWaitingForCancellation($db, 0));
    }

    public function testListEnrolledForCancellationUsesSubscribedZeroFilter(): void
    {
        $db = new CancellationFakeDb([]);
        LessonCancellationHelper::listEnrolledForCancellation($db, 7);

        $this->assertSubscribedFilter($db->lastQuery, 0);
    }

    public function testListWaitingForCancellationUsesSubscribedOneFilter(): void
    {
        $db = new CancellationFakeDb([]);
        LessonCancellationHelper::listWaitingForCancellation($db, 7);

        $this->assertSubscribedFilter($db->lastQuery, 1);
    }

    public function testListEnrolledForCancellationReturnsLoadedRows(): void
    {
        $rows = [
            (object) [
                'subscription_id' => 10,
                'student' => 1,
                'firstname' => 'Jan',
                'name' => 'Smit',
                'birthdate' => '2015-01-01',
            ],
        ];
        $db = new CancellationFakeDb($rows);

        $result = LessonCancellationHelper::listEnrolledForCancellation($db, 7);

        $this->assertCount(1, $result);
    }

    // ── loadParentMembers ─────────────────────────────────────────────────────

    public function testLoadParentMembersReturnsEmptyForInvalidStudentId(): void
    {
        $db = new CancellationFakeDb();
        $this->assertSame([], LessonCancellationHelper::loadParentMembers($db, 0));
        $this->assertSame([], LessonCancellationHelper::loadParentMembers($db, -5));
    }

    public function testLoadParentMembersReturnsLoadedRows(): void
    {
        $members = [
            (object) ['id' => 3, 'firstname' => 'Els', 'name' => 'Peeters', 'email' => 'els@example.com'],
        ];
        $db = new CancellationFakeDb($members);

        $result = LessonCancellationHelper::loadParentMembers($db, 99);

        $this->assertCount(1, $result);
        $this->assertSame('Els', $result[0]->firstname);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function assertSubscribedFilter(?CancellationFakeQuery $query, int $expected): void
    {
        $this->assertNotNull($query);
        $found = false;

        foreach ($query->whereConditions as $cond) {
            if (str_contains((string) $cond, 'subscribed') && str_contains((string) $cond, (string) $expected)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Expected subscribed = {$expected} in WHERE conditions");
    }
}

/**
 * Fluent query builder stub for LessonCancellationHelper tests.
 */
final class CancellationFakeQuery
{
    public string $type = 'select';

    /** @var string[] */
    public array $whereConditions = [];

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

    public function where($conditions): self
    {
        $this->whereConditions[] = (string) $conditions;
        return $this;
    }

    public function order($conditions): self
    {
        return $this;
    }
}

/**
 * Minimal database stub for LessonCancellationHelper tests.
 */
final class CancellationFakeDb
{
    /** @var string[] */
    public array $executedTypes = [];

    public ?CancellationFakeQuery $lastQuery = null;

    /** @var object[] */
    private array $loadRows;

    /**
     * @param   object[]  $loadRows  Rows returned by loadObjectList().
     */
    public function __construct(array $loadRows = [])
    {
        $this->loadRows = $loadRows;
    }

    public function getQuery(bool $new = true): CancellationFakeQuery
    {
        return $this->lastQuery = new CancellationFakeQuery();
    }

    /**
     * @param   string|string[]  $name  Column or table name.
     * @param   string|null      $as    Alias (ignored).
     *
     * @return  string|string[]
     */
    public function quoteName($name, $as = null)
    {
        if (is_array($name)) {
            return $name;
        }

        return (string) $name;
    }

    public function quote(mixed $value): string
    {
        return "'" . $value . "'";
    }

    public function setQuery(mixed $query, int $offset = 0, int $limit = 0): self
    {
        if ($query instanceof CancellationFakeQuery) {
            $this->lastQuery = $query;
        }

        return $this;
    }

    /** @return object[] */
    public function loadObjectList(): array
    {
        return $this->loadRows;
    }

    public function execute(): bool
    {
        if ($this->lastQuery !== null) {
            $this->executedTypes[] = $this->lastQuery->type;
        }

        return true;
    }
}
