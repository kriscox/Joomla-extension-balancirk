<?php

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\EditorField;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Custom field for email body with HTML formatting.
 */
class JFormFieldEmailBody extends EditorField
{
    /**
     * The field type.
     *
     * @var         string
     */
    protected $type = 'EmailBody';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   3.7.0
     */
    protected function getInput()
    {
        // Initialize variables.
        $html   = array();
        $value  = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

        // Get editor options.
        $options = array(
            'name'   => $this->name,
            'value'  => $value,
            'rows'   => 10,
            'cols'   => 60,
            'width'  => '100%',
            'height' => '400',
            'filter' => $this->element['filter'],
            'buttons' => $this->element['buttons'],
            'article_style' => $this->element['article_style'],
            'pagebreak' => $this->element['pagebreak'],
            'context' => $this->form->getName(),
        );

        // Add custom JavaScript.
        HTMLHelper::_('jquery.framework');
        HTMLHelper::_('script', 'system/editor.js', array('version' => 'auto', 'relative' => true));

        // Load the editor.
        $editor = \Joomla\CMS\Editor\Editor::getInstance($this->element['editor']);
        $editor->display($this->name, $value, $this->element['width'], $this->element['height'], $this->element['cols'], $this->element['rows'], false, $this->id, $this->element['options']);

        // Get the output.
        $output = array();
        foreach ($html as $line) {
            $output[] = $line;
        }

        return implode("\n", $output);
    }
}
