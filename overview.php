<?php
date_default_timezone_set("America/Chicago");
include("functions.php");
include("classes.php");
//$debug = 1;
$print_each_loan = 1;

#################### setup ####################

$start_date = '2014-09-05'; // IMPORTANT: if you change this, you must input current balances.
$end_date = '2018-01-05'; 

$finances = new Finances;

#################### checking ####################

$checking = $finances->checking;

$checking->set_balance('2799.60');

#################### savings ####################

$savings = $finances->savings;

$savings->set_balance('8106.76');
$savings->add_transfer('200', 0, 0, 'weekly', '2014-10-03');

# savings can have interest
#$savings->setup_earning('monthly', '2014-01-01');
#$savings->add_interest(8.0, 0, 0, 'constant', null);

#################### loans ####################

$loans = $finances->loans;

# car payment
$civic = $loans->add_loan('civic');
$civic->setup_loan('monthly', '2014-01-01');
$civic->set_balance('12102.98');
$civic->add_interest(0.899999, 0, 0, 'constant', null);
$civic->add_amount('408.15', '0', '0', 'monthly', '2014-01-04');

# student loans
$nelnet1 = $loans->add_loan('nelnet 999-B-1');
$nelnet1->setup_loan('monthly', '2014-01-01');
$nelnet1->set_balance('8300.00');
$nelnet1->add_interest(6.8, 0, 0, 'constant', null);
$nelnet1->add_amount(95.57, 0, 0, 'monthly', '2014-01-10');

$nelnet2 = $loans->add_loan('nelnet 999-A-0');
$nelnet2->setup_loan('monthly', '2014-01-01');
$nelnet2->set_balance('5171.64');
$nelnet2->add_interest(5.6, 0, 0, 'constant', null);
$nelnet2->add_amount(11.56, 0, 0, 'monthly', '2014-01-10');

$nelnet3 = $loans->add_loan('nelnet 181-A-0');
$nelnet3->setup_loan('monthly', '2014-01-01');
$nelnet3->set_balance('3134.28');
$nelnet3->add_interest(5.35, 0, 0, 'constant', null);
$nelnet3->add_amount(40.76, 0, 0, 'monthly', '2014-01-12');

$sallie1 = $loans->add_loan('sallie mae 1');
$sallie1->setup_loan('monthly', '2014-01-01');
$sallie1->set_balance('5011.12');
$sallie1->add_interest(3.0, 0, 0, 'constant', null);
$sallie1->add_amount(100, 0, 0, 'monthly', '2014-01-10');

$sallie2 = $loans->add_loan('sallie mae 2');
$sallie2->setup_loan('monthly', '2014-01-01');
$sallie2->set_balance('11058.10');
$sallie2->add_interest(2.875, 0, 0, 'constant', null);
$sallie2->add_amount(200, 0, 0, 'monthly', '2014-01-10');

# credit cards
$freedom = $loans->add_loan('freedom');
$freedom->setup_loan('monthly', '2014-01-01');
$freedom->set_balance('1624.20');
$freedom->add_interest(12.99, 0, 0, 'constant', null);
$freedom->add_amount('2000', 0, 0, 'monthly', '2014-01-20');

$citi = $loans->add_loan('citi');
$citi->setup_loan('monthly', '2014-01-01');
$citi->set_balance('1808.44');
# no interest
$citi->add_amount('500', 0, 0, 'monthly', '2014-01-20');

$amex = $loans->add_loan('amex');
$amex->setup_loan('monthly', '2014-01-01');
$amex->set_balance('72.74');
$amex->add_interest(17.24, 0, 0, 'constant', null);
$amex->add_amount('500', 0, 0, 'monthly', '2014-01-20');

# mattress
$mattress = $loans->add_loan('mattress');
$mattress->setup_loan('monthly', '2014-01-01');
$mattress->set_balance('2603.92');
# no interest
$mattress->add_amount('56.69', 0, 0, 'monthly', '2014-01-13');

# nordstrom
$nordstrom = $loans->add_loan('nordstrom');
$nordstrom->setup_loan('monthly', '2014-01-01');
$nordstrom->set_balance(389.19);
# no interest
$nordstrom->add_amount(100, 0, 0, 'monthly', '2014-01-10');

#################### income ####################

$income = $finances->income;

# ladd
$ladd = $income->add_item('Ladd');
$ladd->add_amount('2500', 0, 0, 'biweekly', '2014-09-05');

# jaymie
$jaymie = $income->add_item('Jaymie');
$jaymie->add_amount('2100', 0, 0, 'biweekly', '2014-09-12');

#################### expenses ####################

$expenses = $finances->expenses;

# rent
$rent = $expenses->add_item('rent');
$rent->add_amount('975', '2013-11-01', '2014-11-01', 'monthly', '2013-11-01');
$rent->add_amount('1000', '2014-11-01', '2015-11-01', 'monthly', '2013-11-01');

# food

# pets

# medical

# etc

################################################

$date = $start_date;
while ($date <= $end_date) {
	$finances->do_daily_finances($date);
	$finances->print_today();
	$date = next_day($date);
}

?>
