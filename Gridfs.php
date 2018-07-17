<?php 
class Gridfs {

    public $MongoObject;

    public function __construct($db = 'multi') {
		$config = include('GridConfig.php');
        $this->MongoObject = new Object();
        $this->MongoObject->client = new MongoClient("mongodb://".$config['host'].":".$config['port']);
        $this->MongoObject->db = $this->MongoObject->client->selectDB($db);
    }


    public function renderImage($id = null,$prefix = null){
	    $grid = $this->MongoObject->db->getGridFS($prefix);                    // Initialize GridFS
	    $id = (gettype($id) == 'object') ? $id : new MongoId($id);
	    $file = $grid->get($id);
	    //header('Content-type: '.$file->file['contentType']);
	    header('Content-type: image/jpeg;');
	    $imageFile = $file->getBytes(); 

	    header('Content-type: image/jpeg');
	    header("Content-Length: " . strlen($imageFile));
	    ob_clean();
	    echo $imageFile;
	    exit(0);
    }

    public function getContentType($id = null, $prefix = null) {
        $grid = $this->MongoObject->db->getGridFs($prefix);
        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);
        return $file->file['contentType'];
    }

    /**
     * Works, but needs to be fleshed out more and pointers and what not
     * need to be defined.
     */
    public function render($id = null,$fs='records'){
        $grid = $this->MongoObject->db->getGridFS($fs);                    // Initialize GridFS
        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);

        header('Content-Type: '.$file->file["contentType"]);
        //header('Content-Disposition: attachment; filename='.$file->file['filename']); 
        echo $file->getBytes(); 

        exit(0);

    }
    public function readFile($collection = null,$id = null){
        $grid = $this->MongoObject->db->getGridFS($collection);
        $file = $grid->get($id);

        return $file->getBytes();
    }

    public function uploadFiles($files = array(),$prefix = 'uploads'){
        $grid = $this->MongoObject->db->getGridFS($prefix);                    // Initialize GridFS

        $file_ids = array();
        foreach($files as $key => $file){
            if($file['name'] == ''){
                continue;
            }

            $name = $file['name'];        // Get Uploaded file name
            $tmp_name = $file['tmp_name'];        // Get Uploaded file name
            $type = $file['type'];        // Try to get file extension
            $id = $grid->storeFile($tmp_name,array("filename" => $name,"contentType" => $type, "aliases" => null, "metadata" => null));    // Store uploaded file to GridFS
            unlink($tmp_name);

            /* Add additional metadata related to the file if required */
            //$files = $this->MongoObject->db->fs->files;
            //$files->update(array("_id" => $id), array('$set' => array("filename" => $name,"contentType" => $type, "aliases" => null, "metadata" => null)));

            $file_ids[$key]['id'] = $id;
            $file_ids[$key]['name'] = $name;
            if(isset($file['data_type'])){
                $file_ids[$key]['data_type'] = $file['data_type'];
            }else{
                $file_ids[$key]['data_type'] = 'attachment';
            }
        }

        return $file_ids;
    }

    public function downloadFile($id=null,$prefix='uploads'){
        $grid = $this->MongoObject->db->getGridFS($prefix);                    // Initialize GridFS

        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);

        if ( (substr($file->file['filename'],-3) == 'zip')) { /*|| (substr($file->file['filename'],-3) == 'pdf') ) {*/
           /* Any file types you want to be downloaded can be listed in this */
           header('Content-Type: application/octet-stream');
           header('Content-Disposition: attachment; filename='.$file->file['name']); 
           header('Content-Transfer-Encoding: binary');
           $cursor = $this->MongoObject->db->fs->chunks->find(array("files_id" => $id))->sort(array("n" => 1));
           foreach($cursor as $chunk) {
              echo $chunk['data']->bin;
           }
        }
        else {
           header('Content-Type: '.$file->file["contentType"]);
           header('Content-Disposition: attachment; filename='.$file->file['filename']); 
           ob_clean();
           echo $file->getBytes(); 
        }   

        exit(0);
    }

    public function writeToTmp($id=null, $prefix='uploads'){
        $grid = $this->MongoObject->db->getGridFS($prefix);
        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $file = $grid->get($id);
        $file->write('/tmp/'.$file->file['filename']);
        return '/tmp/'.$file->file['filename'];
    }

    public function removeFile($id=null,$prefix='uploads'){
        $grid = $this->MongoObject->db->getGridFS($prefix);
        $id = (gettype($id) == 'object') ? $id : new MongoId($id);
        $grid->delete($id);
    }
}
?>

