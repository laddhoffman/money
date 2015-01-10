<?php

include("functions.php");
include("classes.php");

if (isset($_REQUEST['input_file'])) {
    $json_input_file = $_REQUEST['input_file'];
} elseif (isset($_POST['input_file'])) {
    $json_input_file = $_POST['input_file'];
} else {
    $options = getopt('', array(
        'input:',
    ));
    $json_input_file = $options['input'];
}

//echo "filename = $json_input_file";

$debug = false;
$print_each_loan = true;
$print_each_expense = true;
$print_each_holding = true;
$print_extra_line = false;


if (!$json_input_file) {
    // need to read from json input file
    exit(1);
}

############### read from json #################

$input = json_decode(file_get_contents('setups/'.$json_input_file)); // make an object

$finances = new Finances;

$finances->date_start = $input->date_start;
$finances->date_end = $input->date_end;
    
# read accounts
$accounts = $finances->accounts;
foreach ($input->accounts as $a) {
    $account = $accounts->add_account($a->name);
    $account->set_balance($a->balance);
}

$finances->set_default_checking_name($input->default_checking);

# read portfolio
$portfolio = $finances->portfolio;
foreach ($input->portfolio as $a) {
    $holding = $portfolio->add_account($a->name);
    $holding->set_balance($a->balance);
    if ($a->checking_name) {
        $holding->set_checking_name($finances, $a->checking_name);
    }
    if (isset($a->transfer_schedule)) {
        foreach ($a->transfer_schedule as $t) {
            $holding->add_transfer($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
        }
    }
    // some holdings earn interest
    if (isset($a->earning)) {
        $earning = $a->earning;
        $holding->setup_earning($earning->interest_method, $earning->interest_extra);
        foreach ($earning->apr_schedule as $t) {
            $holding->add_interest($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
        }
    }
}

# read loans
$loans = $finances->loans;
foreach ($input->loans as $a) {
    $loan = $loans->add_loan($a->name);
    $loan->setup_loan($a->interest_method, $a->interest_extra);
    $loan->set_balance($a->balance);
    foreach ($a->apr_schedule as $t) {
        $loan->add_interest($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
    }
    foreach ($a->payment_schedule as $t) {
        $loan->add_amount($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
    }
}

# read incomes
$income = $finances->income;
foreach ($input->income as $a) {
    $item = $income->add_item($a->name);
    foreach ($a->schedule as $t) {
        $item->add_amount($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
    }
}

# read expenses
$expenses = $finances->expenses;
foreach ($input->expenses as $a) {
    $item = $expenses->add_item($a->name);
    foreach ($a->schedule as $t) {
        $item->add_amount($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
    }
}

$date = $finances->date_start;
$n = 0;
$results = array();
while ($date <= $finances->date_end) {
    $finances->do_daily_finances($date);
    $today = $finances->get_today(false);
    // print_r($today);
    $row = array_merge(array('date' => $date), $today);
    array_push($results, $row);
    if (++$n >= 2) {
        break;
    }
    $date = next_day($date);
}

$results_json = json_encode($results);
echo "$results_json";
?>
