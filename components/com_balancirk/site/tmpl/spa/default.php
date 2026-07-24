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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;

$app = Factory::getApplication();
$document = $app->getDocument();
$wa = $document->getWebAssetManager();
$assetBase = 'media/com_balancirk/spa/browser/';
$mainAssetPath = JPATH_ROOT . '/' . $assetBase . 'main.js';
$manifestUrl = Uri::root() . $assetBase . 'manifest.webmanifest';
$user = $app->getIdentity();
$params = ComponentHelper::getParams('com_balancirk');

$apiToken = '';
$canViewLessons = false;
$canViewRelations = false;
$canExportAccounting = false;
$canAdminPortal = false;

$return = base64_encode(Uri::getInstance()->toString());
$loginUrl = Route::_('index.php?option=com_users&view=login&return=' . $return, false);
$logoutUrl = Route::_(
    'index.php?option=com_users&task=user.logout&return=' . $return . '&' . Session::getFormToken() . '=1',
    false
);
$passwordResetUrl = Route::_('index.php?option=com_users&view=reset', false);

$newsletterItemId = (int) $params->get('newsletter_menu_item', 0);
$newsletterUrl = $newsletterItemId > 0
    ? Route::_('index.php?Itemid=' . $newsletterItemId, false)
    : '';

if (!$user->guest) {
    $profile = UserHelper::getProfile((int) $user->id);
    $apiToken = (string) ($profile->get('joomlatoken')['token'] ?? '');
    $canViewLessons = $user->authorise('lessons.view', 'com_balancirk');
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

$portalMode = ($canViewLessons || $canAdminPortal || $canExportAccounting) ? 'staff' : 'member';
$pageTitle = isset($this->item->title) ? (string) $this->item->title : 'Balancirk';
?>

<div class="page-header">
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-spa-top}'); ?>

<?php if (is_file($mainAssetPath)) : ?>
    <?php
    $wa->registerAndUseStyle('balancirk-spa-styles', $assetBase . 'styles.css');
    $wa->registerAndUseScript('balancirk-spa-polyfills', $assetBase . 'polyfills.js', [], ['type' => 'module']);
    $wa->registerAndUseScript('balancirk-spa-main', $assetBase . 'main.js', [], ['type' => 'module']);
    $document->addScriptOptions('balancirk-spa', [
        'token' => $apiToken,
        'apiBase' => '/api/index.php/v1',
        'subscriptionCreateUrl' => Route::_('index.php?option=com_balancirk&view=subscription&id=0', false),
        'portalMode' => $portalMode,
        'isGuest' => (bool) $user->guest,
        'canViewLessons' => $canViewLessons,
        'canAdminPortal' => $canAdminPortal,
        'canViewRelations' => $canViewRelations,
        'canExportAccounting' => $canExportAccounting,
        'loginUrl' => $loginUrl,
        'logoutUrl' => $logoutUrl,
        'passwordResetUrl' => $passwordResetUrl,
        'newsletterUrl' => $newsletterUrl,
        'userName' => $user->guest ? '' : (string) $user->name,
    ]);
    $document->addHeadLink($manifestUrl, 'manifest', 'rel');
    $document->setMetaData('theme-color', '#0d5e56');
    ?>
    <app-root></app-root>
<?php else : ?>
    <div class="alert alert-warning">
        The Balancirk SPA is not built yet. Build and deploy with:
        <code>cd frontend/spa &amp;&amp; npm run build:deploy</code>
    </div>
<?php endif; ?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-spa-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
