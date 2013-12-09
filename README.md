SilverGraph
===========

Creates data model visualisations of SilverStripe DataObjects, showing fields, relations and ancestry.
Can output images in .png, .svg and raw GraphViz "dot" format.
Flexible configuration options and can be called from command line and URL.

##Installation##
* Composer/Packagist: Install composer and then run `composer require froog/silvergraph dev-master`
* Manual: Download and extract the silvergraph folder into the top level of your site, and visit /dev/build?flush=all to rebuild the database.

##Requirements##
 * SilverStripe 3.0.0+
 * To create images: GraphViz (latest version) http://www.graphviz.org/ 
  * To install (Debian/Ubuntu): `apt-get install graphviz`  

##Usage##

###Command line: (in site root)###

* Default png image:   `sake Silvergraph/png > datamodel.png` 
* Parameter example:   `sake Silvergraph/png location=mysite,cms inherited=1 exclude=SiteTree > datamodel.png` 
* Default dot file:    `sake Silvergraph/dot > datamodel.dot`

###Browser: (logged in as admin)###

* Default png image:   http://mysite.com/Silvergraph/png
* Parameter example:   http://mysite.com/Silvergraph/png?location=mysite,cms&inherited=1&exclude=SiteTree
* Default dot file: http://mysite.com/Silvergraph/dot

###Parameters###

####Specify the folder to look for classes under
* `location=mysite` _(default)_   Only graph classes under the /mysite folder
* `location=/`                  Graph ALL classes in every module (warning - may take a long time and could generate a large .png)
* `location=mysite,mymodule`    Only graph classes under /mysite and /mymodule folders

####Remove specific classes from the graph
* `exclude=SiteTree`
* `exclude=SiteTree,File`

####Show or hide inherited relations
* `inherited-relations=0` _(default)_ Don't show inherited relations
* `inherited-relations=1`			Show inherited relations (verbose)

####Include DataObject on the graph
* `include-root=0` _(default)_   Don't graph DataObject
* `include-root=1`              Graph DataObject

####Group classes by modules
* `group=1` _(default)_  Don't group by modules
* `group=0`            Group the modules into their own container

## TO DO

* Better default styling/colours of the graph
* create svg output method
* Less verbose option for relations, eg; combining has_one, has_many paths on the same path
* Showing inherited fields on class tables
* Better error handling from dot -> png, if error in dot format

####How far to traverse and render relations
* depth=0  Don't show any relations
* depth=1  Only show relations between included classes
* depth=2  Show next level of classes

####How far to show decesndants
* ancestry=0 Don't show any ancestry relations
* ancestry=1  <default> Show only immediate descendants

####Show or hide inherited database fields
* inherited-fields=0 <default> Don't show inherited fields
* inherited-fields=1			Show inherited fields (verbose)
