<?php

namespace CoCoCo\Component\Balancirk\Api\Controller;

\defined('_JEXEC') or die;

use CoCoCo\Component\Balancirk\Site\Helper\MutualityOptionsHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * API controller for component settings needed by frontend clients.
 *
 * @since  1.2.29
 */
class SettingsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  1.2.29
     */
    protected $contentType = 'settings';

    /**
     * The default view for display.
     *
     * @var    string
     * @since  1.2.29
     */
    protected $default_view = 'display';

    /**
     * Get full component settings for admin clients.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function getsettings(): void
    {
        $app = Factory::getApplication();

        if (!$this->canReadSettings()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        echo new JsonResponse($this->buildSettings(true));
        $app->close();
    }

    /**
     * Get public frontend settings.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function getpublicsettings(): void
    {
        $app = Factory::getApplication();
        echo new JsonResponse($this->buildSettings(false));
        $app->close();
    }

    /**
     * Save editable component settings.
     *
     * @return  void
     *
     * @since   1.2.29
     */
    public function savesettings(): void
    {
        $app = Factory::getApplication();

        if (!$this->canWriteSettings()) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), true);
            $app->close();
        }

        $changes = $this->normalizeSettingsInput($this->getRequestData());

        if (empty($changes)) {
            echo new JsonResponse(null, Text::_('JLIB_APPLICATION_ERROR_SAVE_FAILED'), true);
            $app->close();
        }

        $component = ComponentHelper::getComponent('com_balancirk');
        $params = new Registry((string) $component->params);

        foreach ($changes as $key => $value)
        {
            $params->set($key, $value);
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = ' . $db->quote((string) $params))
            ->where($db->quoteName('extension_id') . ' = ' . (int) $component->id);
        $db->setQuery($query)->execute();

        echo new JsonResponse($this->buildSettings(true, $params));
        $app->close();
    }

    /**
     * Build the settings payload.
     *
     * @param   bool            $includeSensitive  Include mail templates and redirect id.
     * @param   Registry|null   $params            Optional params registry.
     *
     * @return  array
     *
     * @since   1.2.29
     */
    private function buildSettings(bool $includeSensitive, ?Registry $params = null): array
    {
        $params = $params ?? ComponentHelper::getParams('com_balancirk');
        $mutualityRaw = (string) $params->get('mutuality_options', '');

        $data = [
            'mutuality_options' => $mutualityRaw,
            'mutuality_list' => MutualityOptionsHelper::getOptions($mutualityRaw),
        ];

        if ($includeSensitive) {
            $data['email_subject_subscription'] = (string) $params->get('email_subject_subscription', '');
            $data['email_body_subscription'] = (string) $params->get('email_body_subscription', '');
            $data['email_subject_waitinglist'] = (string) $params->get('email_subject_waitinglist', '');
            $data['email_body_waitinglist'] = (string) $params->get('email_body_waitinglist', '');
            $data['redirect_url'] = (int) $params->get('redirect_url', 0);
        }

        return $data;
    }

    /**
     * Normalize incoming settings payload to allowed keys.
     *
     * @param   array  $data  Request payload.
     *
     * @return  array
     *
     * @since   1.2.29
     */
    private function normalizeSettingsInput(array $data): array
    {
        $changes = [];
        $stringKeys = [
            'email_subject_subscription',
            'email_body_subscription',
            'email_subject_waitinglist',
            'email_body_waitinglist',
            'mutuality_options',
        ];

        foreach ($stringKeys as $key)
        {
            if (array_key_exists($key, $data)) {
                $changes[$key] = (string) $data[$key];
            }
        }

        if (!array_key_exists('mutuality_options', $changes) && array_key_exists('mutuality_list', $data) && \is_array($data['mutuality_list'])) {
            $values = array_values(
                array_filter(
                    array_map(static fn($item): string => trim((string) $item), $data['mutuality_list']),
                    static fn(string $item): bool => $item !== ''
                )
            );
            $changes['mutuality_options'] = implode("\n", array_unique($values));
        }

        if (array_key_exists('redirect_url', $data)) {
            $changes['redirect_url'] = max(0, (int) $data['redirect_url']);
        }

        return $changes;
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
     * Check read permissions for private settings.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canReadSettings(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && (
            $user->authorise('core.manage', 'com_balancirk')
            || $user->authorise('core.admin', 'com_balancirk')
        );
    }

    /**
     * Check write permissions for settings updates.
     *
     * @return  bool
     *
     * @since   1.2.29
     */
    private function canWriteSettings(): bool
    {
        $user = Factory::getApplication()->getIdentity();

        return !$user->guest && $user->authorise('core.admin', 'com_balancirk');
    }
}

