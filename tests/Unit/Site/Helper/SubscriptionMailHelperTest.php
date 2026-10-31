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

    // -----------------------------------------------------------------------
    // buildCancellationMailMessage() — template resolution priority chain
    // -----------------------------------------------------------------------

    /**
     * When no custom template and no lesson-specific template are supplied,
     * buildCancellationMailMessage() must fall back to the hardcoded Dutch
     * default (getDefaultCancellationSubjectTemplate / getDefaultCancellationBodyTemplate).
     *
     * @return void
     */
    public function testBuildCancellationMailMessageFallsBackToHardcodedDefault(): void
    {
        $lesson = (object) ['name' => 'Turnen', 'start' => '2026-09-01', 'end' => '2027-06-30'];
        $student = (object) ['firstname' => 'Sofie', 'name' => 'Claes'];
        $member = (object) ['firstname' => 'Marc', 'name' => 'Claes'];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20'
        );

        $this->assertStringContainsString('Turnen', $mail['subject']);
        $this->assertStringContainsString('afgelast', $mail['subject']);
        $this->assertStringContainsString('Marc', $mail['body']);
        $this->assertStringContainsString('Turnen', $mail['body']);
        $this->assertStringContainsString('afgelast', $mail['body']);
    }

    /**
     * When the lesson row carries a cancellation_email_subject/body and no
     * custom per-recipient template is provided, those lesson-specific values
     * must be used (priority 1 in the resolution chain).
     *
     * @return void
     */
    public function testBuildCancellationMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name'                     => 'Turnen',
            'start'                    => '2026-09-01',
            'end'                      => '2027-06-30',
            'cancellation_email_subject' => 'Afgelast: {lesson_name}',
            'cancellation_email_body'    => 'Hallo {member_firstname}, {lesson_name} gaat dit jaar niet door.',
        ];
        $student = (object) ['firstname' => 'Sofie', 'name' => 'Claes'];
        $member = (object) ['firstname' => 'Marc', 'name' => 'Claes'];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20'
        );

        $this->assertSame('Afgelast: Turnen', $mail['subject']);
        $this->assertSame('Hallo Marc, Turnen gaat dit jaar niet door.', $mail['body']);
    }

    /**
     * When no custom and no lesson-specific templates are set but $defaults
     * carries cancellation_subject / cancellation_body, those global defaults
     * must be used (priority 2).
     *
     * @return void
     */
    public function testBuildCancellationMailMessageUsesGlobalDefaultsWhenNoLessonTemplate(): void
    {
        $lesson = (object) ['name' => 'Turnen', 'start' => '2026-09-01', 'end' => '2027-06-30'];
        $student = (object) ['firstname' => 'Sofie', 'name' => 'Claes'];
        $member = (object) ['firstname' => 'Marc', 'name' => 'Claes'];

        $defaults = [
            'cancellation_subject' => 'Geannuleerd: {lesson_name}',
            'cancellation_body'    => 'Dag {member_firstname}, {lesson_name} wordt geannuleerd.',
        ];

        $mail = SubscriptionMailHelper::buildCancellationMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20',
            $defaults
        );

        $this->assertSame('Geannuleerd: Turnen', $mail['subject']);
        $this->assertSame('Dag Marc, Turnen wordt geannuleerd.', $mail['body']);
    }

    // -----------------------------------------------------------------------
    // buildYearstartPromotionMailMessage() — lesson-specific template
    // -----------------------------------------------------------------------

    /**
     * When the lesson row carries yearstart_email_subject / yearstart_email_body,
     * buildYearstartPromotionMailMessage() must prefer those over the hardcoded
     * Dutch defaults.
     *
     * @return void
     */
    public function testBuildYearstartPromotionMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name'                    => 'Acro',
            'start'                   => '2026-09-10',
            'end'                     => '2027-05-20',
            'yearstart_email_subject' => 'Jaarstart bevestiging {lesson_name}',
            'yearstart_email_body'    => 'Hoi {member_firstname}, {student_firstname} is bevestigd voor {lesson_name}.',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildYearstartPromotionMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-01'
        );

        $this->assertSame('Jaarstart bevestiging Acro', $mail['subject']);
        $this->assertSame('Hoi Els, Lena is bevestigd voor Acro.', $mail['body']);
    }

    // -----------------------------------------------------------------------
    // buildRejectionMailMessage() — lesson-specific template
    // -----------------------------------------------------------------------

    /**
     * When the lesson row carries rejection_email_subject / rejection_email_body,
     * buildRejectionMailMessage() must prefer those over the hardcoded defaults.
     *
     * @return void
     */
    public function testBuildRejectionMailMessageUsesLessonSpecificTemplates(): void
    {
        $lesson = (object) [
            'name'                    => 'Acro',
            'start'                   => '2026-09-10',
            'end'                     => '2027-05-20',
            'rejection_email_subject' => 'Geen plaats in {lesson_name}',
            'rejection_email_body'    => 'Dag {member_firstname}, er is geen plek voor {student_firstname} in {lesson_name}.',
        ];
        $student = (object) ['firstname' => 'Lena', 'name' => 'Peeters'];
        $member = (object) ['firstname' => 'Els', 'name' => 'Peeters'];

        $mail = SubscriptionMailHelper::buildRejectionMailMessage(
            $lesson,
            $student,
            $member,
            '2026-08-20'
        );

        $this->assertSame('Geen plaats in Acro', $mail['subject']);
        $this->assertSame('Dag Els, er is geen plek voor Lena in Acro.', $mail['body']);
    }

    // -----------------------------------------------------------------------
    // Default template content verification
    // -----------------------------------------------------------------------

    /**
     * The hardcoded Dutch cancellation subject/body must mention "afgelast"
     * so users who omit a custom template receive intelligible mail content.
     *
     * @return void
     */
    public function testGetDefaultCancellationTemplatesMentionAfgelast(): void
    {
        $this->assertStringContainsString('afgelast', SubscriptionMailHelper::getDefaultCancellationSubjectTemplate());
        $this->assertStringContainsString('afgelast', SubscriptionMailHelper::getDefaultCancellationBodyTemplate());
    }

    /**
     * The default yearstart promotion subject must contain {lesson_name} so
     * the rendered mail refers to the correct lesson.
     * The body must mention "definitief" to indicate a permanent booking.
     *
     * @return void
     */
    public function testGetDefaultYearstartPromotionTemplatesContainKeyContent(): void
    {
        $this->assertStringContainsString('{lesson_name}', SubscriptionMailHelper::getDefaultYearstartPromotionSubjectTemplate());
        $this->assertStringContainsString('definitief', SubscriptionMailHelper::getDefaultYearstartPromotionBodyTemplate());
    }

    /**
     * The default rejection subject must mention "Wachtlijst" and the body
     * must explain there is no place available ("geen plaats bieden").
     *
     * @return void
     */
    public function testGetDefaultRejectionTemplatesMentionGeenPlaats(): void
    {
        $this->assertStringContainsString('Wachtlijst', SubscriptionMailHelper::getDefaultRejectionSubjectTemplate());
        $this->assertStringContainsString('geen plaats bieden', SubscriptionMailHelper::getDefaultRejectionBodyTemplate());
    }
}
