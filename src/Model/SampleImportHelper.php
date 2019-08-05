<?php

namespace Drupal\ifba_import_export_mod\Model;

use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityStorageException;

class SampleImportHelper {

  /**
   * @var array
   *  The required CSV header for imports
   */
  public static $requiredHeader = array('Label(sample id)','Approximate Sample Amount','Archivist Comments','Contained In','Date Collected','Date Mask','Fate', 'Field Comments',
                          'Fish Length','Fish Origin','Fish Tag Type','Fish Tag ID','Fish Weight','Sex','Sex Determination Method','Geographic Feature','Is the sample in the IFBA Time Series table?',
                          'Lab Number', 'Life Stage','Maturity','Maturity Determination Method','Representative Sample?','Species');

  /**
   * Read CSV file for data import
   *
   * @param $fileName
   *
   * @param $requiredHeader
   *
   * @return array|bool
   *  Array of associate arrays or false if error
   *
   * @throws \Exception
   *  Throw exception if header does not match required header and inform user
   */
  public static function readCSVFile($fileName,$requiredHeader){

    $delimiter = ',';

    if(!file_exists($fileName) || !is_readable($fileName))
      return false;

    $header = null;
    $data = array();

    if (($handle = fopen($fileName, 'r')) !== false)
    {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
      {
        if(!$header)
          $header = $row;
        else
          $data[] = array_combine($header, $row);
      }
      fclose($handle);
    }
    /* Check for difference between read header and required header */
    $difference = array_diff($header,$requiredHeader);
    /*Throw exception and inform user of first field error mismatch */
    if(!empty($difference)){
      $error = '';
      foreach ($difference as $field => $value){
        $error = $value;
        break;
      }
      throw new \Exception('Error.  Field not matching. Check field:  '.$error);
    }
    return $data;// array of associate arrays
  }

