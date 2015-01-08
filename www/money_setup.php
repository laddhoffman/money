<?php

/* Build an array of each setup described in saved/ */
$save_dir = 'saved';
$files = scandir($save_dir);
$setups = array();
foreach ($files as $file) {
    if (preg_match('/^\.+$/', $file)) {
        continue;
    }
    // echo "$file\n";
    $setups[] = json_decode(file_get_contents("$save_dir/$file"));
}

$initial_data = json_encode($setups, JSON_PRETTY_PRINT);
// echo $initial_data."\n";
// exit;
?>

<html>
<head>
<title>Laddtropia - Finance JSON Editor</title>

<!-- Foundation CSS framework (Bootstrap and jQueryUI also supported) -->
<!-- <link rel='stylesheet' href='/foundation5/css/foundation.css'> -->

<!-- Bootstrap CSS framework -->
<link rel='stylesheet' href='/bootstrap3/dist/css/bootstrap.css'>

<!-- JQuery (required for Bootstrap) -->
<!-- <script src='/jquery-1.11.1.js'></script> -->

<!-- Bootstrap JS Plugins -->
<!-- <script src='/bootstrap3/dist/js/bootstrap.js'> -->

<!-- Font Awesome icons (Bootstrap, Foundation, and jQueryUI also supported) -->
<!-- <link rel='stylesheet' href='/font-awesome-4.2.0/css/font-awesome.css'> -->

<!-- JSON Editor -->
<script src='jsoneditor.js'></script>

</head>
<body>
    <div id='submit_status'></div>
    <button id='submit'>Submit</button>
    <button id='restore'>Restore to Default</button>
    <button id='enable_disable'>Disable/Enable Form</button>
    <span id='valid_indicator'></span>
    
    <div id='editor_holder'></div>

    
    <script>
      // This is the starting value for the editor
      // We will use this to seed the initial editor 
      // and to provide a "Restore to Default" button.
      var starting_value = <?php echo "$initial_data"; ?>
      
      // Initialize the editor
      var editor = new JSONEditor(document.getElementById('editor_holder'),{
        // Enable fetching schemas via ajax
        ajax: true,

        // Use a CSS theme
        //theme: 'foundation5',
        theme: 'bootstrap3',
        iconlib: "bootstrap3",
        //iconlib: "fontawesome4",
        
        // The schema for the editor

        schema: {
          type: "array",
          title: "Financial Setups",
          format: "tabs",
          options: {
            disable_array_delete: true
          },
          items: {
            // title: "Financial Setup",
            headerTemplate: "{{self.name}}",
            $ref: "schema/Setup.json"
          }
        },
        
        // Seed the form with a starting value
        startval: starting_value,
        
        // Disable additional properties
        no_additional_properties: true,
        
        // Require all properties by default
        required_by_default: true
      });
      
      // Hook up the submit button
      document.getElementById('submit').addEventListener('click',function() {
        // Get the value from the editor
        // console.log(editor.getValue());
        setups = editor.getValue()
        setups.forEach(function(setup) {
          filename = setup.name;
          content_string = JSON.stringify(setup, null, 1);
          console.dir(JSON.parse(content_string));
          // POST the data to be saved to a file
          var xmlhttp;
          if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
              xmlhttp=new XMLHttpRequest();
          } else {// code for IE6, IE5
              xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
          }
          console.log("opening ajax connection");
          xmlhttp.open("POST", "save_file.php", false);
          xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
          string_to_send = '';
          string_to_send += 'filename='+encodeURIComponent(filename);
          string_to_send += '&content='+encodeURIComponent(content_string);
          // console.log(string_to_send);
          xmlhttp.send(string_to_send);
          var result = JSON.parse(xmlhttp.responseText);
          console.log(result);
          document.getElementById('submit_status').innerHTML = result.message;
        });
      });
      
      // Hook up the Restore to Default button
      document.getElementById('restore').addEventListener('click',function() {
        editor.setValue(starting_value);
      });
      
      // Hook up the enable/disable button
      document.getElementById('enable_disable').addEventListener('click',function() {
        // Enable form
        if(!editor.isEnabled()) {
          editor.enable();
        }
        // Disable form
        else {
          editor.disable();
        }
      });
      
      // Hook up the validation indicator to update its 
      // status whenever the editor changes
      editor.on('change',function() {
        // Get an array of errors from the validator
        var errors = editor.validate();
        
        var indicator = document.getElementById('valid_indicator');
        
        // Not valid
        if(errors.length) {
          indicator.style.color = 'red';
          indicator.textContent = "not valid";
        }
        // Valid
        else {
          indicator.style.color = 'green';
          indicator.textContent = "valid";
        }
      });
    </script>
</body>
</html>
