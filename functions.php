<?php

function next_day($date) {
	// return the next day
	return date("Y-m-d", strtotime("+1 day", strtotime($date)));
}

function print_item($name, $value) {
	printf("\t%s %.2f", $name, $value);
}

function do_daily_finances($date) {
	global $income;
	global $expenses;
	global $loans;

	// income calculations
	$today['income'] = $income->get_all($date);

	// expense calculations
	$today['expense'] = $expenses->get_all($date);

	// loan calculations
	$today['interest'] = $loans->compute_all_interest($date);
	$today['payments'] = $loans->make_all_payments($date);
	$today['balance'] = $loans->get_all_balances();

	// TODO: savings calculations

	printf("[%s]", $date);
	foreach ($today as $column => $value) {
		print_item($column, $value);
	}
	printf("\n");

//	printf("[$date] income %6.2d\texpense %6.2d\tpayments %6.2d\tbalances %6.2d\tinterest %6.2d\n", $today_income, $today_expense, $today_payments, $today_loan_balance, $today_interest);

}

?>