  /**
   * Create new entities of type 'sample' from associate array data
   *
   * @param $data
   *  The data read from the CSV file. An array of associate arrays.
   *
   * @safetyDeleteStorage array
   *  An array to hold sample objects in case of exception error -> retroactively delete all records.
   *
   * @return string
   *  If all entities crated return simple message for user
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *  MySQL type errors saving to Drupal database (@catch on form submit)
   *
   * @throws \Exception
   *  If any processing methods throw an exception:
   *    1) field mismatch
   *    2) duplicate Sample ID (title/PK)
   *    3) taxonomy term mismatch
   *    4) node reference term mismatch
   *  Inform user and abort import process (@catch on form submit)
   */
  public static function createEntitiesFromData($data) {
    /* An array to hold the entity sample objects for deletion if any exceptions encountered */
    $safetyDeleteStorage = [];

    $rowCount = 2;//first row of excel data

      foreach ($data as $array) {
        try {
          /* Create node and then set fields programmatically */
          $sample = Node::create(['type' => 'irish_fish_biochronology_archive']);

          /*Check for duplicate entry by 'Sample ID' (title/PK) */
          $sample_ID = $array['Label(sample id)'];
          self::checkForDuplicatePK($rowCount,'irish_fish_biochronology_archive',$sample_ID);
          $sample->set('title', $sample_ID);

          //value
          $sample->set('field_approx_sample_amount', $array['Approximate Sample Amount']);
          //value
          $sample->set('field_archivist_comments', $array['Archivist Comments']);
          //node ref
          $target_id = self::turnValueToNodeTid($sample_ID,$rowCount,'ifba_archive_container', $array['Contained In']);
          $sample->set('field_contained_in', $target_id);
          //date: drupal date format (varchar not datetime in MySQL) *minor glitch on time 01:00:00
          $rawDate = $array['Date Collected'];
          $dateFormat = date('Y-m-d', strtotime(str_replace('-', '/', $rawDate)));
          $dateTimeFormat = $dateFormat . 'T00:00:00';
          $sample->set('field_date_collected', $dateTimeFormat);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'marine_institute_date_mask', $array['Date Mask']);
          $sample->set('field_date_mask', $target_id);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_fate', $array['Fate']);
          $sample->set('field_fate', $target_id);
          //value
          $sample->set('field_field_comments', $array['Field Comments']);
          //value
          $sample->set('field_fish_length', $array['Fish Length']);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_fish_origin', $array['Fish Origin']);
          $sample->set('field_fish_origin', $target_id);
          //*field collection items
          $fishTagType = $array['Fish Tag Type'];
          $fishTagId = $array['Fish Tag ID'];
          //value
          $sample->set('field_fish_weight', $array['Fish Weight']);
          //*field collection items
          $sex = $array['Sex'];
          $sexDeterminationMethod = $array['Sex Determination Method'];
          //node ref
          $target_id = self::turnValueToNodeTid($sample_ID,$rowCount,'feature', $array['Geographic Feature']);
          $sample->set('field_geographic_feature', $target_id);
          //boolean
          $ts_value = 0;//default is unchecked
          if ($array['Is the sample in the IFBA Time Series table?'] == 'On') {
            $ts_value = 1;
          }
          $sample->set('field_sample_in_time_series', $ts_value);
          //value
          $sample->set('field_lab_number', $array['Lab Number']);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_life_stage', $array['Life Stage']);
          $sample->set('field_sample_life_stage', $target_id);
          //*field collection
          $maturity = $array['Maturity'];
          $maturityDeterminationMethod = $array['Maturity Determination Method'];
          //boolean check
          $rs_value = 0;//default unchecked
          if ($array['Representative Sample?'] == 'On') {
            $rs_value = 1;
          }
          $sample->set('field_representative_sample_', $rs_value);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'world_register_of_marine_species', $array['Species']);
          $sample->set('field_sample_species', $target_id);
          //save new sample node
          $sample->enforceIsNew();
          $sample->save();
          //push to array for potential delete if encounter exception
          //debug('Sample with internal ID '.$sample->id().' created.');
          array_push($safetyDeleteStorage, $sample);
          /*field collection items - must have valid HostEntity ($sample) to create field collection item children*/
          //field collection item
          $fishTagFC_Item = FieldCollectionItem::create(['field_name' => 'field_fish_tag_type']);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_tag_type', $fishTagType);
          $fishTagFC_Item->set('field_tag_type', $target_id);
          $fishTagFC_Item->set('field_tag_code_id', $fishTagId);
          $fishTagFC_Item->setHostEntity($sample);
          $fishTagFC_Item->save();
          //debug('FC with internal ID '.$fishTagFC_Item->id().' created.');
          //field collection item
          $fishSexFC_Item = FieldCollectionItem::create(['field_name' => 'field_gender']);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ices_gender_of_the_sampled_speci', $sex);
          $fishSexFC_Item->set('field_sex', $target_id);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_sex_source', $sexDeterminationMethod);
          $fishSexFC_Item->set('field_sex_source', $target_id);
          $fishSexFC_Item->setHostEntity($sample);
          $fishSexFC_Item->save();
          //debug('FC with internal ID '.$fishSexFC_Item->id().' created.');
          //field collection item
          $maturityFC_Item = FieldCollectionItem::create(['field_name' => 'field_maturity']);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_observed_maturity', $maturity);
          $maturityFC_Item->set('field_maturity', $target_id);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, 'ifba_sample_maturity_source', $maturityDeterminationMethod);
          $maturityFC_Item->set('field_maturity_determination_met', $target_id);
          $maturityFC_Item->setHostEntity($sample);
          $maturityFC_Item->save();
          //debug('FC with internal ID '.$maturityFC_Item->id().' created.');

