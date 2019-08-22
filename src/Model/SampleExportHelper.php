<?php

namespace Drupal\ifba_import_export_mod\Model;

use Drupal\node\Entity\Node;

/**
 * Class SampleExportHelper
 *
 * Set of static methods that help the ExportForm class
 *
 * @package Drupal\ifba_module\Form
 */
class SampleExportHelper {

  /**
   * CSV header for IFBA samples export
   *
   * @return string
   */
  public static function writeSamplesCSVHeader(){
    return  'id,approximateSampleAmount,archivistComments,containedIn,dateCollected,'
      .'dateMasked,fate,fieldComments,fishLength,fishOrigin,fishTagType,'
      .'fishWeight,sex,geographicFeature,ifbaTimeTable,labNumber,lifeStage,'
      .'maturity,representativeSample,species'
      .PHP_EOL;
  }

  /**
   * Read IFBA Sample data from Drupal DB
   *
   * @param $container_id
   *  Container id is the Sample Set Container entity id.
   *
   *  If the value is '0' read all data, else filter by particular Sample Set Container.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\node\Entity\Node[]
   */
  public static function readIfbaSampleData($container_id){
    /* Get entity query object for entity type 'node' */
    $query = \Drupal::entityQuery('node');

    $node_ids = [];

    /* Use query object with condition filters to execute query and return node ids array */
    if($container_id===0) {
        $node_ids = $query->condition('type',IFBATypes::IRISH_FISHERIES_BIOCHRONOLOGY_ARCHIVE)
                          ->condition('status', 1)
                          ->execute();
    }else{
        $node_ids = $query->condition('type', IFBATypes::IRISH_FISHERIES_BIOCHRONOLOGY_ARCHIVE)
                          ->condition('field_contained_in',$container_id)
                          ->condition('status', 1)
                          ->execute();
    }
    /* Pass node ids array into loadMultiple to return all associated data/nodes */
    $nodes_result = Node::loadMultiple($node_ids);

    return $nodes_result;
  }

  /**
   * Read all IFBA Container data from Drupal DB
   *
   * This function is used to populate the dropdown option/select
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\node\Entity\Node[]
   */
  public static function readIfbaContainerData(){
    /* Get entity query object for entity type 'node' */
    $query = \Drupal::entityQuery('node');

    /*Use query object with condition filters to execute query and return node ids array*/
    $node_ids = $query->condition('type',IFBATypes::IFBA_ARCHIVE_CONTAINER)
      ->condition('status',1)
      ->execute();

    /*Pass node ids array into loadMultiple to return all associated data/nodes*/
    $nodes_result = Node::loadMultiple($node_ids);

    return $nodes_result;
  }

  /**
   * Create sample objects array from nodes
   *
   * This function processes the nodes via the Sample class setters
   *
   * @param array $nodes_result
   *
   * @return array samples
   */
  public static function createSamplesFromNodes(array $nodes_result){

    $samples = [];

    foreach ($nodes_result as $node){
      /*Instantiate instance of Sample model data class*/
      $sample = new Sample();

      $sample->setId($node);//PK

      $sample->setApproximateSampleAmount($node);
      $sample->setArchivistComments($node);
      $sample->setContainedIn($node);//FK (Sample Set Container)
      $sample->setDateCollected($node);
      $sample->setDateMasked($node);
      $sample->setFate($node);
      $sample->setFieldComments($node);
      $sample->setFishLength($node);
      $sample->setFishOrigin($node);
      $sample->setFishTagType($node);//1 : M (Field Collection Item)
      $sample->setFishWeight($node);
      $sample->setSex($node);//1 : M (Field Collection Item)
      $sample->setGeographicFeature($node);
      $sample->setIfbaTimeTable($node);
      $sample->setLabNumber($node);
      $sample->setLifeStage($node);
      $sample->setMaturity($node);//1 : M (Field Collection Item)
      $sample->setRepresentativeSample($node);
      $sample->setSpecies($node);

      array_push($samples,$sample);//push sample object to array samples
    }
  return $samples;
  }

  /**
   * Write samples to CSV file (can take array of any object type)
   *
   * This function writes samples array to CSV using PHP built-in fputcsv
   *
   * @param array
   * An array of Sample objects
   *
   * @return string/boolean
   *  Message for user or false if error
   */
  public static function writeSamplesToCSV(array $samples){

    $file = 'samples_export.csv';

    if($handle = fopen($file,'w')){

      /* Write csv header at first row*/
      fwrite($handle, self::writeSamplesCSVHeader());

      /*use PHP fputcsv function to write each sample object's fields to CSV*/
      foreach ($samples as $fields){
        fputcsv($handle,get_object_vars($fields));
      }

      fclose($handle);

      return 'File ' . $file . '  generated successfully.';
    }else{
      return false;
    }
  }

}