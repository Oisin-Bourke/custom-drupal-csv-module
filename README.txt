IRISH FISHERIES BIOCHRONOLOGY ARCHIVE(IFBA): IMPORT EXPORT CSV MODULE
---------------------

 * Introduction

    This self contained custom module is designed to add import and export CSV functionality for the IFBA.

    Use cases:

    1. Import CSV file to DRUPAL database - typically by sample set container

            *this process is aborted (and reverted) if any errors are encountered (entity storage or general taxonomy/node term exceptions)

            *this process cannot handle adding multiple/secondary 'Fish Tag Types' (need to edit existing entry)

            *this process cannot be used to edit/overwrite existing records (need to edit manually or delete)

            *this process does not accept duplicate entries (by 'title')

    2. Export all records to a CSV file

            * field collection items are packaged into a single CSV field as a JSON string object

    3. Export records by sample set container to a CSV file

            * field collection items are packaged into a single CSV field as a JSON string objects

 * Requirements

    This module is currently an isolated/self-contained import and export implementation.

    However, the IFBA database depends on 'taxonomy terms' from the core Marine Institute catalogue.

 * Recommended modules

    All those part of the Drupal core and those related to the Marine Institute base.

 * Installation

    The module will be installed by MI admin and enabled through the Drupal interface 'Extend' and then selecting
    'IFBA Import and Export CSV Module' (currently located under 'Custom')

    It also also possible to install using Drush or Drupal Console (command line apps for Drupal dev work)

 * Configuration

    The module is currently configured to be installed under 'modules/custom' as indicate by the info.yml file with: 'package: Custom'

 * Troubleshooting

    None (as yet).

 * FAQ

    Why is the import and export rather slow?

    Both import and export functions will be relatively slow due to the abstracted nature of the Drupal database.
    For example, when importing a single field taxonomy term such as 'Atlantic Salmon', we have to iterate all terms
    in that 'controlled list' to find its match integer number representation. This number then populates the database
    with the correct term. Similarly, when creating a new record and checking for potential duplicates, we have to
    iterate every 'Sample ID' (title) to find potential clashes.


 * Maintainers

    ?

 * Tests

    Use Cases:

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

    *Export does not generate any exceptions

    SIDE NOTE:

    **It was not possible to conduct any functional tests using PHPUnit on this module due to an issue with the
    MI catalogue core. At path: 'C:\BitnamiSites\MIDataCatalogue\profiles\agov\..' there is an issue with code/directory
    regeneration and thus 'Fatal error: Cannot redeclare page_cache_help()' on attempting to test.