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

    public function testResolveTemplateFallsBackToDefaultThenFallback(): void
    {
        $fromDefault = SubscriptionMailHelper::resolveTemplate('', 'Global template', 'Fallback template');
        $fromFallback = SubscriptionMailHelper::resolveTemplate('', '', 'Fallback template');

        $this->assertSame('Global template', $fromDefault);
        $this->assertSame('Fallback template', $fromFallback);
    }

    public function testGetInvoiceHintFallsBackWhenDatesInvalid(): void
    {
        $hint = SubscriptionMailHelper::getInvoiceHint('not-a-date', 'also-invalid', false);

        $this->assertStringContainsString('betalingsgegevens', $hint);
    }

    public function testBuildMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'subscription_email_subject' => 'Custom subject {lesson_name}',
            'subscription_email_body' => 'Hi {member_firstname}, {student_firstname} => {lesson_name}',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildMailMessage($lesson, $student, $member, '2026-08-20', false, []);

        $this->assertSame('Custom subject Acro', $mail['subject']);
        $this->assertSame('Hi Els, Lena => Acro', $mail['body']);
    }

    public function testRenderTemplateCollapsesExcessiveBlankLines(): void
    {
        $rendered = SubscriptionMailHelper::renderTemplate(
            "A\n\n\n\nB",
            []
        );

        $this->assertSame("A\n\nB", $rendered);
    }

    public function testBuildMailMessageWithWaitingListUsesWaitingListTemplates(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'waitinglist_email_subject' => 'Wachtlijst {lesson_name}',
            'waitinglist_email_body' => 'Beste {member_firstname}, je staat op de wachtlijst.',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildMailMessage($lesson, $student, $member, '2026-08-20', true, []);

        $this->assertSame('Wachtlijst Acro', $mail['subject']);
        $this->assertSame('Beste Els, je staat op de wachtlijst.', $mail['body']);
    }

    public function testBuildMailMessageFallsBackToDefaultWaitingListBodyTemplate(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildMailMessage($lesson, $student, $member, '2026-08-20', true, []);

        $this->assertStringContainsString('wachtlijst', strtolower($mail['body']));
    }

    public function testGetDefaultBodyTemplateForWaitingListContainsWachtlijstKeyword(): void
    {
        $body = SubscriptionMailHelper::getDefaultBodyTemplate(true);

        $this->assertStringContainsString('wachtlijst', $body);
    }

    public function testGetDefaultBodyTemplateForSubscriptionContainsLessonStartDatePlaceholder(): void
    {
        $body = SubscriptionMailHelper::getDefaultBodyTemplate(false);

        $this->assertStringContainsString('{lesson_start_date}', $body);
    }

    public function testGetDefaultSubjectTemplateForWaitingListContainsWachtlijstSuffix(): void
    {
        $subject = SubscriptionMailHelper::getDefaultSubjectTemplate(true);

        $this->assertStringContainsString('wachtlijst', $subject);
    }

    public function testGetDefaultSubjectTemplateForSubscriptionDoesNotContainWachtlijst(): void
    {
        $subject = SubscriptionMailHelper::getDefaultSubjectTemplate(false);

        $this->assertStringNotContainsString('wachtlijst', $subject);
    }

    public function testBuildContextWithMissingLessonEndDateReturnsEmptyString(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $context = SubscriptionMailHelper::buildContext($lesson, $student, $member, '2026-08-20', false);

        $this->assertSame('', $context['{lesson_end_date}']);
    }

    public function testBuildMailMessageWithGlobalDefaultTemplatesWhenNoLessonTemplate(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];
        $defaults = [
            'subscription_subject' => 'Global subject {lesson_name}',
            'subscription_body' => 'Global body for {member_firstname}',
        ];

        $mail = SubscriptionMailHelper::buildMailMessage($lesson, $student, $member, '2026-08-20', false, $defaults);

        $this->assertSame('Global subject Acro', $mail['subject']);
        $this->assertSame('Global body for Els', $mail['body']);
    }
}
