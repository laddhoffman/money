<?php

$file_name = $_POST['filename'];
$content = $_POST['content'];
$file_path = "setups/$file_name";
$file = fopen($file_path, "w");
if (!$file) {
    $ret['code'] = -1;
    $ret['message'] = "could not open file '$file_path'";
    echo json_encode($ret);
    exit(1);
}
fwrite($file, $content);
fclose($file);
$ret['code'] = 0;
$ret['message'] = "saved successfully";
$ret['filename'] = "$file_name";
$ret['content'] = "$content";
echo json_encode($ret);
exit(0);
