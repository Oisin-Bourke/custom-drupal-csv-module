<?php

namespace Drupal\ifba_import_export_mod\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ifba_import_export_mod\Model\SampleImportHelper;
use Drupal\file\Entity\File;


/**
 * Class SampleImportForm
 *  'Controller' class that handles the import form
 *
 * @package Drupal\ifba_import_export_mod\Form
 */
class SampleImportForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ifba_import_form';
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
      '#markup' => '<p>IFBA Container Sample CSV Upload</p>
                    <ul>
                        <h4>Ensure the following:</h4>
                        <li>A valid CSV file</li>
                        <li>The sample set container has been created</li>
                        <li>The taxonomy terms are correct</li>
                    </ul> 
                    ',
    );

    $form['import_csv'] = array(
      '#type' => 'managed_file',
      '#title' => t('Upload file here'),
      '#upload_location' => 'public://data/',
      '#default_value' => '',
      "#upload_validators" => array("file_validate_extensions" => array("csv")),
      '#states' => array(
        'visible' => array(
          ':input[name="File_type"]' => array('value' => t('Upload CSV File')),
        ),
      ),
    );

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Ensure a file has been selected for upload
   *
   * @param array $form
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('import_csv')== null) {
      $form_state->setErrorByName('import_csv', $this->t('No file selected'));
    }
  }

  /**
   * Form submission handler.
   *
   * Get uploaded file, read contents of file, write to Drupal database.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @EntityStorageException/Exception
   *  Catching any MySQL type errors writing to Drupal database
   *
   * @Exception
   *  Catch any custom exception messages from functions called
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   try{
      /* Fetch the array of the file stored temporarily in DB */
      $csv_file = $form_state->getValue('import_csv');
      /* Load the object of the file by it's fid */
      $file = File::load($csv_file[0]);
      /* Set the status flag permanent of the file object */
      $file->setPermanent();
      /* Save the file in the DB */
      $file->save();
      /* Read the data from the CSV file */
      $data = SampleImportHelper::readCSVFile($file->getFileUri(),SampleImportHelper::$requiredHeader);
      if($data == false){
        drupal_set_message(t('File does not exist or is unreadable.'),'error');
      }else {
        /* Import data into Drupal database */
        $message = SampleImportHelper::createEntitiesFromData($data);
        drupal_set_message(t($message));
      }
    }catch (EntityStorageException $e){
      drupal_set_message(t($e->getMessage()),'error');
    }catch (\Exception $e){
      drupal_set_message(t($e->getMessage()),'error');
    }
  }
}