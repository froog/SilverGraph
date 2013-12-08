<?php
/**
 * Class Silvergraph
 *
 * Generates data model graphs from SilverSripe DataObjects, displaying database fields, relations and ancestry
 *
 * Creates directed graphs in the "dot" format, to be used by the GraphViz dot command
 * (see http://www.graphviz.org/), to then create png images from that dot format
 *
 *
 * Usage:
 *
 * Command line:
 *
 * Default png image:   sake Silvergraph/png > datamodel.png
 * Parameters:          sake Silvergraph/png location=mysite,cms inherited=true > datamodel.png
 * Default dot file:    sake Silvergraph/dot > datamodel.dot
 *
 * Browser: (only logged in as admin)
 *
 * mysite.com/Silvergraph/png
 * mysite.com/Silvergraph/png?location=mysite,cms&inherited=true
 *
 *
 * Parameters:
 *
 *  Specify the folder to look for classes under
 *  location=mysite <default>   Only graph classes under the /mysite folder
 *  location=/                  Graph ALL classes in every module (warning - may take a long time and could generate a large .png)
 *  location=mysite,mymodule    Only graph classes under /mysite and /mymodule folders
 *
 *  How far to traverse and render relations
 *  depth=0  Don't show any relations
 *  depth=1  Only show relations between included classes
 *  depth=2  Show next level of classes
 *
 *  Remove specific classes from the graph
 *  exclude=SiteTree
 *  exlcude=SiteTree,File
 *
 *  How far to show decesndants
 *  ancestry=0	Don't show any ancestry relations
 *  ancestry=1   <default> Show only immediate descendants
 *
 *  inherited-relations=0 <default> Don't show inherited relations
 *  inherited-relations=1			Show inherited relations (verbose)
 *
 *  include-root=0 <default>    Don't graph DataObject itself
 *  include-root=1              Graph DataObject
 *
 *  group = 1 <default>  Don't group by folders
 *  group = 0            Group the folders(modules) into their own boxes
 *
 *
 */

class Silvergraph extends CliController {


    private static $allowed_actions = array(
        "dot",
        "png"
    );

    private function paramDefault($param, $default = null, $type = "string") {
        $value = $this->request->getVar($param);
        if (empty($value) ||
            ($type == "numeric" && !is_numeric($value))) {
            $value= $default;
        }
        return $value;
    }

