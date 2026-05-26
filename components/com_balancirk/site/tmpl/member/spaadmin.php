<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;

$app = Factory::getApplication();
$document = $app->getDocument();
$wa = $document->getWebAssetManager();
$assetBase = 'media/com_balancirk/member-spa/browser/';
$mainAssetPath = JPATH_ROOT . '/' . $assetBase . 'main.js';
$manifestUrl = Uri::root() . $assetBase . 'manifest.webmanifest';
$user = $app->getIdentity();
$apiToken = '';
$canViewRelations = false;
$canExportAccounting = false;
$canAdminPortal = false;

if (!$user->guest)
{
	$profile = UserHelper::getProfile((int) $user->id);
	$apiToken = (string) ($profile->get('joomlatoken')['token'] ?? '');
	$canViewRelations = $user->authorise('accounting.viewrelations', 'com_balancirk')
		|| $user->authorise('students.viewall', 'com_balancirk')
		|| $user->authorise('lessons.admin', 'com_balancirk')
		|| $user->authorise('core.admin', 'com_balancirk');
	$canExportAccounting = $user->authorise('accounting.export', 'com_balancirk')
		|| $user->authorise('core.admin', 'com_balancirk');
	$canAdminPortal = $canViewRelations
		|| $canExportAccounting
		|| $user->authorise('core.manage', 'com_balancirk')
		|| $user->authorise('students.viewall', 'com_balancirk')
		|| $user->authorise('lessons.admin', 'com_balancirk');
}
?>

<div class="page-header">
	<h1><?= $this->item->title; ?></h1>
</div>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-top}'); ?>

<?php if ($user->guest || !$canAdminPortal): ?>
	<div class="alert alert-danger">
		Je hebt geen toegang tot de adminvariant van de leden-app.
	</div>
<?php elseif (is_file($mainAssetPath)): ?>
	<?php
	$wa->registerAndUseStyle('balancirk-member-spa-styles', $assetBase . 'styles.css');
	$wa->registerAndUseScript('balancirk-member-spa-polyfills', $assetBase . 'polyfills.js', [], ['type' => 'module']);
	$wa->registerAndUseScript('balancirk-member-spa-main', $assetBase . 'main.js', [], ['type' => 'module']);
	$document->addScriptOptions('balancirk-member-spa', [
		'token' => $apiToken,
		'apiBase' => '/api/index.php/v1',
		'subscriptionCreateUrl' => Route::_('index.php?option=com_balancirk&view=subscription&id=0', false),
		'portalMode' => 'staff',
		'canAdminPortal' => $canAdminPortal,
		'canViewRelations' => $canViewRelations,
		'canExportAccounting' => $canExportAccounting,
		'adminVariant' => true,
	]);
	$document->addHeadLink($manifestUrl, 'manifest', 'rel');
	?>
	<app-member-root></app-member-root>
<?php else: ?>
	<div class="alert alert-warning">
		De leden-app is nog niet gebouwd. Build en deploy eerst met:
		<code>cd frontend/member-spa &amp;&amp; npm run build:deploy</code>
	</div>
<?php endif; ?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
