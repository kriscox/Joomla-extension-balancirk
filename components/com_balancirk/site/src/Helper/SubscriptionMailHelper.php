<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Helper;

\defined('_JEXEC') or die;

use DateTimeImmutable;
use Joomla\CMS\Language\Text;

/**
 * Helper for rendering subscription emails.
 *
 * @since  1.2.20
 */
class SubscriptionMailHelper
{
    /**
     * Build a rendered subject/body pair for a subscription mail.
     *
     * @param   object  $lesson            Lesson record.
     * @param   object  $student           Student record.
     * @param   object  $member            Member record.
     * @param   string  $subscriptionDate  Subscription date in Y-m-d format.
     * @param   bool    $waitingList       Whether the student ended up on the waiting list.
     * @param   array   $defaults          Default subject/body templates.
     *
     * @return  array{subject:string, body:string}
     *
     * @since   1.2.20
     */
    public static function buildMailMessage(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        bool $waitingList,
        array $defaults = []
    ): array {
        $subjectTemplate = self::resolveTemplate(
            $waitingList ? ($lesson->waitinglist_email_subject ?? null) : ($lesson->subscription_email_subject ?? null),
            $waitingList ? ($defaults['waitinglist_subject'] ?? null) : ($defaults['subscription_subject'] ?? null),
            self::getDefaultSubjectTemplate($waitingList)
        );
        $bodyTemplate = self::resolveTemplate(
            $waitingList ? ($lesson->waitinglist_email_body ?? null) : ($lesson->subscription_email_body ?? null),
            $waitingList ? ($defaults['waitinglist_body'] ?? null) : ($defaults['subscription_body'] ?? null),
            self::getDefaultBodyTemplate($waitingList)
        );
        $context = self::buildContext($lesson, $student, $member, $subscriptionDate, $waitingList);

        return [
            'subject' => self::renderTemplate($subjectTemplate, $context),
            'body' => self::renderTemplate($bodyTemplate, $context),
        ];
    }

    /**
     * Build a rendered subject/body pair for a waitlist-promotion mail.
     *
     * Invoice hint uses the enrolled (not waiting-list) wording.
     *
     * @param   object  $lesson            Lesson record.
     * @param   object  $student           Student record.
     * @param   object  $member            Member record.
     * @param   string  $subscriptionDate  Subscription date in Y-m-d format.
     * @param   array   $defaults          Default subject/body templates.
     *
     * @return  array{subject:string, body:string}
     *
     * @since   1.3.24
     */
    public static function buildPromotionMailMessage(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        array $defaults = []
    ): array {
        $subjectTemplate = self::resolveTemplate(
            $lesson->promotion_email_subject ?? null,
            $defaults['promotion_subject'] ?? null,
            self::getDefaultPromotionSubjectTemplate()
        );
        $bodyTemplate = self::resolveTemplate(
            $lesson->promotion_email_body ?? null,
            $defaults['promotion_body'] ?? null,
            self::getDefaultPromotionBodyTemplate()
        );
        $context = self::buildContext($lesson, $student, $member, $subscriptionDate, false);

        return [
            'subject' => self::renderTemplate($subjectTemplate, $context),
            'body' => self::renderTemplate($bodyTemplate, $context),
        ];
    }

    /**
     * Build a year-start (manual batch) promotion mail.
     *
     * @param   object  $lesson            Lesson record.
     * @param   object  $student           Student record.
     * @param   object  $member            Member record.
     * @param   string  $subscriptionDate  Subscription date in Y-m-d format.
     * @param   array   $defaults          Default subject/body templates.
     *
     * @return  array{subject:string, body:string}
     *
     * @since   1.3.25
     */
    public static function buildYearstartPromotionMailMessage(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        array $defaults = []
    ): array {
        $subjectTemplate = self::resolveTemplate(
            $lesson->yearstart_email_subject ?? null,
            $defaults['yearstart_subject'] ?? null,
            self::getDefaultYearstartPromotionSubjectTemplate()
        );
        $bodyTemplate = self::resolveTemplate(
            $lesson->yearstart_email_body ?? null,
            $defaults['yearstart_body'] ?? null,
            self::getDefaultYearstartPromotionBodyTemplate()
        );
        $context = self::buildContext($lesson, $student, $member, $subscriptionDate, false);

        return [
            'subject' => self::renderTemplate($subjectTemplate, $context),
            'body' => self::renderTemplate($bodyTemplate, $context),
        ];
    }

