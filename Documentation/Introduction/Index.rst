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

This extension provides a frontend plugin which shows a list of files
and folders in a specified directory on the file system.

You can sort the files in this directory over the backend by name,
size or create date. Also frontend users may, if you want, sort over
the frontend-plugin. The files will be sorted by name as default.

There is also an option to display files as new (with a localized text
after the name).


.. _screenshots:

Screenshots
-----------

.. image:: ../Images/list-new.png

You want to show additional columns of information? Use one of the
hook (Samples/metadata):

.. image:: ../Images/list-metadata.png

You may even transform your list of pictures into a small photo
gallery that provides lightbox click-enlarge feature, again with a
hook (Samples/gallery):

.. image:: ../Images/list-gallery.png

Another example would be to show a list of documents with their
available translations as clickable flags, as usual with a hook
(Samples/multilingual):

.. image:: ../Images/list-multilingual.png
