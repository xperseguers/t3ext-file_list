.. include:: ../../Includes.rst.txt

.. _admin-manual-gallery:

Image gallery (example)
-----------------------

This chapter is an example on how you may extend this extension to be used as an
image gallery.

.. only:: html

   .. contents::
      :local:
      :depth: 1


.. _admin-manual-gallery-extension:

Create a dedicated extension
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create an extension skeleton (e.g., ``my_gallery``) under
:file:`typo3conf/ext/my_gallery/`. You basically need two files:

- :file:`ext_emconf.php` (copy and adapt from another extension);
- :file:`ext_icon.png`.

Now we need to register an additional layout for the file list plugin, as
described in chapter :ref:`developer-manual-flexform-templateLayouts`.

Create file :file:`ext_localconf.php`:

.. code-block:: php

   <?php
   defined('TYPO3_MODE') || die();

   $boot = function (string $_EXTKEY) {

       /* ===========================================================================
           Register an "image gallery" layout
       =========================================================================== */
       $GLOBALS['TYPO3_CONF_VARS']['EXT']['file_list']['templateLayouts'][] = [
           'Image Gallery', // You may use a LLL:EXT: label reference here of course!
           'MyGallery',
       ];

   };

   $boot('<your_extension_key>');
   unset($boot);

We could add the few lines of TypoScript to our existing template but let do
that with a staticTS, as usual.

Create files :file:`ext_tables.php`:

.. code-block:: php

   <?php
   defined('TYPO3_MODE') || die();

   $boot = function (string $_EXTKEY) {

       /* ===========================================================================
           Register default TypoScript
       =========================================================================== */
       \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
           $_EXTKEY,
           'Configuration/TypoScript/',
           'My Gallery'
       );

   };

   $boot('<your_extension_key>');
   unset($boot);

and :file:`Configuration/TypoScript/setup.txt`:

.. code-block:: typoscript

   plugin.tx_filelist {
       view {
           partialRootPaths.100 = EXT:my_gallery/Resources/Private/Partials/
       }
   }


.. _admin-manual-gallery-html:

Create the HTML of your gallery
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Yes, we are already nearly ready! Now we just need to create the HTML Fluid
template to be used with key "MyGallery".

Create file :file:`Resources/Private/Partials/MyGallery.html`:

.. code-block:: html

   <html xmlns="http://www.w3.org/1999/xhtml" lang="en"
         xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers"
         xmlns:fl="http://typo3.org/ns/Causal/FileList/ViewHelpers"
         data-namespace-typo3-fluid="true">

       <f:for each="{files}" as="file">

           <figure>
               <a href="{f:uri.image(image: file, maxWidth: 1200, maxHeight: 800)}"
                   rel="lightbox[gallery_{data.uid}]"
                   title="{file.properties.description}">

                   <fl:thumbnail image="{file}" width="256c" height="256c" />
               </a>
               <figcaption>
                   <f:if condition="{file.properties.description}">
                       <f:then>{file.properties.description}</f:then>
                       <f:else>
                           <f:if condition="{file.properties.title}">
                               <f:then>{file.properties.title}</f:then>
                               <f:else>{file.name}</f:else>
                           </f:if>
                       </f:else>
                   </f:if>
               </figcaption>
           </figure>

       </f:for>

   </html>

This is just an example of course! But it shows you how to get a
lightbox-enabled gallery of images with the FAL description or title (or even
file name) as fallback.

Have fun!

.. note::

   By iterating over ``{folders}`` in addition to ``{files}`` your gallery would
   support nested collections of images, based on folders. Just like that.

.. hint::

   If you need to deal with a large list of images, you probably will want to
   paginate it and you may find the `Paginate ViewHelper from Fluid Powered
   TYPO3 <https://fluidtypo3.org/viewhelpers/fluid/master/Widget/PaginateViewHelper.html>`_
   useful...