    /**
     * Build a waitlist rejection mail.
     *
     * @param   object  $lesson            Lesson record.
     * @param   object  $student           Student record.
     * @param   object  $member            Member record.
     * @param   string  $subscriptionDate  Subscription date in Y-m-d format.
     * @param   array   $defaults          Default subject/body templates.
     *
     * @return  array{subject:string, body:string}
     *
     * @since   1.3.25
     */
    public static function buildRejectionMailMessage(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        array $defaults = []
    ): array {
        $subjectTemplate = self::resolveTemplate(
            $lesson->rejection_email_subject ?? null,
            $defaults['rejection_subject'] ?? null,
            self::getDefaultRejectionSubjectTemplate()
        );
        $bodyTemplate = self::resolveTemplate(
            $lesson->rejection_email_body ?? null,
            $defaults['rejection_body'] ?? null,
            self::getDefaultRejectionBodyTemplate()
        );
        $context = self::buildContext($lesson, $student, $member, $subscriptionDate, true);

        return [
            'subject' => self::renderTemplate($subjectTemplate, $context),
            'body' => self::renderTemplate($bodyTemplate, $context),
        ];
    }

    /**
     * Build a lesson cancellation mail (optionally fully personalized subject/body).
     *
     * When $customSubject / $customBody are non-empty they are used as templates
     * (placeholders still applied). Otherwise lesson → global → hardcoded defaults.
     *
     * @param   object       $lesson            Lesson record.
     * @param   object       $student           Student record.
     * @param   object       $member            Member record.
     * @param   string       $subscriptionDate  Subscription date in Y-m-d format.
     * @param   array        $defaults          Default subject/body templates.
     * @param   string|null  $customSubject     Per-recipient subject override.
     * @param   string|null  $customBody        Per-recipient body override.
     *
     * @return  array{subject:string, body:string}
     *
     * @since   1.3.25
     */
    public static function buildCancellationMailMessage(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        array $defaults = [],
        ?string $customSubject = null,
        ?string $customBody = null
    ): array {
        $subjectTemplate = self::resolveTemplate(
            $customSubject,
            null,
            self::resolveTemplate(
                $lesson->cancellation_email_subject ?? null,
                $defaults['cancellation_subject'] ?? null,
                self::getDefaultCancellationSubjectTemplate()
            )
        );
        $bodyTemplate = self::resolveTemplate(
            $customBody,
            null,
            self::resolveTemplate(
                $lesson->cancellation_email_body ?? null,
                $defaults['cancellation_body'] ?? null,
                self::getDefaultCancellationBodyTemplate()
            )
        );
        $context = self::buildContext($lesson, $student, $member, $subscriptionDate, false);

        return [
            'subject' => self::renderTemplate($subjectTemplate, $context),
            'body' => self::renderTemplate($bodyTemplate, $context),
        ];
    }

    /**
     * Build placeholder context values.
     *
     * @param   object  $lesson            Lesson record.
     * @param   object  $student           Student record.
     * @param   object  $member            Member record.
     * @param   string  $subscriptionDate  Subscription date in Y-m-d format.
     * @param   bool    $waitingList       Whether the student is on the waiting list.
     *
     * @return  array<string, string>
     *
     * @since   1.2.20
     */
    public static function buildContext(
        object $lesson,
        object $student,
        object $member,
        string $subscriptionDate,
        bool $waitingList
    ): array {
        $memberFirstname = trim((string) ($member->firstname ?? ''));
        $memberName = trim((string) ($member->name ?? ''));
        $studentFirstname = trim((string) ($student->firstname ?? ''));
        $studentName = trim((string) ($student->name ?? ''));

        return [
            '{member_firstname}' => $memberFirstname,
            '{member_name}' => $memberName,
            '{member_fullname}' => trim($memberFirstname . ' ' . $memberName),
            '{student_firstname}' => $studentFirstname,
            '{student_name}' => $studentName,
            '{student_fullname}' => trim($studentFirstname . ' ' . $studentName),
            '{lesson_name}' => trim((string) ($lesson->name ?? '')),
            '{lesson_start_date}' => self::formatDate($lesson->start ?? null),
            '{lesson_end_date}' => self::formatDate($lesson->end ?? null),
            '{subscription_date}' => self::formatDate($subscriptionDate),
            '{invoice_hint}' => self::getInvoiceHint($lesson->start ?? null, $subscriptionDate, $waitingList),
        ];
    }

