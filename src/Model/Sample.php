<?php

namespace Drupal\ifba_import_export_mod\Model;

use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

/**
 * Class Sample
 *
 * Having a data model class:
 *
 *    1) may allow for quickly subscribing to ORM and scaffolding (CRUD)...if move away from Drupal!!!
 *
 *    2) cleaning up data with setters
 *
 *    3) some inheritance to allow for polymorphic implementation
 *
 *    4) Objects or arrays of objects are easy to deal with (ie PHP fputcsv, json_encode.decode, or other serializing methods)
 *
 *    5) Although this does add an extra layer!!
 *
 * @package Drupal\ifba_module\Form
 */
class Sample {

  /**
   * Properties public to allow iteration of 'sample' objects using fputcsv($handle,get_object_vars($fields))
   *
   * Properties are currently used for export / import bypasses this class
   */
  public $id = 0;//title
  public $approximateSampleAmount = 0;
  public $archivistComments = '';
  public $containedIn = '';
  public $dateCollected = '';
  public $dateMasked = '';
  public $fate = '';
  public $fieldComments = '';
  public $fishLength = 0.0;
  public $fishOrigin = '';
  public $fishTagType = '';
  //private $fishTagId = 0;//Field collection item
  public $fishWeight = 0.0;
  public $sex = '';
  //private $sexDeterminationMethod = '';//Field collection item
  public $geographicFeature = '';
  public $ifbaTimeTable = 0;//boolean
  public $labNumber = 0;
  public $lifeStage = '';
  public $maturity = '';
  //private $maturityDeterminationMethod = '';//Field collection item
  public $representativeSample = 0;//boolean
  public $species = '';

  /**
   * Getters and setters:
   *
   * Getters simply get the value
   *
   * Setters process the Drupal entity $node object to extract and then set value
   */

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param $node
   */
  public function setId($node) {
    $id = $node->title->value;//OK
    $this->id = $id;
  }

  /**
   * @return int
   */
  public function getApproximateSampleAmount() {
    return $this->approximateSampleAmount;
  }

  /**
   * @param $node
   */
  public function setApproximateSampleAmount($node) {
    $approximateSampleAmount = $node->field_approx_sample_amount->value;//OK
    $this->approximateSampleAmount = $approximateSampleAmount;
  }

  /**
   * @return string
   */
  public function getArchivistComments() {
    return $this->archivistComments;
  }

  /**
   * @param $node
   */
  public function setArchivistComments($node) {
    $archivistComments = $node->field_archivist_comments->value;//OK
    $this->archivistComments = $archivistComments;
  }

  /**
   * @return string
   */
  public function getContainedIn() {
    return $this->containedIn;
  }

  /**
   * @param $node
   *
   * Contained In (field_contained_in) = FK
   *
   *  Node sample (fk) -> node container (pk) -> getTitle
   */
  public function setContainedIn($node) {
    $containedIn_tid = $node->field_contained_in->target_id;
    $container_node = Node::load($containedIn_tid);
    if(is_null($container_node)){
      $this->containedIn = 'NULL';
    }else {
      $containedIn = $container_node->getTitle();
      $this->containedIn = $containedIn;
    }
  }

  /**
   * @return string
   */
  public function getDateCollected() {
    return $this->dateCollected;
  }

  /**
   * @param $node
   */
  public function setDateCollected($node) {
    $dateCollected = $node->field_date_collected->value;//OK
    $this->dateCollected = $dateCollected;
  }

  /**
   * @return string
   */
  public function getDateMasked() {
    return $this->dateMasked;
  }

  /**
   * @param $node (not tested)
   */
  public function setDateMasked($node) {
    $mask_tid = $node->field_date_mask->target_id;//OK
    if(is_null($mask_tid)){
      $this->dateMasked = 'NULL';
    }else {
      $term = Term::load($mask_tid);
      if (is_null($term)) {
        $this->dateMasked = 'NULL';
      }
      else {
        $fate = $term->getName();
        $this->dateMasked = $fate;
      }
    }
  }

  /**
   * @return string
   */
  public function getFate() {
    return $this->fate;
  }

  /**
   * @param $node
   *
   * Node -> taxonomy term
   */
  public function setFate($node) {
    $fate_tid = $node->field_fate->target_id;//OK
    if(is_null($fate_tid)){
      $this->fate = 'NULL';
    }else {
      $term = Term::load($fate_tid);
      if (is_null($term)) {
        $this->fate = 'NULL';
      }
      else {
        $fate = $term->getName();
        $this->fate = $fate;
      }
    }
  }

  /**
   * @return string
   */
  public function getFieldComments() {
    return $this->fieldComments;
  }

