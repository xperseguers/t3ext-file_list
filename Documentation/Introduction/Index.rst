.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _introduction:

Introduction
============

.. _what-it-does:

What does it do?
----------------

This extension provides a frontend plugin which shows a list of files and folders in a specified directory on the file
system or using more advanced FAL selectors such as categories or collections of files.

You can sort the files in this directory over the Backend by name, size or creation date. Also Frontend users may, if
you want, sort over the Frontend plugin. The files will be sorted by name as default.

There is also an option to display files as new (with a localized text after the name).


.. _screenshots:

Screenshots
-----------

TODO


.. _screenshots-legacy:

Legacy plugin
^^^^^^^^^^^^^

Here are a few screenshots using the legacy plugin (which only supports local storages with FAL and is using a
marker-based template instead of Fluid).

.. caution::
    You are encouraged to update your website and use the new plugin instead of the old one. The legacy plugin is not
    maintained anymore and will be dropped altogether in a future release of this extension.

.. image:: ../Images/list-new.png

You want to show additional columns of information? Use one of the hook (:file:`Resources/Private/Samples/metadata/`):

.. image:: ../Images/list-metadata.png
    :alt: Additional columns of information (legacy plugin)

You may even transform your list of pictures into a small photo gallery that provides lightbox click-enlarge feature,
again with a hook (:file:`Resources/Private/Samples/gallery/`):

.. image:: ../Images/list-gallery.png
    :alt: Photo gallery (legacy plugin)

Another example would be to show a list of documents with their available translations as clickable flags, as usual with
a hook (:file:`Resources/Private/Samples/multilingual/`):

.. image:: ../Images/list-multilingual.png
    :alt: Multilingual list of files (legacy plugin)
