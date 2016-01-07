.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _ts-plugin-tx-filelist-pi1:

plugin.tx_filelist_pi1
----------------------

This table is an overview of the main keys in the plugin configuration ``plugin.tx_filelist_pi1`` (legacy):

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
	`extension.category.<name>`_                          :ref:`t3tsref:data-type-string`                                       no                      *empty*
	`extension.remap.<extension>`_                        :ref:`t3tsref:data-type-string`                                       no                      *empty*
	iconsPathFiles_                                       :ref:`t3tsref:data-type-string`                                       yes                     "EXT:file_list/Resources/Public/Icons/Files/"
	iconsPathFolders_                                     :ref:`t3tsref:data-type-string`                                       yes                     "EXT:file_list/Resources/Public/Icons/Folders/"
	iconsPathSorting_                                     :ref:`t3tsref:data-type-string`                                       yes                     "EXT:file_list/Resources/Public/Icons/Sorting/"
	ignoreFileNamePattern_                                :ref:`t3tsref:data-type-string`                                       no                      "/^(\..\*\|thumbs\.db)$/i"
	ignoreFolderNamePattern_                              :ref:`t3tsref:data-type-string`                                       no                      "/^(\..\*\|CVS)$/i"
	root_                                                 :ref:`t3tsref:data-type-path`, :ref:`t3tsref:data-type-string`        yes                     "fileadmin/"
	templateFile_                                         :ref:`t3tsref:data-type-string`                                       yes                     "EXT:file_list/Resources/Private/Templates/template_pi1.html"
	===================================================== ===================================================================== ======================= ==================


Property details
^^^^^^^^^^^^^^^^

.. only:: html

	.. contents::
		:local:
		:depth: 1


.. _ts-plugin-tx-filelist-pi1-extension-category:

extension.category.<name>
"""""""""""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.extension.category.<name> =` :ref:`t3tsref:data-type-string`

Comma-separated list of extensions belonging to the category "name".

When a file link is rendered, the extension searches for a dedicated icon (e.g., "docx.png" or "docx.gif" in directory
iconsPathFiles_. If this dedicated icon is not found, the category icon will be used instead ("category\_name.png").

Default are categories "archive", "document", "flash", "image", "sound", "source" and "video" defined with all common
corresponding extensions.


.. _ts-plugin-tx-filelist-pi1-extension-remap:

extension.remap.<extension>
"""""""""""""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.extension.remap.<extension> =` :ref:`t3tsref:data-type-string`

Remapping of extensions before falling back to the category icon. If "docx" is remapped to "doc" with

::

	extension.remap.docx = doc

and a ".docx" file is encountered when rendering the file link, a "docx.png" or "docx.gif" icon will be searched for.
If not found, a "doc.png" or "doc.gif" icon will be searched for to be used instead. And if not found, the category icon
will be used as fallback option.


.. _ts-plugin-tx-filelist-pi1-iconsPathFiles:

iconsPathFiles
""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.iconsPathFiles =` :ref:`t3tsref:data-type-string`

Path to the directory containing icons for file extensions and file categories (either relative to site root or prefixed
with an extension name).


.. _ts-plugin-tx-filelist-pi1-iconsPathFolders:

iconsPathFolders
""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.iconsPathFolders =` :ref:`t3tsref:data-type-string`

Path to the directory containing icons for folders (either relative to site root or prefixed with an extension name).


.. _ts-plugin-tx-filelist-pi1-iconsPathSorting:

iconsPathSorting
""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.iconsPathSorting =` :ref:`t3tsref:data-type-string`

Path to the directory containing the sort icons, up and down (either relative to site root or prefixed with an
extension name).


.. _ts-plugin-tx-filelist-pi1-ignoreFileNamePattern:

ignoreFileNamePattern
"""""""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.ignoreFileNamePattern =` :ref:`t3tsref:data-type-string`

Perl compatible regular expression for file names which shouldn't be displayed. For further information on how to use
Perl compatible regular expressions please visit http://php.net/manual/en/book.pcre.php. The default value avoids all
files starting with a "." (system hidden files) as well as those named "Thumbs.db" (Microsoft Windows related thumbnail
files) to be shown in the frontend.


.. _ts-plugin-tx-filelist-pi1-ignoreFolderNamePattern:

ignoreFolderNamePattern
"""""""""""""""""""""""

:typoscript:`plugin.tx_filelist_pi1.ignoreFolderNamePattern =` :ref:`t3tsref:data-type-string`

Perl compatible regular expression for folder names which shouldn't be displayed. For further information on how to use
Perl compatible regular expressions please visit http://php.net/manual/en/book.pcre.php. The default value avoids all
folders starting with a "." (system hidden folders) as well as those named "CVS" (versioning system folders) to be shown
in the frontend.


.. _ts-plugin-tx-filelist-pi1-root:

root
""""

:typoscript:`plugin.tx_filelist_pi1.root =` :ref:`t3tsref:data-type-path` *or* :ref:`t3tsref:data-type-string`

Allowed root directory (either absolute or relative to site root). This forces the directory of all plugins to be within
this directory.


.. _ts-plugin-tx-filelist-pi1-templateFile:

templateFile
""""""""""""

:typoscript:`plugin.tx_filelist_pi1.templateFile =` :ref:`t3tsref:data-type-string`

Template to use.
