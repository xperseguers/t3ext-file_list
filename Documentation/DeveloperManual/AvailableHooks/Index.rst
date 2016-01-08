.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _admin-manual-hooks:

Available hooks (legacy)
------------------------

This extensions provides two hooks:

``$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['extraItemMarkerHook']``

    Allows additional template markers to be defined and may be used to show a meaningful description instead of the
    filename as text of the link.

``$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['file_list']['filesDirectoriesHook']``

    Allows the list of files in a directory to be processed before it is rendered in frontend. May be used, for
    instance, to generate a multilingual list of files, each column containing the translation of a master document.

.. note::
    We provide a few samples showing you how to use these hooks in directory :file:`Resources/Private/Samples/` of the
    File List extension. Please read corresponding :file:`README` files to understand how to install and use them.

.. caution::
    Samples are not meant to be used as-this on a productive system, they are provided instead to give you ideas and
    examples on how to use those hooks.
