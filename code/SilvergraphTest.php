<?php

/**
 * Class SilvergraphTest
 *
 * Generates data model graphs from SilverSripe DataObjects, displaying database fields and relations
 *
 *
 * Parameters:
 *
 *  location=mysite <default>   Only graph classes under the /mysite folder
 *  location=/                  Graph ALL classes in every module (warning - takes a long time and will generate a large .png)
 *  location=mysite,mymodule    Only graph classes under /mysite and /mymodule folders
 *
 *  depth - how far to traverse and render relations
 *
 *  depth=0  Don't show any relations
 *  depth=1  Only show relations between included classes
 *  depth=2  Show next level of classes
 *
 * exclude - remove specific classes from the graph
 * exclude=SiteTree
 * exlcude=SiteTree,File
 *
 * ancestry - how far to show decesndants
 * ancestry=0	Don't show any ancestry relations
 * ancestry=1   <default> Show only immediate descendants 
 *
 */

class SilvergraphTest extends Page {

}

class SilvergraphTest_Controller extends Page_Controller {


    private static $allowed_actions = array(
        "dot",
        "png"
    );

    /**
     * Generates a GraphViz dot template
     *
     * @return HTMLText
     */
    public function dot(){
        //Parameters
        $location = "userforms/code";
        //$location = "mysite,cms,framework";
        //location = "mysite";
        $exclude = "";
        $depth = 1;
		$ancestry = 1;

        $renderClasses = array();

        //Get all DataObject subclasses
        $dataClasses = ClassInfo::subclassesFor('DataObject');

        //Remove DataObject itself
        array_shift($dataClasses);

        //Get all classes in a specific folder(s)
        $folders = explode(",", $location);
        $folderClasses = array();
        foreach($folders as $folder) {
            $folderClasses = array_merge($folderClasses, ClassInfo::classes_for_folder($folder));
        }

        //Get the intersection of the two
        foreach($dataClasses as $key => $dataClass) {
            foreach($folderClasses as $folderClass) {
                if (strtolower($dataClass) == strtolower($folderClass)) {;
                    $renderClasses[$dataClass] = $dataClass;
                }
            }
        }

        //Remove all excluded classes
        $excludeArray = explode(",", $exclude);
        foreach($excludeArray as $exclude) {
            unset($renderClasses[$exclude]);
        }

        $classes = new ArrayList();

        foreach($renderClasses as $className) {
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

                //Note - has_() calls are verbose - they retrieve relations all the way down to base class

                /*if ($depth == 1) {
                    $hasOneArray = $singleton->config()->has_one;
                    $hasManyArray = $singleton->config()->has_many;
                    $manyManyArray = $singleton->config()->many_many;
                } else {*/
                    $hasOneArray = $singleton->has_one();
                    $hasManyArray = $singleton->has_many();
                    $manyManyArray = $singleton->many_many();
					
					//$belongsToArray = $singleton->belongs_to();
					//print_r(ClassInfo::ancestry($className));
					//print_r($singleton->getClassAncestry());
                //}
				
				//If ancestry = 0, remove the "Parent" relation in has_one
				if ($ancestry == 0 && isset($hasOneArray["Parent"])) {
					unset($hasOneArray["Parent"]);
				}

                $class->HasOne = self::relationObject($hasOneArray, $excludeArray);
                $class->HasMany = self::relationObject($hasManyArray, $excludeArray);
                $class->ManyMany = self::relationObject($manyManyArray, $excludeArray);

            }

            $classes->push($class);
        }

        $this->customise(array(
            "Classes" => $classes
        ));


        return $this->renderWith("SilvergraphTest");

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