.. include:: ../../Includes.rst.txt

.. _admin-manual-ts-configuration:

Configuration over TypoScript
-----------------------------


.. _ts-plugin-tx-filelist-filelist:

plugin.tx_filelist.settings
---------------------------

This table is an overview of the main keys in the plugin configuration
``plugin.tx_filelist.settings``:

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
   dateFormat_                                           :ref:`t3tsref:data-type-string`                                       yes                     "d.m.Y H:i"
   fileIconRootPath_                                     :ref:`t3tsref:data-type-string`                                       yes                     "EXT:file_list/Resources/Public/Icons/Files/"
   newDurationMaxSubfolders_                             :ref:`t3tsref:data-type-integer`                                      yes                     3
   `extension.category.<name>`_                          :ref:`t3tsref:data-type-string`                                       no                      *empty*
   `extension.remap.<extension>`_                        :ref:`t3tsref:data-type-string`                                       no                      *empty*
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

Allowed root folder or array of allowed root folders. This forces the folder of
all plugins to be within these folders' hierarchy.


.. _ts-plugin-tx-filelist-filelist-dateFormat:

dateFormat
""""""""""

.. code-block:: typoscript

   plugin.tx_filelist.settings.dateFormat = d.m.Y H:i:s

Format used by the default templates to show file's creation date.


.. _ts-plugin-tx-filelist-filelist-fileIconRootPath:

fileIconRootPath
""""""""""""""""

.. code-block:: typoscript

   plugin.tx_filelist.settings.fileIconRootPath = EXT:<extension-key>/Resources/Public/Icons/FileTypes/

Path to the directory containing icons for file types (either relative to site
root or prefixed with an extension name).


.. _ts-plugin-tx-filelist-filelist-newDurationMaxSubfolders:

newDurationMaxSubfolders
""""""""""""""""""""""""

.. code-block:: typoscript

   plugin.tx_filelist.settings.newDurationMaxSubfolders = 3

Number of subdirectory levels to check for new files (in order to show a "new"
badge for folders).


.. _ts-plugin-tx-filelist-filelist-extension-category:

extension.category.<name>
"""""""""""""""""""""""""

.. code-block:: typoscript

   plugin.tx_filelist.settings.extension.category.<name> = extension1, extension2

Comma-separated list of extensions belonging to the category "name".

When a file link is rendered, the extension searches for a dedicated icon (e.g.,
:file:`docx.png` or :file:`docx.gif` in directory fileIconRootPath_. If this
dedicated icon is not found, the category icon will be used instead
(:file:`category_name.png`).

Default are categories "archive", "document", "flash", "image", "sound",
"source" and "video" defined with all common corresponding file extensions.


.. _ts-plugin-tx-filelist-filelist-extension-remap:

extension.remap.<extension>
"""""""""""""""""""""""""""

.. code-block::

   plugin.tx_filelist.settings.extension.remap.<extension1> = extension2

Remapping of extensions before falling back to the category icon. If "docx" is
remapped to "doc" with

.. code-block:: typoscript

	extension.remap.docx = doc

and a ".docx" file is encountered when rendering the file link, a
:file:`docx.png` or :file:`docx.gif` icon will be searched for.

If not found, a :file:`doc.png` or :file:`doc.gif` icon will be searched for to
be used instead. And if not found, the category icon will be used as a fallback
option.
