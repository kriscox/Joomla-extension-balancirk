<?php

/**
 * @package     Joomla.site
 * @subpackage  com_balancirk
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\Helpers\Tag;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Factory;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$states = array(
	'0' => Text::_('COM_BALANCIRK_LESSON_STATUS_PAST'),
	'1' => Text::_('COM_BALANCIRK_LESSON_STATUS_CURRENT'),
	'2' => Text::_('COM_BALANCIRK_LESSON_STATUS_NEXT'),
	'-2' => Text::_('JTRASHED')
);
$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';

$userid = Factory::getApplication()->getIdentity()->id;
$bearertoken = UserHelper::getProfile($userid)->get('joomlatoken')['token'];
?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-top}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-subscriptions-top}'); ?>
<div class="row">
	<div class="col-md-12">
		<nav aria-label="Toolbar">
			<button class="button-new btn btn-success" type="button" onclick="location.href = 'index.php?option=com_balancirk&view=subscription&id=0';">
				<span class=" icon-new" aria-hidden="true"></span>
				<?= TEXT::_('COM_BALANCIRK_BUTTON_NEW') ?>
			</button>
			<a href="<?= Route::_('index.php?option=com_balancirk&view=students', false); ?>">
				<button class="btn btn-primary" type="button"><?= TEXT::_('COM_BALANCIRK_STUDENTS_LINK') ?></button>
			</a>
		</nav>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div id="j-main-container" class="j-main-container">
			<?= LayoutHelper::render('student.filter', array('view' => $this)); ?>
			<?php if (empty($this->items)) : ?>
				<div class="alert alert-info">
					<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
					<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php else : ?>
				<table class="table" id="subscriptionList">
					<caption id="captionTable">
						<?= Text::_('COM_BALANCIRK_SUBSCRIPTIONS_TABLE_CAPTION'); ?>
					</caption>
					<thead>
						<tr>
							<th style="width:1%" class="d-none text-center">
								<?= HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_STUDENT', 'a.firstname', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_LESSON', 'a.lesson', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_YEAR', 'a.year', $listDirn, $listOrder); ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<span class="fas fa-check fa-xs"> </span><?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_SUBSCRIBED', 'a.subscribed', $listDirn, $listOrder) ?> &#47;
								<span class="fas fa-clock fa-xs"> </span><?= HTMLHelper::_('searchtools.sort', 'COM_BALANCIRK_TABLE_TABLEHEAD_WAITINGLIST', 'a.subscribed', $listDirn, $listOrder) ?>
							</th>
							<th scope="col" class="text_center d-md-table-cell">
								<a href=#> <?= Text::_('COM_BALANCIRK_TABLE_TABLEHEAD_UNSUBSCRIBE'); ?> </a>
							</th>
						</tr>
						<?php $n = count($this->items);

						foreach ($this->items as $i => $item) : ?>
							<tr class="row<?= $i % 2; ?>">
								<td class="text-center d-none">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?= $this->escape(addslashes($item->firstname)); ?> <?= $this->escape(addslashes($item->name)); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?= $this->escape(addslashes($item->lesson)); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?= $this->escape(addslashes($item->year)); ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<?php if ($item->subscribed == 0) : ?>
										<span class="fas fa-check fs-lg"> </span>
									<?php else : ?>
										<span class="fas fa-clock fa-lg"> </span>
									<?php endif; ?>
								</td>
								<td scope="row" class="d-md-table-cell">
									<button class="btn btn-danger btn-sm"
										onclick="showDeleteModal(<?= $item->id; ?>, '<?= $item->firstname; ?> <?= $item->name; ?>', '<?= $item->lesson; ?>')">
										<span class="icon-purge" />
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
				</table>

			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Modal for Delete Confirmation -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteModalLabel"><?= Text::_('COM_BALANCIRK_MODEL_UNSUBSCRIBE_TITLE') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<?= Text::_('COM_BALANCIRK_MODAL_UNSUBSCRIBE_PART1') ?> <span id="Name"></span>
				<?= Text::_('COM_BALANCIRK_MODAL_UNSUBSCRIBE_PART2') ?> <span id="Lesson"></span>
				<?= Text::_('COM_BALANCIRK_MODAL_UNSUBSCRIBE_PART3') ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Text::_('JCANCEL') ?></button>
				<button type="button" class="btn btn-danger" id="confirmDeleteButton"><?= Text::_('JACTION_DELETE') ?></button>
			</div>
		</div>
	</div>
</div>

<?php echo JHtml::_('content.prepare', '{loadposition balancirk-subscriptions-bottom}'); ?>
<?php echo JHtml::_('content.prepare', '{loadposition balancirk-bottom}'); ?>

<!-- JavaScript for handling delete logic -->
<script>
	let subscriptionIdToDelete = null;

	function showDeleteModal(subscriptionId, student, lesson) {
		// Set the subscription ID to a global variable to be used in the delete function
		subscriptionIdToDelete = subscriptionId;

		// Set the subscription name in the modal
		document.getElementById('Name').innerText = student;
		document.getElementById('Lesson').innerText = lesson;

		// Show the Bootstrap modal
		let deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'), {});
		deleteModal.show();
	}

	document.getElementById('confirmDeleteButton').addEventListener('click', function() {
		if (subscriptionIdToDelete !== null) {
			// Send the DELETE request to the API endpoint
			fetch(`/api/index.php/v1/subscription/${subscriptionIdToDelete}`, {
					method: 'DELETE',
					headers: {
						'Content-Type': 'application/json',
						'Authorization': 'Bearer <?= $bearertoken; ?>'
					},
				})
				.then(response => {
					if (response.ok) {
						// Successfully deleted, reload the page
						location.reload();
					} else {
						alert('Failed to delete subscription. Please try again.');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('An error occurred while trying to delete the subscription.');
				});
		}
	});
</script>