  /**
   * @param $node
   */
  public function setFieldComments($node) {
    $fieldComments = $node->field_field_comments->value;//OK
    $this->fieldComments = $fieldComments;
  }

  /**
   * @return float
   */
  public function getFishLength() {
    return $this->fishLength;
  }

  /**
   * @param $node
   */
  public function setFishLength($node) {
    $fishLength = $node->field_fish_length->value;//OK
    $this->fishLength = $fishLength;
  }

  /**
   * @return string
   */
  public function getFishOrigin() {
    return $this->fishOrigin;
  }

  /**
   * @param $node
   */
  public function setFishOrigin($node) {
    $target_id = $node->field_fish_origin->target_id;//OK
    $term = Term::load($target_id);
    if(is_null($term)){
      $this->fishOrigin = 'NULL';
    }else {
      $fishOrigin = $term->getName();
      $this->fishOrigin = $fishOrigin;
    }
  }

  /**
   * @return string
   */
  public function getFishTagType() {
    return $this->fishTagType;
  }

  /**
   * @param $node
   *
   * Node -> field collection -> field collection item(s) -> taxonomy term & value -> json object
   *
   * Will return multiple fish tag entries values.
   */
  public function setFishTagType($node) {
      /* Array to hold temp objects for JSON string */
    $objects = [];
    /* Get all values associated with entity id in that 'table' */
    $array2d = $node->field_fish_tag_type->getValue();
    /* Get all FK's (value) */
    $value_fks = array_column($array2d, 'value');

    if(empty($value_fks)){
        $this->fishTagType = 'NULL';//Setting to NULL*
    }else{
      /* Use each FK to load field collection item and then extract taxonomy and value */
      foreach ($value_fks as $fk) {
        $field_collection_object = FieldCollectionItem::load($fk);
        $field_collection_array = $field_collection_object->toArray();
        /* Declare temp object for JSON */
        $object = new \stdClass();

        // 1) Get tag type
        //$tag_type_tid = $field_collection_array['field_tag_type'][0]['target_id'];//Getting offset error
        $tag_type_tid = 0;
        foreach ($field_collection_array['field_tag_type'] as $field){
          $tag_type_tid = (int) $field['target_id'];
        }

        $term = Term::load($tag_type_tid);
        if (is_null($term)) {
          $tagType = 'NULL';
        }
        else {
          $tagType = $term->getName();
        }

        // 2) Get tag code
        //$tag_code_id_value = $field_collection_array['field_tag_code_id'][0]['value'];//Getting offset error
        $tag_code_id_value = '';
        foreach ($field_collection_array['field_tag_code_id'] as $field){
          $tag_code_id_value = $field['value'];
        }

        if (is_null($tag_code_id_value)) {
          $tag_code_id_value = 'NULL';
        }

        /* Assign extracted values to object */
        $object->code = $tag_code_id_value;
        $object->type = $tagType;
        //Push
        array_push($objects, $object);
      }
      /* Create json string */
      $jsonString = json_encode($objects);
      $this->fishTagType = $jsonString;
    }

  }

  /**
   * @return float
   */
  public function getFishWeight() {
    return $this->fishWeight;
  }

  /**
   * @param $node
   *
   * **Maybe not getting proper float value ??
   */
  public function setFishWeight($node) {
    $fishWeight = $node->field_fish_weight->value;//OK
    $this->fishWeight = $fishWeight;
  }

  /**
   * @return string
   */
  public function getSex() {
    return $this->sex;
  }

  /**
   * @param $node
   *
   * Node -> field collection -> field collection item -> associate array -> taxonomy terms -> json object
   */
  public function setSex($node) {
    //Get the foreign key from node at 'value'
    $field_gender_fk = (int) $node->field_gender->value;
    //Pass foreign key to FieldCollectionItem load() to get field collection object
    $field_collection_object = FieldCollectionItem::load($field_gender_fk);
    //Set field collection object to an associate array (**maybe here there is a more direct path**)
    $field_collection_array = $field_collection_object->toArray();
    //Need the target id to load the taxonomy term string
    $field_sex_tid = 0;
    foreach ($field_collection_array['field_sex'] as $field){
      $field_sex_tid = (int) $field['target_id'];
    }
    //Call taxonomy Term load() with target Id to get the term object
    $term = Term::load($field_sex_tid);
    if(is_null($term)){
      $fieldSex = 'NULL';
    }else {
      //Use term object to get the String name
      $fieldSex = $term->getName();
    }

    $field_sex_source_tid = 0;
    foreach ($field_collection_array['field_sex_source'] as $field){
      $field_sex_source_tid = (int) $field['target_id'];
    }

    $term = Term::load($field_sex_source_tid);
    if(is_null($term)){
      $fieldSexSource = 'NULL';
    }else {
      //Use term object to get the String name
      $fieldSexSource = $term->getName();
    }

    $object = new \stdClass();
    $object->sex = $fieldSex;
    $object->source = $fieldSexSource;

    $sex = json_encode($object);
    $this->sex = $sex;
  }

