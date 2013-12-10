<?php
/**
 * Class Silvergraph
 *
 * Generates data model graphs from SilverSripe DataObjects, displaying database fields, relations and ancestry
 *
 * Refer to README.md for usage guide and requirements
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
        if  (($type == "string" && empty($value)) ||
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

        $opt = array();

        $opt['location'] =      $this->paramDefault('location', 'mysite');
        $opt['ancestry'] =      $this->paramDefault('ancestry', 1, 'numeric');
        $opt['relations'] =     $this->paramDefault('relations', 1, 'numeric');
        $opt['fields'] =        $this->paramDefault('fields', 0, 'numeric');
        $opt['include_root'] =  $this->paramDefault('include-root', 0, 'numeric');
        $opt['exclude'] =       $this->paramDefault('exclude');
        $opt['group'] =         $this->paramDefault('group', 0, 'numeric');

        $renderClasses = array();

        //Get all DataObject subclasses
        $dataClasses = ClassInfo::subclassesFor('DataObject');

        //Remove DataObject itself
        array_shift($dataClasses);

        //Get all classes in a specific folder(s)
        $folders = explode(",", $opt['location']);
        $folderClasses = array();
        foreach($folders as $folder) {
            $folderClasses[$folder] = ClassInfo::classes_for_folder($folder);
        }

        $excludeArray = explode(",", $opt['exclude']);

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
            $folder->Group = ($opt['group'] == 1);
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
                if ($opt['relations'] > 0) {

                    if ($opt['relations'] > 1) {
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
                    if ($opt['include_root'] == 0 && $parentClass == "DataObject") {
                        unset($hasOneArray["Parent"]);
                    }

                    //if ancestry = 0, remove the "Parent" relation in has_one
                    if ($opt['ancestry'] == 0 && isset($hasOneArray["Parent"])) {
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

        //Execute the dot command on the local machine.
        //Using pipes as per the example here: http://php.net/manual/en/function.proc-open.php
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
}