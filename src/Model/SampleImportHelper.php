<?php

namespace Drupal\ifba_import_export_mod\Model;

use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityStorageException;

class SampleImportHelper {

  /**
   * @var array
   *  The required CSV header for imports.
   */
  public static $requiredHeader
    = array(IFBATypes::CSV_HEAD_SAMPLE_ID,IFBATypes::CSV_HEAD_APPROX_SAMPLE_AMOUNT,IFBATypes::CSV_HEAD_ARCHIVIST_COMMENTS,
            IFBATypes::CSV_HEAD_CONTAINED_IN,IFBATypes::CSV_HEAD_DATE_COLLECTED,IFBATypes::CSV_HEAD_DATE_MASK,
            IFBATypes::CSV_HEAD_FATE,IFBATypes::CSV_HEAD_FIELD_COMMENTS,IFBATypes::CSV_HEAD_FISH_LENGTH,
            IFBATypes::CSV_HEAD_FISH_ORIGIN,IFBATypes::CSV_HEAD_FISH_TAG_TYPE,IFBATypes::CSV_HEAD_FISH_TAG_ID,
            IFBATypes::CSV_HEAD_FISH_WEIGHT,IFBATypes::CSV_HEAD_SEX,IFBATypes::CSV_HEAD_SEX_DETERMINATION,
            IFBATypes::CSV_HEAD_GEO_FEATURE,IFBATypes::CSV_HEAD_TIME_SERIES,IFBATypes::CSV_HEAD_LAB_NUMBER,
            IFBATypes::CSV_HEAD_LIFE_STAGE, IFBATypes::CSV_HEAD_MATURITY,IFBATypes::CSV_HEAD_MATURITY_DETER,
            IFBATypes::CSV_HEAD_REP_SAMPLE,IFBATypes::CSV_HEAD_SPECIES
    );

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
   *  Throw exception if header does not match required header and inform user of error
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
    /* Ensure correct number of fields */
    if(count($header)!==count($requiredHeader)){
      throw new \Exception('Error.  Expecting '. count($requiredHeader) . ' number of fields. Read file contains '.count($header). ' fields.');
    }
    /* Check for difference between header and required header */
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
   *  If all entities created return simple message for user
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
    /* A counter to inform user of row error */
    $rowCount = 2;

