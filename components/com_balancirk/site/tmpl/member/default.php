<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_balancirk
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-top}'); ?>

<div class="balancirk-member-dashboard">
    <h2><?= Text::_('COM_BALANCIRK_MEMBER_DASHBOARD_TITLE') ?: 'Mijn Balancirk'; ?></h2>

    <div class="btn-toolbar mb-3" role="toolbar">
        <a class="btn btn-primary me-2" href="<?= Route::_('index.php?option=com_balancirk&view=student&layout=edit&id=0'); ?>">Nieuw kind toevoegen</a>
        <a class="btn btn-secondary me-2" href="<?= Route::_('index.php?option=com_balancirk&view=subscriptions'); ?>">Inschrijvingen beheren</a>
        <a class="btn btn-outline-primary" href="<?= Route::_('index.php?option=com_balancirk&view=member&layout=edit'); ?>">Profiel aanpassen</a>
    </div>

    <h3>Mijn kinderen</h3>
    <?php if (empty($this->students)) : ?>
        <div class="alert alert-info">Nog geen kinderen gekoppeld aan je account.</div>
    <?php else : ?>
        <table class="table table-striped">
            <thead><tr><th>Naam</th><th>Geboortedatum</th><th>Acties</th></tr></thead>
            <tbody>
            <?php foreach ($this->students as $student) : ?>
                <tr>
                    <td><?= $this->escape($student->firstname . ' ' . $student->name); ?></td>
                    <td><?= $this->escape($student->birthdate); ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= Route::_('index.php?option=com_balancirk&task=student.edit&id=' . (int) $student->id); ?>">Aanpassen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Mijn inschrijvingen</h3>
    <form method="get" class="mb-3">
        <input type="hidden" name="option" value="com_balancirk">
        <input type="hidden" name="view" value="member">
        <label for="filter_year" class="form-label">Schooljaar</label>
        <select id="filter_year" name="filter_year" class="form-select" onchange="this.form.submit()">
            <?php foreach ($this->years as $year) : ?>
                <option value="<?= $this->escape($year); ?>" <?= ((string) $this->selectedYear === (string) $year) ? 'selected="selected"' : ''; ?>><?= $this->escape($year); ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (empty($this->subscriptions)) : ?>
        <div class="alert alert-info">Nog geen inschrijvingen gevonden.</div>
    <?php else : ?>
        <table class="table table-striped">
            <thead><tr><th>Kind</th><th>Les</th><th>Jaar</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($this->subscriptions as $subscription) : ?>
                <tr>
                    <td><?= $this->escape($subscription->firstname . ' ' . $subscription->name); ?></td>
                    <td><?= $this->escape($subscription->lesson); ?></td>
                    <td><?= $this->escape($subscription->year); ?></td>
                    <td><?= ((int) $subscription->subscribed === 0) ? 'Ingeschreven' : 'Wachtlijst'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-member-bottom}'); ?>
<?php echo HTMLHelper::_('content.prepare', '{loadposition balancirk-bottom}'); ?>
