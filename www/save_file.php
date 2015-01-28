<?php

$file_name = $_POST['filename'];
$content = $_POST['content'];
$file_path = "setups/$file_name";
$file = fopen($file_path, "w");
if (!$file) {
    $ret['status'] = -1;
    $ret['message'] = "could not write file '$file_name'. $errstr";
    echo json_encode($ret);
    exit(1);
}
fwrite($file, $content);
fclose($file);
$ret['status'] = 0;
$ret['message'] = "saved file '$file_name' successfully";
$ret['filename'] = "$file_name";
$ret['content'] = "$content";
echo json_encode($ret);
exit(0);
