<?php
// read initial data from file
$initial_data_file = "money_data.json";
$initial_data = file_get_contents($initial_data_file);
?>
<html>
<head>
<title>Laddtropia - Finance JSON Editor</title>
<script src='jsoneditor.js'></script>
</head>
<body>
    <button id='submit'>Submit (console.log)</button>
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
        
        // The schema for the editor

        schema: {
    /*
          title: "Financial Setup",
          format: "tabs",
      $ref: "money_schema.json"
    */
          type: "array",
          title: "Financial Setups",
          format: "tabs",
          items: {
            title: "Financial Setup",
            // headerTemplate: "{{$self.name}}",
            $ref: "money_schema.json"
    /*
            oneOf: [
              {
                $ref: "money_schema.json",
                title: "Basic Financial Setup"
              }
            ]

    */
          }
        },
        
        // Seed the form with a starting value
        startval: starting_value,
        
        // Disable additional properties
        no_additional_properties: true,
        
        // Require all properties by default
        required_by_default: true
      });
      
      // Hook up the submit button to log to the console
      document.getElementById('submit').addEventListener('click',function() {
        // Get the value from the editor
        // console.log(editor.getValue());
        content_string = JSON.stringify(editor.getValue());
        console.dir(JSON.parse(content_string));
        // return;
        // POST the data to be saved to a file
        var xmlhttp;
        if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp=new XMLHttpRequest();
        } else {// code for IE6, IE5
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        console.log("opening ajax connection");
        xmlhttp.open("POST", "save_file.php", false);
        //xmlhttp.setRequestHeader("Content-type", "application/json");
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
        console.log("posting data: %s", content_string);
        xmlhttp.send('content='+encodeURIComponent(content_string));
        var result = JSON.parse(xmlhttp.responseText);
        console.log(result);
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
