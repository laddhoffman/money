<?php

include("functions.php");
include("classes.php");

$options = getopt('', array(
	'input:',
	'user:',
));

if (isset($_REQUEST['input_file'])) {
    $json_input_file = $_REQUEST['input_file'];
} elseif (isset($_POST['input_file'])) {
    $json_input_file = $_POST['input_file'];
} else {
    $json_input_file = $options['input'];
}

if (isset($_SERVER['REMOTE_USER'])) {
	$user = $_SERVER['REMOTE_USER'];
} else {
    $user = $options['user'];
}

//echo "filename = $json_input_file";

$debug = false;
$debug_intervals = false;
$debug_interest = false;

$print_each_loan = true;
$print_each_expense = true;
$print_each_holding = true;
$print_extra_line = false;

class Result {
    var $status;
    var $message;
    var $data = array();
}

$result = new Result();

if (!$json_input_file) {
    // need to read from json input file
    $result->status = -1;
    $result->message = "no input file specified";
    echo json_encode($results);
    exit(1);
}

if (!$user) {
    $result->status = -1;
    $result->message = "no user specified";
    echo json_encode($results);
    exit(1);
}

############### read from json #################

$json_input_path = "setups/$user/$json_input_file";

$input = json_decode(file_get_contents($json_input_path)); // make an object

$finances = new Finances;

$finances->date_start = $input->date_start;
$finances->date_end = $input->date_end;
    
# read accounts
$accounts = $finances->accounts;
foreach ($input->accounts as $a) {
    $account = $accounts->add_account($a->name);
    $account->set_balance($a->balance);
}

$default_checking_name = $input->default_checking;
$finances->set_default_checking_name($default_checking_name);

# read portfolio
$portfolio = $finances->portfolio;
foreach ($input->portfolio as $a) {
    $holding = $portfolio->add_holding($a->name);
    $holding->set_balance($a->balance);
    if ($a->checking_name) {
        $holding->set_checking_name($finances, $a->checking_name);
    } else {
        $holding->set_checking_name($finances, $default_checking_name);
	}
    if (isset($a->payment_schedule)) {
        foreach ($a->payment_schedule as $t) {
            $holding->add_transfer($t->amount, $t->date_start, $t->date_end, $t->period, $t->extra);
        }
    }
    // some holdings earn interest
    if (isset($a->interest_schedule)) {
        $holding->setup_earning($a->interest_method, $a->interest_extra);
        foreach ($a->interest_schedule as $t) {
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
    foreach ($a->interest_schedule as $t) {
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

while ($date <= $finances->date_end) {
    $row = array();
    try {
        $finances->do_daily_finances($date);
    } catch (Exception $e) {
        $result->status = -1;
        $result->message = $e->getMessage();
        break;
    }
    $today = $finances->get_today(false);

    $row['date'] = $date;
    $row = array_merge($row, $today);

    array_push($result->data, $row);

    $date = next_day($date);
}

echo json_encode($result, JSON_PRETTY_PRINT);

?>