    /**
     * Generates a GraphViz dot template
     *
     * @return String a dot compatible data format
     */
    public function dot(){

        $location =     $this->paramDefault('location', 'mysite');
        $depth =        $this->paramDefault('depth', 1, 'numeric');
        $ancestry =     $this->paramDefault('ancestry', 1, 'numeric');
        $inherited_relations =    $this->paramDefault('inherited-relations', 0, 'numeric');
        $include_root = $this->paramDefault('include-root', 0, 'numeric');
        $exclude =      $this->paramDefault('exclude');
        $group =        $this->paramDefault('group', 0, 'numeric');

        $renderClasses = array();

        //Get all DataObject subclasses
        $dataClasses = ClassInfo::subclassesFor('DataObject');

        //Remove DataObject itself
        array_shift($dataClasses);

        //Get all classes in a specific folder(s)
        $folders = explode(",", $location);
        $folderClasses = array();
        foreach($folders as $folder) {
            $folderClasses[$folder] = ClassInfo::classes_for_folder($folder);
        }

        $excludeArray = explode(",", $exclude);

        //Get the intersection of the two - grouped by the folder
        foreach($dataClasses as $key => $dataClass) {
            foreach($folderClasses as $folder => $classList) {
                foreach($classList as $folderClass) {
                    if (strtolower($dataClass) == strtolower($folderClass)) {;
                        //Remove all excluded classes
                        if (!in_array($dataClass, $excludeArray)) {
                            $renderClasses[$folder][$dataClass] = $dataClass;
                        }
                    }
                }
            }
        }

        $folders = new ArrayList();

        foreach($renderClasses as $folderName => $classList) {

            $folder = new DataObject();
            $folder->Name = $folderName;
            $folder->Group = ($group == 1);
            $classes = new ArrayList();

            foreach ($classList as $className) {
                //Create a singleton of the class, to use for has_one,etc  instance methods
                $singleton = singleton($className);

                //Create a blank DO to use for rendering on the template
                $class = new DataObject();
                $class->ClassName = $className;

                //Get all the data field for the class
                //NOTE - custom_database_fields doesn't get inheirted fields
                $dataFields = DataObject::custom_database_fields($className);
                $fields = new ArrayList();
                foreach($dataFields as $fieldName => $dataType) {
                    $field = new DataObject();
                    $field->FieldName = $fieldName;

                    //special case - Enums are too long - put new lines on commas
                    if (strpos($dataType, "Enum") === 0) {
                        $dataType = str_replace(",", ",<br/>", $dataType);
                    }

                    $field->DataType = $dataType;
                    $fields->push($field);
                }

                $class->FieldList = $fields;

                //Get all the relations for the class
                if ($depth > 0) {

                    if ($inherited_relations == 1) {
                        $config = Config::INHERITED;
                    } else {
                        $config = Config::UNINHERITED;
                    }

                    $hasOneArray = Config::inst()->get($className, 'has_one', $config);
                    $hasManyArray = Config::inst()->get($className, 'has_many', $config);
                    $manyManyArray = Config::inst()->get($className, 'many_many', $config);

                    //TODO - what's the difference between:
                    /*
                    $hasOneArray = Config::inst()->get($className, 'has_one');
                    $hasManyArray = Config::inst()->get($className, 'has_many');
                    $manyManyArray = Config::inst()->get($className, 'many_many');

                    //and

                    $hasOneArray = $singleton->has_one();
                    $hasManyArray = $singleton->has_many();
                    $manyManyArray = $singleton->many_many();
                    //Note - has_() calls are verbose - they retrieve relations all the way down to base class
                    // ?? eg; for SiteTree, BackLinkTracking is a belongs_many_many
                    */

                    //$belongsToArray = $singleton->belongs_to();
                    //print_r(ClassInfo::ancestry($className));
                    //print_r($singleton->getClassAncestry());


                    //If ancestry = 0, remove the "Parent" relation in has_one
                    /*if ($ancestry == 0 && isset($hasOneArray["Parent"])) {
                        unset($hasOneArray["Parent"]);
                    }*/

                    //Add parent class to HasOne
                    //Remove the default "Parent" because thats the final parent, rather than the immediate parent
                    unset($hasOneArray["Parent"]);
                    $classAncestry = ClassInfo::ancestry($className);
                    //getClassAncestry returns an array ordered from root to called class - to get parent, reverse and remove top element (called class)
                    $classAncestry = array_reverse($classAncestry);
                    array_shift($classAncestry);
                    $parentClass = reset($classAncestry);
                    $hasOneArray["Parent"] = $parentClass;

                    //Ensure DataObject is not shown if include-root = 0
                    if ($include_root == 0 && $parentClass == "DataObject") {
                        unset($hasOneArray["Parent"]);
                    }

                    $class->HasOne = self::relationObject($hasOneArray, $excludeArray);
                    $class->HasMany = self::relationObject($hasManyArray, $excludeArray);
                    $class->ManyMany = self::relationObject($manyManyArray, $excludeArray);

                }

                $classes->push($class);
            }

            $folder->Classes = $classes;
            $folders->push($folder);
        }

        $this->customise(array(
            "Folders" => $folders
        ));


        return $this->renderWith("Silvergraph");

    }

    public static function relationObject($relationArray, $excludeArray) {
        $relationList = new ArrayList();
        if (is_array($relationArray)) {
            foreach($relationArray as $name => $remoteClass) {
                //Only add the relation if it's not in the exclusion array
                if (!in_array($remoteClass, $excludeArray)) {
                    $relation = new DataObject();
                    $relation->Name = $name;
                    $relation->RemoteClass = $remoteClass;
                    $relationList->push($relation);
                }
            }
        }
        return $relationList;
    }

    /** Calls the local dot command to generates a png file based off dot();
     *
     * NOTE: Requires graphviz & dot to be installed locally
     * (eg;  apt-get install graphviz)
     *
     */
    public function png() {
        $dot = $this->dot();

        $cmd = 'dot -Tpng';

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")  // stdout is a pipe that the child will write to
        );

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout

            fwrite($pipes[0], $dot);
            fclose($pipes[0]);

            $png_content = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);

            if (!empty($error)) {
                user_error("Couldn't execute dot command, ensure graphviz is installed and dot is on path. Shell error: $error");
            }

            //Return the content as a png
            header('Content-type: image/png');
            echo $png_content;
        }
    }

    /*public static function subclassesInFolder($class, $folderPath) {
        $absFolderPath  = Director::getAbsFile($folderPath);
        $matchedClasses = array();
        $descendants = SS_ClassLoader::instance()->getManifest()->getDescendantsOf($class);
        $result      = array($class => $class);

        if ($descendants) {
            return $result + ArrayLib::valuekey($descendants);
        } else {
            return $result;
        }
    }*/


}