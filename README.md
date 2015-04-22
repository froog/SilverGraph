SilverGraph
===========

Creates data model visualisations of SilverStripe DataObjects, showing fields, relations and ancestry.
Can output images in .png, .svg and raw GraphViz "dot" format.
Flexible configuration options and can be called from command line and URL.

![SilverGraph example](https://raw.github.com/froog/SilverGraph/master/doc/SilverGraph_example__location=cms,framework,mysite.png)

_Example call: http://example.com/Silvergraph/png?location=cms,framework,mysite_

##Installation##
* Composer/Packagist: Install composer and then run `composer require froog/silvergraph` (* for version)
* Manual: Download and extract the silvergraph folder into the top level of your site, and visit /dev/build?flush=all to rebuild the database.

###Installation on OSX###

* Install Graphviz via Homebrew: `brew install graphviz` and note down the location
* Add the location to your `_ss_environment.php` file, e.g.:    
`define('SILVERGRAPH_GRAPHVIZ_PATH', '/usr/local/Cellar/graphviz/2.38.0/bin/');`

##Requirements##
 * SilverStripe 3.0.0+
 * To create images: GraphViz (latest version) http://www.graphviz.org/ 
  * To install (Debian/Ubuntu): `apt-get install graphviz`  

##Usage##

###Command line: (in site root)###

* Default png image:   `sake Silvergraph/png > datamodel.png` 
* Parameters:   `sake Silvergraph/png location=mysite,cms inherited=1 exclude=SiteTree > datamodel.png` 
* Default dot file:    `sake Silvergraph/dot > datamodel.dot`

###Browser: (logged in as admin)###

* Default png image:   http://example.com/Silvergraph/png
* Parameters:   http://example.com/Silvergraph/png?location=mysite,cms&inherited=1&exclude=SiteTree
* Default dot file: http://example.com/Silvergraph/dot

###Parameters###

####Specify the folder to look for classes under
* `location=mysite` _(default)_   Only graph classes under the /mysite folder
* `location=/`                  Graph ALL classes in every module (warning - may take a long time and could generate a large .png)
* `location=mysite,mymodule`    Only graph classes under /mysite and /mymodule folders

####Remove specific classes from the graph
* `exclude=SiteTree`
* `exclude=SiteTree,File`

####How verbosely to show relations
* `relations=0` Don't show any relations
* `relations=1` _(default)_ Don't show inherited relations
* `relations=2`			Show inherited relations (verbose)

####How verbosely to show fields
* `fields=0` Don't show any fields
* `fields=1` _(default)_ Show only fields defined on self
* `fields=2`			Show inherited fields (verbose)

####How verbosely to show ancestors
* `ancestry=0` Don't show any ancestry relations
* `ancestry=1` _(default)_ Show ancestry relations

####Include DataObject on the graph
* `include-root=0` _(default)_   Don't graph DataObject
* `include-root=1`              Graph DataObject

####Group classes by modules
* `group=0` _(default)_  Don't group by modules
* `group=1`            Group the modules into their own container

####Specify direction graph is laid out
* `rankdir=x`            	Where x is `TB` _(default)_ ,`LR`,`RL`, or `BT` (top-bottom, left-right, right-left, bottom-top)

## TO DO

* Better default styling/colours of the graph
* Less verbose option for relations, eg; combining has_one, has_many paths on the same path
* Better error handling from dot -> png, if error in dot format


