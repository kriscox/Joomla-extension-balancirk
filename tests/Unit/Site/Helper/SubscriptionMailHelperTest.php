<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\SubscriptionMailHelper;
use PHPUnit\Framework\TestCase;

final class SubscriptionMailHelperTest extends TestCase
{
    public function testBuildContextIncludesNamesAndDates(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $context = SubscriptionMailHelper::buildContext($lesson, $student, $member, '2026-08-20', false);

        $this->assertSame('Els', $context['{member_firstname}']);
        $this->assertSame('Els Peeters', $context['{member_fullname}']);
        $this->assertSame('Lena Peeters', $context['{student_fullname}']);
        $this->assertSame('10/09/2026', $context['{lesson_start_date}']);
        $this->assertSame('20/08/2026', $context['{subscription_date}']);
    }

    public function testInvoiceHintBeforeStartMentionsLessonStart(): void
    {
        $hint = SubscriptionMailHelper::getInvoiceHint('2026-09-10', '2026-08-20', false);

        $this->assertStringContainsString('10/09/2026', $hint);
    }

    public function testInvoiceHintAfterStartMentionsLessonAlreadyStarted(): void
    {
        $hint = SubscriptionMailHelper::getInvoiceHint('2026-09-10', '2026-10-01', false);

        $this->assertStringContainsString('10/09/2026', $hint);
    }

    public function testWaitingListHintUsesWaitingListText(): void
    {
        $hint = SubscriptionMailHelper::getInvoiceHint('2026-09-10', '2026-08-20', true);

        $this->assertNotSame('', $hint);
    }

    public function testResolveTemplatePrefersLessonSpecificValue(): void
    {
        $resolved = SubscriptionMailHelper::resolveTemplate('Lesson template', 'Global template', 'Fallback template');

        $this->assertSame('Lesson template', $resolved);
    }

    public function testRenderTemplateReplacesPlaceholders(): void
    {
        $rendered = SubscriptionMailHelper::renderTemplate(
            'Hallo {member_firstname}, {student_firstname} komt naar {lesson_name}.',
            [
                '{member_firstname}' => 'Els',
                '{student_firstname}' => 'Lena',
                '{lesson_name}' => 'Acro',
            ]
        );

        $this->assertSame('Hallo Els, Lena komt naar Acro.', $rendered);
    }
}