    /**
     * Render a template by replacing placeholders.
     *
     * @param   string  $template  Template string.
     * @param   array   $context   Placeholder values.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    public static function renderTemplate(string $template, array $context): string
    {
        $rendered = strtr($template, $context);

        $rendered = preg_replace("/(\r\n|\r|\n){3,}/", "\n\n", $rendered) ?? $rendered;

        return trim($rendered);
    }

    /**
     * Return the invoice expectation text.
     *
     * @param   string|null  $lessonStart       Lesson start date.
     * @param   string       $subscriptionDate  Subscription date.
     * @param   bool         $waitingList       Whether the subscription is on the waiting list.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    public static function getInvoiceHint(?string $lessonStart, string $subscriptionDate, bool $waitingList): string
    {
        if ($waitingList) {
            return self::getWaitingListInvoiceHint();
        }

        $startDate = self::createDate($lessonStart);
        $createdAt = self::createDate($subscriptionDate);

        if (!$startDate || !$createdAt) {
            return self::getFallbackInvoiceHint();
        }

        if ($createdAt < $startDate) {
            return self::translate(
                'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_BEFORE_START',
                $startDate->format('d/m/Y')
            );
        }

        return self::translate(
            'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_AFTER_START',
            $startDate->format('d/m/Y')
        );
    }

    /**
     * Resolve a lesson-specific template, then a global default, then the hardcoded fallback.
     *
     * @param   string|null  $lessonTemplate   Lesson-specific template.
     * @param   string|null  $defaultTemplate  Global default template.
     * @param   string       $fallback         Hardcoded fallback template.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    public static function resolveTemplate(?string $lessonTemplate, ?string $defaultTemplate, string $fallback): string
    {
        $lessonTemplate = trim((string) $lessonTemplate);

        if ($lessonTemplate !== '') {
            return $lessonTemplate;
        }

        $defaultTemplate = trim((string) $defaultTemplate);

        if ($defaultTemplate !== '') {
            return $defaultTemplate;
        }

        return $fallback;
    }

    /**
     * Get the default subject template.
     *
     * @param   bool  $waitingList  Whether the mail is for the waiting list.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    public static function getDefaultSubjectTemplate(bool $waitingList): string
    {
        if ($waitingList) {
            return self::translate('COM_BALANCIRK_SUBJECT_SUBSCRIPTION') . ' "{lesson_name}" - wachtlijst';
        }

        return self::translate('COM_BALANCIRK_SUBJECT_SUBSCRIPTION') . ' "{lesson_name}"';
    }

    /**
     * Get the default body template.
     *
     * @param   bool  $waitingList  Whether the mail is for the waiting list.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    public static function getDefaultBodyTemplate(bool $waitingList): string
    {
        if ($waitingList) {
            return <<<TEXT
Hallo {member_firstname},

Bedankt voor de inschrijving van {student_firstname} voor "{lesson_name}".

Deze inschrijving staat momenteel op de wachtlijst.

{invoice_hint}

Met vriendelijke groeten,

Het Balancirk team
TEXT;
        }

        return <<<TEXT
Hallo {member_firstname},

Bedankt voor de inschrijving van {student_firstname} voor "{lesson_name}".

De lessenreeks start op {lesson_start_date}.

{invoice_hint}

Met vriendelijke groeten,

Het Balancirk team
TEXT;
    }

    /**
     * Get the default promotion subject template.
     *
     * @return  string
     *
     * @since   1.3.24
     */
    public static function getDefaultPromotionSubjectTemplate(): string
    {
        return 'Plaats vrij in les "{lesson_name}"';
    }

    /**
     * Get the default promotion body template.
     *
     * @return  string
     *
     * @since   1.3.24
     */
    public static function getDefaultPromotionBodyTemplate(): string
    {
        return <<<TEXT
Hallo {member_firstname},

Goed nieuws: {student_firstname} is van de wachtlijst naar een vaste plaats in "{lesson_name}" verplaatst.

De lessenreeks loopt van {lesson_start_date} tot {lesson_end_date}.

{invoice_hint}

Met vriendelijke groeten,

Het Balancirk team
TEXT;
    }