  /**
   * @return string
   */
  public function getGeographicFeature() {
    return $this->geographicFeature;
  }

  /**
   * @param $node
   *
   * Node -> node -> getTitle()
   */
  public function setGeographicFeature($node) {
    $geographic_feature_tid = $node->field_geographic_feature->target_id;//ie 2286 refers to no taxonomy term
    $geographic_node = Node::load($geographic_feature_tid);
    if(is_null($geographic_node)){
      $this->geographicFeature = 'NULL';
    }else{
      $geographicFeature = $geographic_node->getTitle();
      $this->geographicFeature = $geographicFeature;
    }
  }

  /**
   * @return int (boolean)
   */
  public function getIfbaTimeTable() {
    return $this->ifbaTimeTable;
  }

  /**
   * @param $node
   */
  public function setIfbaTimeTable($node) {
    $ifbaTimeTable = $node->field_sample_in_time_series->value;//OK 1 or 0 boolean
    $this->ifbaTimeTable = $ifbaTimeTable;
  }

  /**
   * @return int
   */
  public function getLabNumber() {
    return $this->labNumber;
  }

  /**
   * @param $node
   */
  public function setLabNumber($node) {
    $labNumber = $node->field_lab_number->value;//OK
    $this->labNumber = $labNumber;
  }

  /**
   * @return string
   */
  public function getLifeStage() {
    return $this->lifeStage;
  }

  /**
   * @param $node
   *
   * Node -> taxonomy term
   */
  public function setLifeStage($node) {
    $sample_life_stage_tid = $node->field_sample_life_stage->target_id;//OK
    if(is_null($sample_life_stage_tid)){
      $this->lifeStage = 'NULL';
    }else {
      $term = Term::load($sample_life_stage_tid);
      if (is_null($term)) {
        $this->lifeStage = 'NULL';
      }
      else {
        $lifeStage = $term->getName();
        $this->lifeStage = $lifeStage;
      }
    }
  }

  /**
   * @return string
   */
  public function getMaturity() {
    return $this->maturity;
  }

  /**
   * @param $node
   *
   * Node -> field collection -> field collection item -> associate array -> taxonomy terms -> json object
   */
  public function setMaturity($node) {
    $field_maturity_fk = $node->field_maturity->value;

    $field_collection_object = FieldCollectionItem::load($field_maturity_fk);
    //Set field collection object to an associate array (**maybe here there is a more direct path**)
    $field_collection_array = $field_collection_object->toArray();
    //Need the target id to load the taxonomy term string
    $field_maturity_tid = 0;
    foreach ($field_collection_array['field_maturity'] as $field){
      $field_maturity_tid = (int) $field['target_id'];
    }
    //Call taxonomy Term load() with target Id to get the term object
    $term = Term::load($field_maturity_tid);

    if(is_null($term)){
      $fieldMaturity = 'NULL';
    }else {
      //Use term object to get the String name
      $fieldMaturity = $term->getName();
    }
    //Need the target id to load the taxonomy term string
    $field_maturity_method_tid = 0;
    foreach ($field_collection_array['field_maturity_determination_met'] as $field){
      $field_maturity_method_tid = (int) $field['target_id'];
    }
    //Call taxonomy Term load() with target Id to get the term object
    $term = Term::load($field_maturity_method_tid);
    if(is_null($term)){
      $fieldMaturityMethod = 'NULL';
    }else {
      //Use term object to get the String name
      $fieldMaturityMethod = $term->getName();
    }

    $object = new \stdClass();
    $object->maturity = $fieldMaturity;
    $object->method = $fieldMaturityMethod;

    $maturity = json_encode($object);
    $this->maturity = $maturity;
  }

  /**
   * @return int (boolean)
   */
  public function getRepresentativeSample() {
    return $this->representativeSample;
  }

  /**
   * @param $node
   */
  public function setRepresentativeSample($node) {
    $representativeSample = $node->field_representative_sample_->value;//OK
    $this->representativeSample = $representativeSample;
  }

  /**
   * @return string
   */
  public function getSpecies() {
    return $this->species;
  }

  /**
   * @param $node
   *
   * Node -> taxonomy term
   */
  public function setSpecies($node) {
    $species_tid = $node->field_sample_species->target_id;//OK
    $term = Term::load($species_tid);
    if(is_null($term)){
    $this->species = 'NULL';
    }else{
      $species = $term->getName();
      $this->species = $species;
    }
  }

}