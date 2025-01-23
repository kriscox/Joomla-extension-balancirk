<?php

/**
 * @package	 Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license	 GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Site\Model;

\defined('_JEXEC') or die;

use JConfig;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Application\ApplicationHelper;

/**
 * Member model for the Joomla Balancirk component.
 *
 * @since  0.0.1
 */
class MemberModel extends AdminModel
{
    // TODO: Add AdminModel functionalities update, read, write + from

    /**
     * The type alias for this content type.
     *
     * @var	string
     * @since  0.0.1
     */
    public $typeAlias = 'com_balancirk.member';

    /**
     * The prefix to use with controller messages.
     *
     * @var	string
     * @since  0.0.1
     */
    protected $textPrefix = 'COM_BALANCIRK';

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canDelete($record)
    {
        if (!empty($record->id)) {
            $app = Factory::getApplication();

            return $app->getIdentity()->authorise('core.delete', 'com_balancirk.members.' . (int) $record->id);
        }

        return false;
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
     *
     * @since   0.0.1
     */
    protected function canEditState($record)
    {
        $user = Factory::getApplication()->getIdentity();

        // Check for existing article.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_balancirk.members.' . (int) $record->id);
        }

        // Default to component settings if neither article nor category known.
        return parent::canEditState($record);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name	  The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   0.0.1
     * @throws  \Exception
     */
    public function getTable($name = '', $prefix = '', $options = array())
    {
        $name = 'members';
        $prefix = 'Table';

        if ($table = $this->_createTable($name, $prefix, $options)) {
            return $table;
        }

        throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name), 0);
    }

    /**
     * Method to get the row form.
     *
     * @param	array   $data	    Data from the form.
     * @param	boolean $loadData   True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     *
     * @since   0.0.1
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm($this->typeAlias, 'member', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since   0.0.1
     */
    protected function loadFormData()
    {
        /** @var  SiteApplication*/
        $app = Factory::getApplication();
        $data = $app->getUserState('com_balancirk.member.data', array());

        if (empty($data)) {
            $data = $this->getItem($app->getIdentity()->id);

            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
        }

        $this->preprocessData($this->typeAlias, $data);

        return $data;
    }

    /**
     * Register user and save additional fields
     *
     * The user is register in Joomla and the additional fields are added using the view specially
     * created for this purpose
     *
     * @param   array					$data 	form data
     * @return  boolean
     * @throws  \InvalidArgmentException 	if userdata format is fault
     * @throws  \UnexcpectedValueException 	if userdata is fault
     * @throws  \RuntimeException 			if saving does not works
     *
     * @since   0.0.1
     **/
    public function register(array $data)
    {
        // TODO: check the activation of the user. Redicect page, mail send and ...

        $hash = ApplicationHelper::getHash(UserHelper::genRandomPassword());
        $data['activation'] = $hash;
        $data['block'] = 1;

        // TODO get the default user group. now it's fixed to Registered
        $data['groups'] = array(2);

        $app = Factory::getApplication();
        $user = new User();

        // Throws \InvalidArgumentException, \UnexpectedValueException
        if (!$user->bind($data)) {
            $app->enqueueMessage(Text::_("COM_BALANCIRK_USER_ERROR") . $user->getError(), 'error');

            return false;
        }

        // Throws \RuntimeException
        if (!$user->save()) {
            $app->enqueueMessage(Text::_("COM_BALANCIRK_USER_ERROR") . $user->getError(), 'error');

            return false;
        }

        // Fetch created userid.
        $id = $user->id;

        // Fill extra information in table
        $db = $this->getDatabase();

        // Define columns and their values
        $columns = array('id', 'firstname', 'street', 'number', 'bus', 'postcode', 'city', 'phone');
        $values = array(
            $id,
            $data['firstname'],
            $data['street'],
            $data['number'],
            $data['bus'],
            $data['postcode'],
            $data['city'],
            $data['phone']
        );

        // Create query and don't forget to quote everything
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__balancirk_members_additional'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', array_map(fn ($n) => $db->quote($n), $values)));

        // Execute query
        $db->setQuery($query);
        $db->execute();

        // Send activation mail
        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

        // Set the sender
        $config = Factory::getApplication()->get('config');
        $sender = array(
            $config->get('mailfrom'),
            $config->get('fromname')
        );

        // Get the activation url
        $linkMode = $config->force_ssl == 2 ? 1 : 0;
        $activationUrl = Route::link(
            'site',
            'index.php?option=com_users&task=registration.activate&token=' . $hash,
            false,
            $linkMode,
            true
        );

        // TODO Message configureerbaar maken in de backend
        // Set the mailbody
        $message = "
		Hallo {$data['firstname']},

		Bedankt om je te registreren.

		Om je account te kunnen gebruiken moet je deze nog activeren via deze link: {$activationUrl}

		Met vriendelijke circusgroeten,

		Rudi en Kris
		";

        // Set the Recipient
        $send = $mailer->addRecipient($data['email'])
            ->setSender($sender)
            ->setSubject("Welkom bij balancirk")
            ->setBody($message)
            ->Send();

        if ($send != true) {
            $app->enqueueMessage(Text::_("COM_BALANCIRK_USER_ERROR") . 'Error sending email', 'error');

            return false;
        }

        return true;
    }
}
