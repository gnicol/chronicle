P4CMS Documentation
-------------------

This folder contains the documentation for P4CMS in HTML, LaTeX, and
PDF formats.

If you do not find any documentation within this folder, you may have
to generate it with doxygen. Full documentation generation requires
the following software to be installed:

    doxygen
    perl
    graphviz
    latex

If you have all the pre-requisites installed, you can build the
HTML and LaTeX documentation by running the following command in the
parent folder:

    doxygen

Documentation builds may take some time to complete as caller and
called_by graphs are enabled by default.

In order to build the PDF documentation, run the following commands:

    cd latex
    make pdf
    mv refman.pdf ../p4cms_api.pdf
