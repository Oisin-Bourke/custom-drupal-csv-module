<?php


namespace Drupal\ifba_import_export_mod\Model;
/**
 * Container Class that holds the 'enum' types related to the IFBA module
 *
 */
abstract class IFBATypes {

  /* Bundle types */
  const IRISH_FISHERIES_BIOCHRONOLOGY_ARCHIVE = 'irish_fish_biochronology_archive';
  const IFBA_ARCHIVE_CONTAINER = 'ifba_archive_container';
  const FEATURE = 'feature';

  /*Taxonomy term types */
  const MARINE_INSTITUTE_DATE_MASK = 'marine_institute_date_mask';
  const WORLD_REGISTER_OF_MARINE_SPECIES = 'world_register_of_marine_species';
  const ICES_SAMPLE_OF_THE_SAMPLED_SPECI = 'ices_gender_of_the_sampled_speci';
  const IFBA_SAMPLE_FATE = 'ifba_sample_fate';
  const IFBA_SAMPLE_FISH_ORIGIN = 'ifba_sample_fish_origin';
  const IFBA_SAMPLE_LIFE_STAGE = 'ifba_sample_life_stage';
  const IFBA_SAMPLE_SEX_SOURCE = 'ifba_sample_sex_source';
  const IFBA_SAMPLE_OBSERVED_MATURITY = 'ifba_sample_observed_maturity';
  const IFBA_SAMPLE_MATURITY_SOURCE = 'ifba_sample_maturity_source';
  const IFBA_TAG_TYPE = 'ifba_tag_type';

  /*Sample property types */
  const FIELD_APPROX_SAMPLE_AMOUNT = 'field_approx_sample_amount';
  const FIELD_ARCHIVIST_COMMENTS = 'field_archivist_comments';
  const FIELD_CONTAINED_IN = 'field_contained_in';//FK
  const FIELD_DATE_COLLECTED = 'field_date_collected';
  const FIELD_DATE_MASK = 'field_date_mask';
  const FIELD_FATE = 'field_fate';
  const FIELD_FIELD_COMMENTS = 'field_field_comments';
  const FIELD_FISH_LENGTH = 'field_fish_length';
  const FIELD_FISH_ORIGIN = 'field_fish_origin';
  const FIELD_FISH_TAG_TYPE = 'field_fish_tag_type';//FC
  const FIELD_FISH_WEIGHT = 'field_fish_weight';
  const FIELD_GENDER = 'field_gender';//FC
  const FIELD_GEOGRAPHIC_FEATURE = 'field_geographic_feature';
  const FIELD_SAMPLE_IN_TIME_SERIES = 'field_sample_in_time_series';
  const FIELD_LAB_NUMBER = 'field_lab_number';
  const FIELD_SAMPLE_LIFE_STAGE = 'field_sample_life_stage';
  const FIELD_MATURITY = 'field_maturity';//FC
  const FIELD_REPRESENTATIVE_SAMPLE = 'field_representative_sample_';
  const FIELD_SAMPLE_SPECIES = 'field_sample_species';

  /*Field Collection Item types*/
  const FC_FIELD_TAG_TYPE = 'field_tag_type';
  const FC_FIELD_TAG_CODE_ID = 'field_tag_code_id';
  const FC_FIELD_SEX = 'field_sex';
  const FC_FIELD_SEX_SOURCE = 'field_sex_source';
  const FC_FIELD_MATURITY = 'field_maturity';//same as field name!!
  const FC_FIELD_MATURITY_DETERMINATION_METHOD = 'field_maturity_determination_met';

  /*CSV Import Header Fields*/
  const CSV_HEAD_SAMPLE_ID = 'Label(sample id)';
  const CSV_HEAD_APPROX_SAMPLE_AMOUNT = 'Approximate Sample Amount';
  const CSV_HEAD_ARCHIVIST_COMMENTS = 'Archivist Comments';
  const CSV_HEAD_CONTAINED_IN = 'Contained In';
  const CSV_HEAD_DATE_COLLECTED ='Date Collected';
  const CSV_HEAD_DATE_MASK = 'Date Mask';
  const CSV_HEAD_FATE = 'Fate';
  const CSV_HEAD_FIELD_COMMENTS = 'Field Comments';
  const CSV_HEAD_FISH_LENGTH = 'Fish Length';
  const CSV_HEAD_FISH_ORIGIN = 'Fish Origin';
  const CSV_HEAD_FISH_TAG_TYPE = 'Fish Tag Type';
  const CSV_HEAD_FISH_TAG_ID = 'Fish Tag ID';
  const CSV_HEAD_FISH_WEIGHT = 'Fish Weight';
  const CSV_HEAD_SEX = 'Sex';
  const CSV_HEAD_SEX_DETERMINATION = 'Sex Determination Method';
  const CSV_HEAD_GEO_FEATURE = 'Geographic Feature';
  const CSV_HEAD_TIME_SERIES = 'Is the sample in the IFBA Time Series table?';
  const CSV_HEAD_LAB_NUMBER = 'Lab Number';
  const CSV_HEAD_LIFE_STAGE = 'Life Stage';
  const CSV_HEAD_MATURITY = 'Maturity';
  const CSV_HEAD_MATURITY_DETER = 'Maturity Determination Method';
  const CSV_HEAD_REP_SAMPLE = 'Representative Sample?';
  const CSV_HEAD_SPECIES = 'Species';
}