          $rowCount++;
        } catch (EntityStorageException $e) {
          /* If encounter any storage exceptions delete all entities for that import process */
          foreach ($safetyDeleteStorage as $entity) {
            $entity->delete();
            //debug('Entity with internal ID '.$entity->id().' deleted.');
          }
          throw $e;//catch in form submit
        } catch (\Exception $e) {
          /* If encounter PK/title duplicate, FK mismatch, or taxonomy term mismatch, delete all entities for that import process */
          foreach ($safetyDeleteStorage as $entity) {
            $entity->delete();
            //debug('Entity with internal ID '.$entity->id().' deleted.');
          }
          throw $e;//catch in form submit
        }
      }//end create samples loop
    return 'All sample records created successfully.';
  }

  /**
   *  Get taxonomy term target id from value by finding matching term
   *
   * @param $sample_ID
   *  The 'Sample ID' (title/PK) used to report back to user if exception thrown
   *
   * @param $rowCount
   *  The row count used to report back to user if exception thrown
   *
   * @param $taxonomyName
   *  The taxonomy term machine name (string)
   *
   * @param $value
   *  The value read from the CSV file
   *
   * @return integer
   *  The target id for the new entity node (returns 0 if no term found)
   *
   * @throws \Exception
   *  If value has no matching terms presume incorrect entry (case insensitive) and inform user of error
   */
  public static function turnValueToTaxonomyTid ($sample_ID, $rowCount, $taxonomyName, $value){
    $target_id = 0;

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $taxonomyName);
    $target_ids = $query->execute();
    $terms = Term::loadMultiple($target_ids);

    /* Iterate terms and check for matching */
    foreach($terms as $term) {
      $name = $term->getName();
      if (strcasecmp($name, $value) === 0) {
        $target_id = $term->id();//ID of the term is the FK/target_id for the new node entity
        return $target_id;
      }
    }
    /* If the target_id is still '0' then no matching term */
    if($target_id === 0){
      /* If the value is empty allow to continue */
      if($value==''){
        return $target_id;//0
      }
      throw new \Exception('Error. No records created. Invalid taxonomy term: '. $value . ' (See Sample ID: ' . $sample_ID .', at row: '.$rowCount.' )');
    }
    return $target_id;//0
  }

  /**
   *  Get target_id (FK) from value by finding matching titles of
   *  referenced entity node. Ensuring import maintains referential integrity.
   *
   * @param $sample_ID
   *  The 'Sample ID' (title/PK) used to report back to user if exception thrown
   *
   * @param $rowCount
   *  The row count used to report back to user if exception thrown
   *
   * @param $bundle
   *  The machine name of the referenced bundle type (ie 'feature' or 'irish_fish_biochronology_archive')
   *
   * @param $value
   *  The value read from the CSV file
   *
   * @return integer
   *  The target id for the the 'field_contained_in' node (returns 0 if no term found)
   *
   * @throws \Exception
   *  If value has no matching terms presume incorrect entry (case insensitive) and inform user of error
   */
  public static function turnValueToNodeTid($sample_ID, $rowCount, $bundle, $value){
    $target_id = 0;

    $query = \Drupal::entityQuery('node');
    $node_ids = $query->condition('type',$bundle)
                      ->condition('status',1)
                      ->execute();
    $container_nodes = Node::loadMultiple($node_ids);

    /* Iterate node titles and check for matching */
    foreach ($container_nodes as $node){
     $title = $node->getTitle();
      if(strcasecmp($title,$value) === 0){
        $target_id = $node->id();
        return $target_id;
      }
    }
    /* If the target_id is still '0' then no matching container title */
    if($target_id === 0){
      throw new \Exception('Error. No records created. Invalid term: '. $value .' (with respect to type: '.$bundle.', See: Sample ID: ' . $sample_ID .', at row: '.$rowCount.' )');
    }
    return $target_id;//0
  }

  /**
   * Ensure not importing duplicate entity records.
   * Checked against the 'Sample Id' (title) records in the Drupal DB.
   *
   * @param $rowCount
   *  The row count used to report back to user if exception thrown
   *
   * @param $bundle
   *  The bundle to check ie 'irish_fish_biochronology_archive'
   *
   * @param $sample_ID
   *  The 'Sample ID' (title/PK) to check for duplicates
   *
   * @throws \Exception
   *  If find duplicate throw a new exception with problem title/PK to inform user
   */
  public static function checkForDuplicatePK($rowCount, $bundle, $sample_ID){

    $query = \Drupal::entityQuery('node');
    $node_ids = $query->condition('type',$bundle)
      ->condition('status',1)
      ->execute();

    $nodes = Node::loadMultiple($node_ids);

    foreach ($nodes as $node){
      $name = $node->getTitle();
      if($name == $sample_ID) {
        throw new \Exception('Error. No records created. Duplicate entry found for Sample ID: '.$sample_ID. ' ( at row: '.$rowCount.' )');
      }
    }
  }



}