    /* Iterate array of arrays to create new entities*/
      foreach ($data as $array) {
        try {
          /* Create node and then set fields programmatically */
          $sample = Node::create(['type' => IFBATypes::IRISH_FISHERIES_BIOCHRONOLOGY_ARCHIVE ]);

          /* 1st check for duplicate entry by 'Sample ID' (title/PK) */
          $sample_ID = $array[IFBATypes::CSV_HEAD_SAMPLE_ID];
          self::checkForDuplicatePK($rowCount,IFBATypes::IRISH_FISHERIES_BIOCHRONOLOGY_ARCHIVE,$sample_ID);
          $sample->set('title', $sample_ID);

          //value
          $sample->set(IFBATypes::FIELD_APPROX_SAMPLE_AMOUNT, $array[IFBATypes::CSV_HEAD_APPROX_SAMPLE_AMOUNT]);
          //value
          $sample->set(IFBATypes::FIELD_ARCHIVIST_COMMENTS, $array[IFBATypes::CSV_HEAD_ARCHIVIST_COMMENTS]);
          //node ref
          $target_id = self::turnValueToNodeTid($sample_ID,$rowCount,IFBATypes::IFBA_ARCHIVE_CONTAINER, $array[IFBATypes::CSV_HEAD_CONTAINED_IN]);
          $sample->set(IFBATypes::FIELD_CONTAINED_IN, $target_id);
          //date: drupal date format (varchar not datetime in MySQL) *minor glitch on time 01:00:00
          $rawDate = $array[IFBATypes::CSV_HEAD_DATE_COLLECTED];
          $dateFormat = date('Y-m-d', strtotime(str_replace('-', '/', $rawDate)));
          $dateTimeFormat = $dateFormat . 'T00:00:00';
          $sample->set(IFBATypes::FIELD_DATE_COLLECTED, $dateTimeFormat);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::MARINE_INSTITUTE_DATE_MASK, $array[IFBATypes::CSV_HEAD_DATE_MASK]);
          $sample->set(IFBATypes::FIELD_DATE_MASK, $target_id);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_FATE, $array[IFBATypes::CSV_HEAD_FATE]);
          $sample->set(IFBATypes::FIELD_FATE, $target_id);
          //value
          $sample->set(IFBATypes::FIELD_FIELD_COMMENTS, $array[IFBATypes::CSV_HEAD_FIELD_COMMENTS]);
          //value
          $sample->set(IFBATypes::FIELD_FISH_LENGTH, $array[IFBATypes::CSV_HEAD_FISH_LENGTH]);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_FISH_ORIGIN, $array[IFBATypes::CSV_HEAD_FISH_ORIGIN]);
          $sample->set(IFBATypes::FIELD_FISH_ORIGIN, $target_id);
          //*field collection items
          $fishTagType = $array[IFBATypes::CSV_HEAD_FISH_TAG_TYPE];
          $fishTagId = $array[IFBATypes::CSV_HEAD_FISH_TAG_TYPE];
          //value
          $sample->set(IFBATypes::FIELD_FISH_WEIGHT, $array[IFBATypes::CSV_HEAD_FISH_WEIGHT]);
          //*field collection items
          $sex = $array[IFBATypes::CSV_HEAD_SEX];
          $sexDeterminationMethod = $array[IFBATypes::CSV_HEAD_SEX_DETERMINATION];
          //node ref
          $target_id = self::turnValueToNodeTid($sample_ID,$rowCount,IFBATypes::FEATURE, $array[IFBATypes::CSV_HEAD_GEO_FEATURE]);
          $sample->set(IFBATypes::FIELD_GEOGRAPHIC_FEATURE, $target_id);
          //boolean
          $ts_value = 0;//default is unchecked
          if ($array[IFBATypes::CSV_HEAD_TIME_SERIES] == 'On') { $ts_value = 1;}
          $sample->set(IFBATypes::FIELD_SAMPLE_IN_TIME_SERIES, $ts_value);
          //value
          $sample->set(IFBATypes::FIELD_LAB_NUMBER, $array[IFBATypes::CSV_HEAD_LAB_NUMBER]);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_LIFE_STAGE, $array[IFBATypes::CSV_HEAD_LIFE_STAGE]);
          $sample->set(IFBATypes::FIELD_SAMPLE_LIFE_STAGE, $target_id);
          //*field collection
          $maturity = $array[IFBATypes::CSV_HEAD_MATURITY];
          $maturityDeterminationMethod = $array[IFBATypes::CSV_HEAD_MATURITY_DETER];
          //boolean
          $rs_value = 0;//default unchecked
          if ($array[IFBATypes::CSV_HEAD_REP_SAMPLE] == 'On') { $rs_value = 1;}
          $sample->set(IFBATypes::FIELD_REPRESENTATIVE_SAMPLE, $rs_value);
          //taxonomy
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::WORLD_REGISTER_OF_MARINE_SPECIES, $array[IFBATypes::CSV_HEAD_SPECIES]);
          $sample->set(IFBATypes::FIELD_SAMPLE_SPECIES, $target_id);
          //save new sample node
          $sample->enforceIsNew();
          $sample->save();
          //push to array for potential delete if encounter exception
          array_push($safetyDeleteStorage, $sample);

          /**
           * Field collection items must have valid HostEntity ($sample) to create field collection item children
           */
          //field collection item
          $fishTagFC_Item = FieldCollectionItem::create(['field_name' => IFBATypes::FIELD_FISH_TAG_TYPE]);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_TAG_TYPE, $fishTagType);
          $fishTagFC_Item->set(IFBATypes::FC_FIELD_TAG_TYPE, $target_id);
          $fishTagFC_Item->set(IFBATypes::FC_FIELD_TAG_CODE_ID, $fishTagId);
          $fishTagFC_Item->setHostEntity($sample);
          $fishTagFC_Item->save();
          //field collection item
          $fishSexFC_Item = FieldCollectionItem::create(['field_name' => IFBATypes::FIELD_GENDER]);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::ICES_SAMPLE_OF_THE_SAMPLED_SPECI, $sex);
          $fishSexFC_Item->set(IFBATypes::FC_FIELD_SEX, $target_id);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_SEX_SOURCE, $sexDeterminationMethod);
          $fishSexFC_Item->set(IFBATypes::FC_FIELD_SEX_SOURCE, $target_id);
          $fishSexFC_Item->setHostEntity($sample);
          $fishSexFC_Item->save();
          //field collection item
          $maturityFC_Item = FieldCollectionItem::create(['field_name' => IFBATypes::FIELD_MATURITY]);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_OBSERVED_MATURITY, $maturity);
          $maturityFC_Item->set(IFBATypes::FC_FIELD_MATURITY, $target_id);
          $target_id = self::turnValueToTaxonomyTid($sample_ID,$rowCount, IFBATypes::IFBA_SAMPLE_MATURITY_SOURCE, $maturityDeterminationMethod);
          $maturityFC_Item->set(IFBATypes::FC_FIELD_MATURITY_DETERMINATION_METHOD, $target_id);
          $maturityFC_Item->setHostEntity($sample);
          $maturityFC_Item->save();

          /**
           * Implement potential warning here
           *
           * See root of GitHub and file NotImplementedSQLWarningFunction.txt for incomplete function
           *
           * checkForSimilarEntityWarning($sample);
           */

          $rowCount++;
        } catch (EntityStorageException $e) {
          /* If encounter any storage exceptions delete all entities and children for that import process */
          foreach ($safetyDeleteStorage as $entity) {
            $entity->delete();
          }
          throw $e;//catch in form submit
        } catch (\Exception $e) {
          /* If encounter PK/title duplicate, FK mismatch, or taxonomy term mismatch, delete all entities and children for that import process */
          foreach ($safetyDeleteStorage as $entity) {
            $entity->delete();
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
      /* If the field value is empty do not throw error exception*/
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
    /* If the target_id is still '0' then no matching node title and inform user of error*/
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
    /* If find matching title (user PK) inform user of error */
    foreach ($nodes as $node){
      $name = $node->getTitle();
      if($name == $sample_ID) {
        throw new \Exception('Error. No records created. Duplicate entry found for Sample ID: '.$sample_ID. ' ( at row: '.$rowCount.' )');
      }
    }
  }

}