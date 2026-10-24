<?php

declare(strict_types=1);

namespace Tests\Unit\Site\Helper;

use CoCoCo\Component\Balancirk\Site\Helper\SubscriptionMailHelper;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testInvoiceHintBeforeStartMentionsLessonStart(): void
    {
        $hint = SubscriptionMailHelper::getInvoiceHint('2026-09-10', '2026-08-20', false);

        $this->assertStringContainsString('10/09/2026', $hint);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
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

    public function testBuildPromotionMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'promotion_email_subject' => 'Plaats vrij {lesson_name}',
            'promotion_email_body' => '{student_firstname} mag naar {lesson_name}',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildPromotionMailMessage($lesson, $student, $member, '2026-08-20');

        $this->assertSame('Plaats vrij Acro', $mail['subject']);
        $this->assertSame('Lena mag naar Acro', $mail['body']);
    }

    public function testBuildPromotionMailMessageFallsBackToDefaultTemplates(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildPromotionMailMessage($lesson, $student, $member, '2026-08-20');

        $this->assertStringContainsString('Acro', $mail['subject']);
        $this->assertStringContainsString('wachtlijst', $mail['body']);
        $this->assertStringContainsString('Lena', $mail['body']);
    }

    public function testPromotionInvoiceHintUsesEnrolledWording(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'promotion_email_body' => '{invoice_hint}',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildPromotionMailMessage($lesson, $student, $member, '2026-08-20');

        $this->assertStringNotContainsString('wachtlijst', strtolower($mail['body']));
    }

    public function testGetDefaultPromotionTemplatesMentionMoveFromWaitingList(): void
    {
        $this->assertStringContainsString('{lesson_name}', SubscriptionMailHelper::getDefaultPromotionSubjectTemplate());
        $this->assertStringContainsString('wachtlijst', SubscriptionMailHelper::getDefaultPromotionBodyTemplate());
    }

    public function testBuildYearstartPromotionMailMessageUsesDefaults(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildYearstartPromotionMailMessage($lesson, $student, $member, '2026-08-20');

        $this->assertStringContainsString('Acro', $mail['subject']);
        $this->assertStringContainsString('definitief ingeschreven', $mail['body']);
    }

    public function testBuildRejectionMailMessageUsesDefaults(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildRejectionMailMessage($lesson, $student, $member, '2026-08-20');

        $this->assertStringContainsString('Wachtlijst', $mail['subject']);
        $this->assertStringContainsString('geen plaats', $mail['body']);
    }

    public function testBuildCancellationMailMessageUsesCustomSubjectAndBody(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20',
            [],
            'Custom cancel {lesson_name}',
            'Beste {member_firstname}, les {lesson_name} gaat niet door. Alternatief: circus beginners.'
        );

        $this->assertSame('Custom cancel Acro', $mail['subject']);
        $this->assertSame('Beste Els, les Acro gaat niet door. Alternatief: circus beginners.', $mail['body']);
    }

    public function testBuildCancellationMailMessageUsesLessonSpecificTemplate(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'cancellation_email_subject' => 'Les {lesson_name} afgelast',
            'cancellation_email_body' => 'Hallo {member_firstname}, {lesson_name} gaat niet door dit jaar.',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20'
        );

        $this->assertSame('Les Acro afgelast', $mail['subject']);
        $this->assertSame('Hallo Els, Acro gaat niet door dit jaar.', $mail['body']);
    }

    public function testBuildCancellationMailMessageFallsBackToGlobalDefaults(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];
        $defaults = [
            'cancellation_subject' => 'Annulatie {lesson_name}',
            'cancellation_body' => 'Les {lesson_name} is geannuleerd voor {member_firstname}.',
        ];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20',
            $defaults
        );

        $this->assertSame('Annulatie Acro', $mail['subject']);
        $this->assertSame('Les Acro is geannuleerd voor Els.', $mail['body']);
    }

    public function testBuildYearstartPromotionMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name' => 'Acro',
            'start' => '2026-09-10',
            'end' => '2027-05-20',
            'yearstart_email_subject' => 'Jij mag meedoen aan {lesson_name}',
            'yearstart_email_body' => '{student_firstname} is definitief in "{lesson_name}".',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildYearstartPromotionMailMessage(
            $lesson,
            $student,
            $member,
            '2026-09-01'
        );

        $this->assertSame('Jij mag meedoen aan Acro', $mail['subject']);
        $this->assertSame('Lena is definitief in "Acro".', $mail['body']);
    }

    public function testBuildRejectionMailMessageUsesGlobalDefaultsWhenNoLessonTemplate(): void
    {
        $lesson = (object) ['name' => 'Acro', 'start' => '2026-09-10', 'end' => '2027-05-20'];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];
        $defaults = [
            'rejection_subject' => 'Geen plaats voor {student_firstname} in {lesson_name}',
            'rejection_body' => 'Helaas, {student_firstname} kan niet meedoen aan {lesson_name}.',
        ];

        $mail = SubscriptionMailHelper::buildRejectionMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20',
            $defaults
        );

        $this->assertSame('Geen plaats voor Lena in Acro', $mail['subject']);
        $this->assertSame('Helaas, Lena kan niet meedoen aan Acro.', $mail['body']);
    }
}
