<?php

$filename = "saved_data.json";
$content = $_POST['content'];
$file = fopen($filename, "w");
if (!$file) {
    $ret['code'] = -1;
    $ret['message'] = "could not open file '$filename'";
    echo json_encode($ret);
    exit;
}
fwrite($file, $content);
fclose($file);
$ret['code'] = 0;
$ret['message'] = "success";
$ret['filename'] = "$filename";
$ret['content'] = "$content";
echo json_encode($ret);
exit;
