<?php

if (isset($_SERVER['REMOTE_USER'])) {
	$user = $_SERVER['REMOTE_USER'];
} else {
	$user = 'guest';
}

// get a list of files as a json array

$file_names_json = $_POST['filenames'];
$file_names = json_decode($file_names_json, true);

/* Build an array of each setup described in setups/ */
$save_dir = "setups/$user";
$files = scandir($save_dir);

$files_to_keep = array();
$files_to_remove = array();

foreach ($files as $file) {
        if (preg_match('/^\.+$/', $file)) {
                continue;
        }
		if (is_dir($save_dir . '/' . $file)) {
			continue;
		}
        // echo "$file\n";
        // $setups[] = json_decode(file_get_contents("$save_dir/$file"));

		// check if this is in our list of files to keep
		$keep_this = in_array($file, $file_names);
		if ($keep_this) {
			$files_to_keep[] = $file;
		} else {
			$files_to_remove[] = $file;
		}
}

$ret['message'] = "files to keep: ".implode(",", $files_to_keep)."; ";

foreach ($files_to_remove as $file) {
	$timestamp = date('c');
	$path_orig = $save_dir . '/' . $file;
	$path_dest = $save_dir . '/removed/' . $timestamp . ' ' . $file;
	mkdir(dirname($path_dest), 0700, true);
	$ret = rename($path_orig, $path_dest);
	if ($ret) {
		$ret['message'] .= "removed '$file'; ";
	} else {
		$ret['message'] .= "failed to remove '$file'; ";
	}
}

$ret['status'] = 0;
echo json_encode($ret);
exit(0);

