.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _admin-manual-best-practices-typoscript:

Integration with TypoScript
^^^^^^^^^^^^^^^^^^^^^^^^^^^

This page gives you some examples which you can use for integrating EXT:file_list into a website.

.. only:: html

    .. contents::
        :local:
        :depth: 1


Add file_list by TypoScript
"""""""""""""""""""""""""""

If EXT:file_list should be integrated by using TypoScript only, you can use this code snippet:

.. code-block:: typoscript

    lib.filelist = USER
    lib.filelist {
        userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
        extensionName = FileList
        vendorName = Causal
        pluginName = Filelist

        switchableControllerActions {
            File {
                1 = list
            }
        }

        settings < plugin.tx_filelist.settings
        settings {
            path = file:1:/path/to/directory/
            mode = FOLDER
            includeSubfolders = 1
            templateLayout = ThumbnailDescription
        }
    }

Now you can use the object lib.filelist.
