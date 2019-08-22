IRISH FISHERIES BIOCHRONOLOGY ARCHIVE(IFBA): IMPORT EXPORT CSV MODULE
---------------------

## Introduction

This self-contained and currently isolated custom module is designed to add import and export CSV functionality for the IFBA.

#### Use cases:

1. Import CSV file to DRUPAL database - typically by sample set container  (current path: `/ifba/import-csv`)

       *this process is aborted (and reverted) if any errors are encountered (entity storage or general taxonomy/node term exceptions)

       *this process cannot handle adding multiple/secondary 'Fish Tag Types' (need to edit existing entry)

       *this process cannot be used to edit/overwrite existing records (need to edit manually or delete)

       *this process does not accept duplicate entries (by 'title')

2. Export all records to a CSV file  (current path: `/ifba/export`)

       *field collection items are packaged into a single CSV field as a JSON string object

3. Export records by sample set container to a CSV file (current path: `/ifba/export`)

        * field collection items are packaged into a single CSV field as a JSON string objects

## Requirements

This module is currently an isolated/self-contained import and export implementation.

However, the IFBA database depends on 'taxonomy terms' from the core Marine Institute catalogue.

## Recommended modules

All those part of the Drupal core and those related to the Marine Institute base.

## Installation

Drupal documentation recommends that custom modules are located at: `modules/custom`.

The module is currently designated to be installed at `modules/custom`.

The module's `info.yml` file also specifies `package: Custom`.

Install:

 1. Create a `/custom` folder if not already created.

 2. Download zipped contents of 'IFBA' branch from GitHub and unzip.

 3. Copy `ifba_import_export_mod` folder and paste into `custom` folder.

 4. The module can be enabled through the Drupal interface 'Extend' and then selecting 'IFBA Import and Export CSV Module' (currently located under 'Custom')
 
 #### Post Installation Checks
 
 * Navigate to `/ifba/import-csv` and use the `test_import_single_sample_ID999999_then_delete.csv` included in the root of this repository to test import functionality
 * **Be sure to delete this entry using the admin interface after testing!**
 * Navigate to  `/ifba/export` and select a sample container # to export 

## Configuration

The module is currently configured to be installed under `modules/custom` as indicate by the `info.yml` file with: `package: Custom`.

## Troubleshooting

##### Import:

   The import CSV function will report back the following errors to the user where appropriate:

   1. Incorrect file type
   2. Incorrect field name (specifying the the first field name that does not match the required field name)
   3. Incorrect field count
   4. Duplicate entry (specifying the 'Sample ID' and the row number)
   5. Invalid taxonomy term (specifying the invalid term, the 'Sample ID' and the row number)
   6. Invalid reference term - 'ie Sample Set Container #' (specifying the 'bundle', the 'Sample ID' and the row number)

   _This should allow the user to quickly identify the error in the CSV_.

##### Export:

The export CSV function is controlled by a dropdown option/select that allows the user to 'select all' or an existing sample set container number.

Therefore, the user should not encounter any `I/O` type errors.

## FAQ

Why is the import and export rather slow?

    Both import and export functions will be relatively slow due to the abstracted nature of the Drupal database.
    For example, when importing a single field taxonomy term such as 'Atlantic Salmon', we have to iterate all terms
    in that 'controlled list' to find its match integer number representation. This number then populates the database
    with the correct term. Similarly, when creating a new record and checking for potential duplicates, we have to
    iterate every 'Sample ID' (title) to find potential clashes.

Why is there a mismatch between import and export formats?

    The export CSV function bundles field collection items into a single field in JSON object format. For example, field 'sex' is
    represented like: '{"sex":"Female","source":"Lab"}'

    Import, on the other hand, requires a 'flat' CSV file, where field collection items are parsed out into their own fields.
    Therefore, field 'sex' is field 'Sex' and field 'Sex Determination Method'.


## Maintainers

?? To be decided...

## Tests

See `IFBATestCases.xlsx`in root of this repo for documented test cases (including install and post install checks on fresh drupal instance). 

##### Use Cases:

1. Import CSV file to DRUPAL database

      1. Import file with duplicate PK (title) - Not allowed - Inform User - OK
      2. Import file with wrong taxonomy - Not allowed - Inform User - OK
      3. Import file with wrong FK (contained in) - Not allowed - Inform User - OK
      4. Import non-CSV file - Not allowed - Inform User - OK
      5. Import CSV with unexpected header field - Not allowed - Inform User - OK
      6. Import CSV as required - Allowed - Inform User - OK

2. Export all records to a CSV file

      1. Exported approx 950 records - No errors - OK

3. Export records by sample set container to a CSV file

      1. Export by various sample set container - No errors - OK

 _Export should not generate any exceptions_

    SIDE NOTE:

    **It was not possible to conduct any functional tests, which requires a full Drupal instance, using PHPUnit on this module,
    due to an issue with the core MI catalogue. At path: 'C:\BitnamiSites\MIDataCatalogue\profiles\agov\..' there is an issue with
    code/directory duplication and thus: 'Fatal error: Cannot redeclare page_cache_help()' on attempting to test. Attempting to remove
    these duplicate folders at path: 'profiles/agov/profiles/agov' caused the application to crash.
