<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk§
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\MVC\Model\BaseDatabaseModel as ModelLegacy;

/**
 * Script file of Balancirk Component
 *
 * @since  0.0.1
 */
class Com_BalancirkInstallerScript extends InstallerScript
{
    /**
     * Minimum Joomla version to check
     *
     * @var    string
     * @since  0.0.1
     */
    private $minimumJoomlaVersion = '4.0';

    /**
     * Minimum PHP version to check
     *
     * @var    string
     * @since  0.0.1
     */
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;

    /**
     * Method to install the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since  0.0.1
     */
    public function install($parent): bool
    {
        echo Text::_('COM_BALANCIRK_INSTALLERSCRIPT_INSTALL');

        $this->ensureDefaultGroupsAndPermissions();

        return true;
    }

    /**
     * Method to uninstall the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since  0.0.1
     */
    public function uninstall($parent): bool
    {
        echo Text::_('COM_BALANCIRK_INSTALLERSCRIPT_UNINSTALL');

        return true;
    }

    /**
     * Method to update the extension
     *
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since  0.0.1
     *
     */
    public function update($parent): bool
    {
        echo Text::_('COM_BALANCIRK_INSTALLERSCRIPT_UPDATE');

        $this->ensureDefaultGroupsAndPermissions();

        return true;
    }

