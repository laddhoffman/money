<?php


/* Build an array of each setup described in setups/ */
$save_dir = 'setups';
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
<link rel='stylesheet' href='/font-awesome-4.2.0/css/font-awesome.css'> 

<!-- JSON Editor -->
<script src='jsoneditor.js'></script>

<!-- CanvasJS charting library -->
<script src='canvasjs.min.js'></script>

<style>
ul.line-legend {
    border: 1px black solid;
    float: left;
}
</style>

</head>
<body>
        <button id='result_clear' style='visibility:hidden'>Clear Result</button><br>
        <div id='status'></div>
        <div id='result'></div>
        <button id='save_all'>Save All</button>
        <button id='compute'>Compute</button>
        <button id='restore'>Revert to Saved</button>
        <!-- <button id='enable_disable'>Disable/Enable Form</button> -->
        <span id='valid_indicator'></span>
        
        <div id='editor_status'>Loading...</div>
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
                // theme: 'foundation5',
                theme: 'bootstrap3',
                // iconlib: 'bootstrap3',
                // iconlib: 'fontawesome4',
                
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
                        $ref: "schema/Setup.json?ver=5"
                    }
                },
                
                // Seed the form with a starting value
                startval: starting_value,
                
                // Disable additional properties
                no_additional_properties: true,
                
                // Require all properties by default
                required_by_default: true
            });

            // ====================== functions ========================

            function save_to_file(setup) {
                    filename = setup.name;
        /*
                    if (!filename) {
                        message = "need a name for setup '" + setup.title + "'!";
                        document.getElementById('status').innerHTML += message + '<br>';
                        continue;
                    )
        */
                    content_string = JSON.stringify(setup, null, 1);
                    console.dir(JSON.parse(content_string));
                    // POST the data to be saved to a file
                    var xmlhttp;
                    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                            xmlhttp=new XMLHttpRequest();
                    } else {// code for IE6, IE5
                            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    // console.log("opening ajax connection");
                    xmlhttp.open("POST", "save_file.php", false);
                    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    string_to_send = '';
                    string_to_send += 'filename='+encodeURIComponent(filename);
                    string_to_send += '&content='+encodeURIComponent(content_string);
                    // console.log(string_to_send);
                    xmlhttp.send(string_to_send);
                    var result = JSON.parse(xmlhttp.responseText);
                    // console.log(result);
                    if (result.status != 0) {
                            document.getElementById('status').innerHTML += result.message + '<br>';
                            return false;
                    }
                    return true;
            }

            function save_all() {
                // Get the value from the editor
                // console.log(editor.getValue());
                setups = editor.getValue()
                success = true;
                setups.forEach(function(setup) {
                    if (!save_to_file(setup)) {
                        success = false;
                    }
                });
                if (success) {
                        message = "Saved all input";
                        document.getElementById('status').innerHTML += message + '<br>';
                        return true;
                } else {
                        message = "Error while saving input";
                        document.getElementById('status').innerHTML += message + '<br>';
                        return false;
                }
            }

        function draw_chart(chart_name, data) {
                // Get the context of the canvas element we want to select
                var ctx = document.getElementById(chart_name).getContext("2d");

                options = {
                        scaleShowGridLines : true,
                         scaleGridLineColor : "rgba(0,0,0,.05)",
                         scaleGridLineWidth : 1,
                        scaleShowHorizontalLines: true,
                        scaleShowVerticalLines: false,
                        bezierCurve : false,
                         bezierCurveTension : 0.2,
                        pointDot : false,
                         pointDotRadius : 4,
                         pointDotStrokeWidth : 1,
                         pointHitDetectionRadius : 20,
                        datasetStroke : true,
                         datasetStrokeWidth : 2,
                        datasetFill : false,
                        // legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].strokeColor%>\">%nbsp;</span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>"
                        legendTemplate : "<div><ul class=\"line-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"color:<%=datasets[i].strokeColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span></li><%}%></ul></div>"
                };

                // Line Chart
                var myLineChart = new Chart(ctx).Line(data, options);
                var legend_html = myLineChart.generateLegend();
                return legend_html;
        }

        function draw_chart_canvasjs(chart_name, data) {
            var chart = new CanvasJS.Chart(chart_name, data);
            chart.render();
        }

            function compute(name) {
                    // document.getElementById('status').innerHTML += "compute("+name+")<br>";

                    // now call the script to perform the calculations
                    var xmlhttp;
                    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
                        xmlhttp=new XMLHttpRequest();
                    } else {// code for IE6, IE5
                        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
                    }

                    // console.log("opening ajax connection");
                    xmlhttp.open("POST", "compute.php", false);
                    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                    var string_to_send = '';
                    string_to_send += 'input_file='+encodeURIComponent(name);
                    // console.log(string_to_send);
                    // document.getElementById('status').innerHTML += "compute("+name+")<br>";
                    // console.log("posting data");
                    xmlhttp.send(string_to_send);
                    // console.log("posted data");

                    var result = JSON.parse(xmlhttp.responseText);
                    // console.log('response: ' + xmlhttp.responseText);

                    result_append('<div id="chart_canvasjs" style="height: 400px; width: 100%;">');
                    // result_append('<canvas id="chart" width="1000" height="400"></canvas>');

                    document.getElementById('result_clear').style.visibility = 'visible';

                    // Process the returned data
                    // Data is returned as an array of rows; each row is an object representing a day.
                    // console.log(result.data[0]);
                    var data = {
                        labels:[],
                        datasets:[
                            { 
                                label: "Checking", 
                                fillColor: "rgba(0, 255, 0, 0.2)",
                                strokeColor: "rgba(0, 255, 0, 1)",
                                pointColor: "rgba(0, 255, 0, 1)",
                                data: [] 
                            },
                            { 
                                label: "Loans", 
                                fillColor: "rgba(0, 128, 255, 0.2)",
                                strokeColor: "rgba(0, 128, 255, 1)",
                                pointColor: "rgba(0, 128, 255, 1)",
                                data: [] 
                            },
                            { 
                                label: "Portfolio", 
                                fillColor: "rgba(128, 128, 0, 0.2)",
                                strokeColor: "rgba(128, 128, 0, 1)",
                                pointColor: "rgba(128, 128, 0, 1)",
                                data: [] 
                            },
                            { 
                                label: "Net", 
                                fillColor: "rgba(128, 128, 128, 0.2)",
                                strokeColor: "rgba(128, 128, 128, 1)",
                                pointColor: "rgba(128, 128, 128, 1)",
                                data: [] 
                            }
                        ]
                    };
                    var n = 0;
                    result.data.forEach(function (row) {
                        // Add Totals to a dataset for graphing
                        if (row.date.split('-')[2] == 1) {
                                var year_month = row.date.split('-')[0] + '-' + row.date.split('-')[1];
                                data.labels[n++] = year_month;
                        } else {
                                data.labels[n++] = '';
                        }
                        data.datasets[0].data.push(row.totals.checking.toFixed(2));
                        data.datasets[1].data.push(row.totals.loans.toFixed(2));
                        data.datasets[2].data.push(row.totals.portfolio.toFixed(2));
                        data.datasets[3].data.push(row.totals.net_worth.toFixed(2));
                    });

                    /*
                    var legend = draw_chart('chart', data);
                    
                    // result_append(legend);
                    document.getElementById('status').innerHTML = legend;
                    */

                    // try the CanvasJS chart

                    data_canvasjs = {
                        axisY:{
                            gridThickness: 1,
                        },
                        toolTip:{
                            shared: true,
                        },
                        legend:{
                            fontSize: 20,
                            fontFamily: "tamoha",
                            // fontColor: "Sienna"      
                        },
                        data: [
                            {        
                                type: "line",
                                name: "Checking",
                                color: "green",
                                showInLegend: true,
                                dataPoints: []
                            },
                            {        
                                type: "line",
                                name: "Loans",
                                color: "yellow",
                                showInLegend: true,
                                dataPoints: []
                            },
                            {        
                                type: "line",
                                name: "Portfolio",
                                color: "blue",
                                showInLegend: true,
                                dataPoints: []
                            },
                            {        
                                type: "line",
                                name: "Net",
                                color: "red",
                                showInLegend: true,
                                dataPoints: []
                            }
                        ]
                    };
                    var n = 0;
                    result.data.forEach(function (row) {
                        // Add Totals to a dataset for graphing
                        today = row.date.split('-');
                        data_canvasjs.data[0].dataPoints.push({x: new Date(today[0], today[1], today[2]), y: Number(row.totals.checking.toFixed(2))});
                        data_canvasjs.data[1].dataPoints.push({x: new Date(today[0], today[1], today[2]), y: Number(row.totals.loans.toFixed(2))});
                        data_canvasjs.data[2].dataPoints.push({x: new Date(today[0], today[1], today[2]), y: Number(row.totals.portfolio.toFixed(2))});
                        data_canvasjs.data[3].dataPoints.push({x: new Date(today[0], today[1], today[2]), y: Number(row.totals.net_worth.toFixed(2))});
                    });
                    console.log(data_canvasjs);
                    draw_chart_canvasjs('chart_canvasjs', data_canvasjs);
            }

            function result_clear() {
                    document.getElementById('status').innerHTML = '';
                    document.getElementById('result').innerHTML = '';
                    document.getElementById('result_clear').style.visibility = 'hidden';
            }

            function result_append(text) {
                    document.getElementById('result').innerHTML += text;
            }

            // ====================== buttons ========================

            // Hook up the editor status indicator
            editor.on('ready',function() {
                document.getElementById('editor_status').innerHTML = '';
            });
            

            // Hook up the save button
            document.getElementById('save_all').addEventListener('click',function() {
                save_all();
            });

            // Hook up the Compute button
            document.getElementById('compute').addEventListener('click',function() {
                // save first
                if (!save_all()) {
                    // Error saving
                    return false;
                }

                result_clear();

                // get actively selected setup
                var name = editor.editors.root.active_tab.innerText;

                // compute
                compute(name);
           });

            
            // Button to clear results view
            document.getElementById('result_clear').addEventListener('click',function() {
                result_clear();
            });

            // Hook up the Restore to Default button
            document.getElementById('restore').addEventListener('click',function() {
                editor.setValue(starting_value);
            });
            
            // Hook up the validation indicator to update its status whenever the editor changes
            editor.on('change',function() {
                // Get an array of errors from the validator
                var errors = editor.validate();
                
                var indicator = document.getElementById('valid_indicator');
                
                // Not valid
                if(errors.length) {
                    indicator.style.color = 'red';
                    indicator.textContent = "invalid input";
                }
                // Valid
                else {
                    indicator.style.color = 'green';
                    indicator.textContent = "valid input";
                }
            });
        </script>
</body>
</html>
