<?php

// No direct access
 defined('_JEXEC') or die('Restricted access');

 namespace CoCoCo\Component\Balancirk\Site\Model;

 /**
 * @package     Joomla.Administrator
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

/**
 * Studen Table class
 *
 * @since  0.0.1
 */
class BalancirkStudentTable extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  A database connector object
	 */
	function __construct(&$db)
	{
		parent::__construct('#__Balancirk_Student', 'id', $db);
	}
}