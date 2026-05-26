<?php

/**
 * @package     Joomla.API
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Api\Controller;

\defined('_JEXEC') or die;

use DateTimeImmutable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\DatabaseInterface;

/**
 * undocumented class
 */
class PresencesController extends ApiController
{
    protected $contentType = 'presences'; /* My understanding is that this maps to the desired model name */
    protected $default_view = 'presences'; /* This maps to the folder name containing the JSON API view */

    /**
     * Get present student ids for a lesson/date.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function getpresence(): void
    {
        $app = Factory::getApplication();

        if (!$this->canRead()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $lesson = $this->input->getInt('lesson');
        $date = $this->normalizeDate($this->input->getString('date'));

        if ($lesson <= 0) {
            echo new JsonResponse(null, Text::_('JGLOBAL_FIELD_ID_NOT_VALID'), true);
            $app->close();
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('student'))
            ->from($db->quoteName('#__balancirk_presences'))
            ->where($db->quoteName('lesson') . ' = ' . (int) $lesson)
            ->where($db->quoteName('date') . ' = ' . $db->quote($date))
            ->order($db->quoteName('student') . ' ASC');
        $db->setQuery($query);
        $students = array_map('intval', $db->loadColumn() ?: []);

        echo new JsonResponse([
            'lesson' => (int) $lesson,
            'date' => $date,
            'students' => $students,
        ]);
        $app->close();
    }

    /**
     * Save present student ids for a lesson/date.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function setpresence(): void
    {
        $app = Factory::getApplication();

        if (!$this->canWrite()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $payload = $this->getRequestData();
        $lesson = $this->input->getInt('lesson', (int) ($payload['lesson'] ?? 0));
        $date = $this->normalizeDate((string) ($payload['date'] ?? $this->input->getString('date')));
        $students = isset($payload['students']) && \is_array($payload['students']) ? $payload['students'] : [];
        $students = array_values(array_unique(array_filter(array_map('intval', $students), static fn(int $id): bool => $id > 0)));

        if ($lesson <= 0) {
            echo new JsonResponse(null, Text::_('JGLOBAL_FIELD_ID_NOT_VALID'), true);
            $app->close();
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__balancirk_presences'))
            ->where($db->quoteName('lesson') . ' = ' . (int) $lesson)
            ->where($db->quoteName('date') . ' = ' . $db->quote($date));
        $db->setQuery($query)->execute();

        foreach ($students as $student)
        {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__balancirk_presences'))
                ->columns($db->quoteName(['lesson', 'student', 'date']))
                ->values((int) $lesson . ', ' . (int) $student . ', ' . $db->quote($date));
            $db->setQuery($query)->execute();
        }

        echo new JsonResponse([
            'lesson' => (int) $lesson,
            'date' => $date,
            'students' => $students,
            'updated' => true,
        ]);
        $app->close();
    }

    /**
     * Decode request payload and support JSON:API format.
     *
     * @return  array
     *
     * @since   1.2.29
     */
    private function getRequestData(): array
    {
        $payload = (array) json_decode((string) $this->input->json->getRaw(), true);

        if (isset($payload['data']) && \is_array($payload['data'])) {
            $payloadData = $payload['data'];

            return isset($payloadData['attributes']) && \is_array($payloadData['attributes'])
                ? $payloadData['attributes']
                : [];
        }

        return $payload;
    }

    /**
     * Check read permissions for attendance endpoints.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canRead(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && $user->authorise('lessons.view', 'com_balancirk');
    }

    /**
     * Check write permissions for attendance endpoints.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canWrite(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && $user->authorise('lessons.admin', 'com_balancirk');
    }

    /**
     * Normalize date input to Y-m-d.
     *
     * @param   string|null  $date  Input date.
     *
     * @return  string
     *
     * @since   1.2.29
     */
    private function normalizeDate(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return date('Y-m-d');
        }

        $normalized = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($normalized instanceof DateTimeImmutable) {
            return $normalized->format('Y-m-d');
        }

        return date('Y-m-d');
    }
}