    /**
     * Default year-start promotion subject.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultYearstartPromotionSubjectTemplate(): string
    {
        return 'Je bent ingeschreven voor "{lesson_name}"';
    }

    /**
     * Default year-start promotion body.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultYearstartPromotionBodyTemplate(): string
    {
        return <<<TEXT
Hallo {member_firstname},

Goed nieuws: {student_firstname} is definitief ingeschreven voor "{lesson_name}".

De lessenreeks loopt van {lesson_start_date} tot {lesson_end_date}.

{invoice_hint}

Met vriendelijke groeten,

Het Balancirk team
TEXT;
    }

    /**
     * Default waitlist rejection subject.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultRejectionSubjectTemplate(): string
    {
        return 'Wachtlijst "{lesson_name}" — geen plaats';
    }

    /**
     * Default waitlist rejection body.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultRejectionBodyTemplate(): string
    {
        return <<<TEXT
Hallo {member_firstname},

Helaas kunnen we {student_firstname} dit schooljaar geen plaats bieden in "{lesson_name}".

De inschrijving op de wachtlijst is daarom verwijderd.

Met vriendelijke groeten,

Het Balancirk team
TEXT;
    }

    /**
     * Default lesson cancellation subject.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultCancellationSubjectTemplate(): string
    {
        return 'Les "{lesson_name}" is afgelast';
    }

    /**
     * Default lesson cancellation body.
     *
     * @return  string
     *
     * @since   1.3.25
     */
    public static function getDefaultCancellationBodyTemplate(): string
    {
        return <<<TEXT
Hallo {member_firstname},

Helaas moeten we je laten weten dat de les "{lesson_name}" voor dit schooljaar is afgelast.

We nemen contact op als er een alternatief is, of je kan ons mailen als je vragen hebt.

Met vriendelijke groeten,

Het Balancirk team
TEXT;
    }

    /**
     * Format a date for mail output.
     *
     * @param   string|null  $date  Raw date string.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    private static function formatDate(?string $date): string
    {
        $dateObject = self::createDate($date);

        return $dateObject ? $dateObject->format('d/m/Y') : '';
    }

    /**
     * Create an immutable date or return null.
     *
     * @param   string|null  $date  Raw date string.
     *
     * @return  DateTimeImmutable|null
     *
     * @since   1.2.20
     */
    private static function createDate(?string $date): ?DateTimeImmutable
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Fallback invoice hint when dates are incomplete.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    private static function getFallbackInvoiceHint(): string
    {
        return self::translate('COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_FALLBACK');
    }

    /**
     * Invoice hint for waiting list mails.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    private static function getWaitingListInvoiceHint(): string
    {
        return self::translate('COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_WAITINGLIST');
    }

    /**
     * Translate a language key, with a small fallback for test environments.
     *
     * @param   string  $key   Language key.
     * @param   mixed   ...$args  Optional sprintf arguments.
     *
     * @return  string
     *
     * @since   1.2.20
     */
    private static function translate(string $key, ...$args): string
    {
        if (class_exists(Text::class)) {
            return $args === [] ? Text::_($key) : Text::sprintf($key, ...$args);
        }

        $fallbacks = [
            'COM_BALANCIRK_SUBJECT_SUBSCRIPTION' => 'Inschrijving les',
            'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_AFTER_START' => 'De lessenreeks is al gestart op %s. De factuur mag je verwachten na deze inschrijving.',
            'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_BEFORE_START' => 'De factuur mag je verwachten tegen de start van de lessenreeks op %s.',
            'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_FALLBACK' => 'Je ontvangt later nog een mail met de betalingsgegevens.',
            'COM_BALANCIRK_SUBSCRIPTION_INVOICE_HINT_WAITINGLIST' => 'Zolang deze inschrijving op de wachtlijst staat, sturen we nog geen factuur.',
        ];
        $text = $fallbacks[$key] ?? $key;

        return $args === [] ? $text : sprintf($text, ...$args);
    }
}
