<?php

namespace Drupal\ifba_import_export_mod\Form;

use Drupal\ifba_import_export_mod\Model\SampleExportHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SampleExportForm
 *  'Controller' class that handles the export form
 *
 * @package Drupal\ifba_import_export_mod\Form
 */
class SampleExportForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ifba_export_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = array(
      '#markup' => '<p>IFBA Sample CSV Export</p>
                    <ul>
                        <h4>Export Options:</h4>
                        <li>Export all</li>
                        <li>Export by Sample Set Container</li>
                    </ul> 
                    ',
    );

    /* Get nodes entities of Sample Set Container for dropdown select/option */
    $dropdown_source = SampleExportHelper::readIfbaContainerData();
    /* Set option at index key 0 */
    $dropdown_array = array( 0 => '-- EXPORT ALL --');
    /* Populate select/option with key (id) and value (title) */
    foreach ($dropdown_source as $item) {
      $key = $item->id();
      $value = $item->getTitle();
      $dropdown_array[$key] = $value;
    }

    /*sort dropdown and maintain key value pairs */
    natsort($dropdown_array);

    $form['container_filter'] = array(
      '#weight' => '1',
      '#key_type' => 'associative',
      '#multiple_toggle' => '1',
      '#type' => 'select',
      '#options' => $dropdown_array,
      '#title' => 'Filter by Sample Set Container',
    );

    $form['actions']['#type'] = 'actions';//this 'trick' ensures bottom position to bottom

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
     /* Get selected option by sample set container id. Default '0' is 'select all'. */
    $container_id = (int) $form_state->getValue('container_filter');
    /* Read sample data by selected container */
    $nodes_result = SampleExportHelper::readIfbaSampleData($container_id);
    /* Creates sample objects from data */
    $samples = SampleExportHelper::createSamplesFromNodes($nodes_result);
    /* Write sample objects to CSV */
    $message = SampleExportHelper::writeSamplesToCSV($samples);

    if($message!=false){
      drupal_set_message(t($message));
      drupal_set_message(t('<a href="/samples_export.csv" download>Download CSV link</a>'), 'status');
    }else{
      drupal_set_message(t('Error writing to CSV file'),'error');
    }

  }
}