    /**
     * Function called before extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since  0.0.1
     *
     * @throws Exception
     */
    public function preflight($type, $parent): bool
    {
        //echo Text::_('COM_BALANCIRK_INSTALLERSCRIPT_PREFLIGHT');

        if ($type !== 'uninstall')
        {
            // Check for the minimum PHP version before continuing
            if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<'))
            {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }

            // Check for the minimum Joomla version before continuing
            if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<'))
            {
                Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                    Log::WARNING,
                    'jerror'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal procedure commences
     *
     * @param   string            $type    The type of change (install, update or discover_install, not uninstall)
     * @param   InstallerAdapter  $parent  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since  0.0.1
     *
     */
    public function postflight($type, $parent)
    {
        //echo Text::_('COM_BALANCIRK_INSTALLERSCRIPT_POSTFLIGHT');

        // Do not run on uninstall.
        if ($type !== 'uninstall')
        {
            $this->conditionalInstallDashboard('com-balancirk-dashboard', 'balancirk');
            $this->ensureDefaultGroupsAndPermissions();
        }

        return true;
    }

    /**
     * Conditional add dashboard to backend
     *
     * Add our dahsboard to the back-end and config it
     *
     * @param   string $dashboard	dashboard description
     * @param	string $preset		Name of the preset
     * @return  void
     * @throws  conditon
     **/
    private function conditionalInstallDashboard(string $dashboard, string $preset): void
    {
        $position = 'cpanel-' . $dashboard;

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__modules'))
            ->where(
                [
                    $db->quoteName('module') . ' = ' . $db->quote('mod_submenu'),
                    $db->quoteName('client_id') . ' = ' . $db->quote(1),
                    $db->quoteName('position') . ' = :position',
                ]
            )
            ->bind(':position', $position);

        $modules = $db->setQuery($query)->loadResult() ?: 0;

        if ($modules == 0)
        {
            $this->addDashboardMenu($dashboard, $preset);
        }
    }

    /**
     * Create teacher user group
     *
     * Create teacher user group to be used to identify who could be teacher

     * @return boolean	True is succesfull, false otherwise
     * @throws conditon
     **/
    public function addTeacherGroup()
    {
        return $this->ensureUserGroup('Teachers');
    }

    /**
     * Create bookkeeper user group.
     *
     * Create bookkeeper user group to be used to identify who can export and consult relations.
     *
     * @return integer|false  Group id on success, false otherwise.
     *
     * @since   1.2.22
     */
    public function addBookkeeperGroup()
    {
        return $this->ensureUserGroup('Bookkeepers');
    }

    /**
     * Ensure the default Balancirk groups and permissions exist.
     *
     * @return  void
     *
     * @since   1.2.22
     */
    private function ensureDefaultGroupsAndPermissions(): void
    {
        $this->addTeacherGroup();

        $bookkeeperGroupId = $this->addBookkeeperGroup();

        if ($bookkeeperGroupId !== false)
        {
            $this->setComponentPermissions(
                (int) $bookkeeperGroupId,
                [
                    'core.manage' => true,
                    'accounting.viewrelations' => true,
                    'accounting.export' => true,
                ]
            );
        }
    }

    /**
     * Ensure a Joomla user group exists.
     *
     * @param   string  $title     Group title.
     * @param   int     $parentId  Parent group id.
     *
     * @return  integer|false  Group id on success, false otherwise.
     *
     * @since   1.2.22
     */
    private function ensureUserGroup(string $title, int $parentId = 2)
    {
        $groupId = $this->getUserGroupId($title);

        if ($groupId !== null)
        {
            return $groupId;
        }

        $group = array('id' => 0, 'title' => $title, 'parent_id' => $parentId);
        ModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models');

        /** @var \Joomla\Component\Users\Administrator\Model\GroupModel $groupModel */
        $groupModel = ModelLegacy::getInstance('Group', 'UsersModel');

        if (!$groupModel)
        {
            return false;
        }

        if (!$groupModel->save($group))
        {
            Factory::getApplication()->enqueueMessage($groupModel->getError());

            return false;
        }

        return $this->getUserGroupId($title) ?: false;
    }

    /**
     * Get a Joomla user group id by title.
     *
     * @param   string  $title  Group title.
     *
     * @return  integer|null
     *
     * @since   1.2.22
     */
    private function getUserGroupId(string $title): ?int
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($title));

        $db->setQuery($query);
        $result = $db->loadResult();

        return $result !== null ? (int) $result : null;
    }

    /**
     * Set component permissions for a user group.
     *
     * @param   int    $groupId       Joomla user group id.
     * @param   array  $permissions   Permission map.
     *
     * @return  void
     *
     * @since   1.2.22
     */
    private function setComponentPermissions(int $groupId, array $permissions): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'rules']))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('name') . ' = ' . $db->quote('com_balancirk'));

        $db->setQuery($query);
        $asset = $db->loadAssoc();

        if (!$asset)
        {
            return;
        }

        $rules = json_decode($asset['rules'] ?? '{}', true);

        if (!is_array($rules))
        {
            $rules = [];
        }

        foreach ($permissions as $action => $value)
        {
            if (!isset($rules[$action]) || !is_array($rules[$action]))
            {
                $rules[$action] = [];
            }

            $rules[$action][(string) $groupId] = $value ? 1 : 0;
        }

        $update = $db->getQuery(true)
            ->update($db->quoteName('#__assets'))
            ->set($db->quoteName('rules') . ' = ' . $db->quote(json_encode($rules)))
            ->where($db->quoteName('id') . ' = ' . (int) $asset['id']);

        $db->setQuery($update)->execute();
    }

    public function addHiddenMenu()
    {

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu_types'))
            ->where($db->quoteName('menutype') . ' = ' . $db->quote('hiddenmenu'));

        $db->setQuery($query);
        $existing = $db->loadResult();

        if ($existing)
        {
            return; // Already exists
        }

        //Load the menu type table
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables');
        $menuType = Table::getInstance('MenuType', 'MenusTable');

        $menuType->menutype = 'hiddenmenu';
        $menuType->title = 'Hidden Menu';
        $menuType->description = 'A menu that is hidden from the frontend view and used for selecting pages in the backend.';
        $menuType->client_id = 0;

        if (!$menuType->store())
        {
            Log::add(Text::_('COM_BALANCIRK_INSTALLERSCRIPT_ADD_HIDDEN_MENU_ERROR'), Log::ERROR, 'jerror');
            return;
        }
    }
}
