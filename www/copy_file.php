<?php

$file_name = $_POST['filename'];
if (isset($_SERVER['REMOTE_USER'])) {
	$user = $_SERVER['REMOTE_USER'];
} else {
	$user = 'guest';
}
$file_path = "setups/$user/$file_name";
$content = file_get_contents($file_path);
if (!$content) {
    $ret['status'] = -1;
    $ret['message'] = "could not read file '$file_name'. $errstr";
    echo json_encode($ret);
    exit(1);
}
$file_dest = $file_path . " Copy";
$file_name_dest = $file_name . " Copy";
$file = fopen($file_dest, "w");

// get into the file content and modify the name
$content_obj = json_decode($content);
$content_obj->name .= " Copy";
$content = json_encode($content_obj, JSON_PRETTY_PRINT);

if (!$file) {
    $ret['status'] = -1;
    $ret['message'] = "could not write file '$file_name_dest'. $errstr";
    echo json_encode($ret);
    exit(1);
}
fwrite($file, $content);
fclose($file);
$ret['status'] = 0;
$ret['message'] = "saved file '$file_name_dest' successfully";
$ret['filename'] = "$file_name_dest";
$ret['content'] = "$content";
echo json_encode($ret);
exit(0);
