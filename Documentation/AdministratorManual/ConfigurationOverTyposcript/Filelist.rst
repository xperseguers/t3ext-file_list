.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _ts-plugin-tx-filelist-filelist:

plugin.tx_filelist.settings
---------------------------

This table is an overview of the main keys in the plugin configuration ``plugin.tx_filelist.settings``:

.. only:: html

	.. contents::
		:local:
		:depth: 1


Properties
^^^^^^^^^^

.. container:: ts-properties

	===================================================== ===================================================================== ======================= ==================
	Property                                              Data type                                                             :ref:`t3tsref:stdwrap`  Default
	===================================================== ===================================================================== ======================= ==================
	path_                                                 :ref:`t3tsref:data-type-string`                                       yes                     *empty*
	root_                                                 :ref:`t3tsref:data-type-string`, array                                yes                     *empty*
	===================================================== ===================================================================== ======================= ==================


Property details
^^^^^^^^^^^^^^^^

.. only:: html

	.. contents::
		:local:
		:depth: 1


.. _ts-plugin-tx-filelist-filelist-path:

path
""""

.. code-block:: typoscript

    plugin.tx_filelist.settings.path = file:1:/path/to/folder/

Root folder for the plugin.


.. _ts-plugin-tx-filelist-filelist-root:

root
""""

.. code-block:: typoscript

    plugin.tx_filelist.settings.root = file:1:/path/to/folder/

*or*

.. code-block:: typoscript

    plugin.tx_filelist.settings.root {
        10 = file:1:/path/to/folder/
        20 = file:2:/some/other/
    }

Allowed root folder or array of allowed root directories. This forces the folder of all plugins to be within
these folders' hierarchy.
