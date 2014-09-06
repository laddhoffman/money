<?php

class Finances {
	var $first_day;

	var $loans;
	var $expenses;
	var $income;
	var $checking;
	var $savings;

	var $today = array();
	var $date;

	var $columns = array('checking', 'income', 'expenses', 'payments', 'loans', 'save', 'savings', 'net_worth');

	function __construct() {
		$this->first_day = 0;
		$this->checking = new Account;
		$this->savings = new Savings($this->checking);
		$this->loans = new Loans;
		$this->income = new MoneyItems;
		$this->expenses = new MoneyItems;
	}

	function do_daily_finances($date) {
		$this->date = $date;

		$income = $this->income;
		$expenses = $this->expenses;
		$loans = $this->loans;
		$checking = $this->checking;
		$savings = $this->savings;

		// income calculations
		$today['income'] = $income->get_all($date);

		// expense calculations
		$today['expenses'] = $expenses->get_all($date);

		// loan calculations
		$today['interest'] = $loans->compute_all_interest($date);
		$today['payments'] = $loans->make_all_payments($date);
		$today['loans'] = $loans->get_all_balances();

		// savings calculations
		$today['save'] = $savings->compute_transfers($date);
		$today['earn'] = $savings->compute_interest($date);
		$today['savings'] = $savings->get_balance();

		// checking calculations
		$checking->deposit($today['income']);
		$checking->withdraw($today['expenses']);
		$checking->withdraw($today['payments']);
		$today['checking'] = $checking->get_balance();

		// net worth calculations
		$today['net_worth'] = $this->get_net_worth();

		// save results for display
		$this->today = $today;
	}
	function print_today() {
		global $print_each_loan;
		global $print_each_expense;
		global $print_extra_line;
		$today = $this->today;
		$date = $this->date;
		if ($this->first_day == 0) {
			$this->first_day = 1;
			printf("date");
			foreach ($this->columns as $column) {
				printf("\t%s", $column);
			}
			if ($print_each_loan) {
				foreach ($this->loans->list as $name => $loan) {
					printf("\t%s", $name);
				}
			}
			if ($print_each_expense) {
				foreach ($this->expenses->list as $name => $expense) {
					printf("\t%s", $name);
				}
			}
			printf("\n");
		}
		printf("%s", $date);
		foreach ($this->columns as $column) {
			$value = $today[$column];
			printf("\t%.2f", $value);
		}
		if ($print_each_loan) {
			foreach ($this->loans->list as $name => $loan) {
				printf("\t%.2f", $loan->get_balance());
			}
		}
		if ($print_each_expense) {
			foreach ($this->expenses->list as $name => $expense) {
				printf("\t%.2f", $expense->get_amount($date));
			}
		}
		printf("\n");
		if ($print_extra_line) {
			printf("\n");
		}
	}
	function get_net_worth() {
		$money = 0;
		$money += $this->checking->get_balance();
		$money += $this->savings->get_balance();
		$money -= $this->loans->get_all_balances();
		return $money;
	}
}

class ValueInterval {
	var $amount;
	var $date_start;
	var $date_end;
	var $period;
	var $extra;
	function __construct($amount, $date_start, $date_end, $period, $extra) {
		global $debug;
		$this->amount = $amount;
		$this->date_start = $date_start;
		$this->date_end = $date_end;
		$this->period = $period;
		$this->extra = $extra;
		if ($debug) {print_r($this);}
	}
	function includes($date) {
		global $debug;
		//if ($debug) { printf("? '%s' < '%s' < '%s' ? ", $this->date_start, $date, $this->date_end); }
		if (($this->date_start == 0 or $date >= $this->date_start)
		  and ($this->date_end == 0 or $date <= $this->date_end)) {
			$res = true;
		} else {
			$res = false;
		}
		//if ($debug) { echo $res ? "yes" : "no"; echo "\n"; }
		return $res;
	}
}

class MoneyItems {
	// each MoneyItems object contains a list of MoneyItem objects
	var $list = array(); // array of MoneyItem
	function add_item($name) {
		$this_item = new MoneyItem();
		$this->list[$name] = $this_item;
		return $this_item;
	}
	function get_all($date) {
		// return all values for a particular date
		$money = 0;
		foreach ($this->list as $this_item) {
			$money += $this_item->get_amount($date);
		}
		return $money;
	}
}

class MoneyItem {
	// each MoneyItem has a list of date ranges during which certain values apply
	var $list = array(); // array of ValueInterval
	function add_amount($amount, $date_start, $date_end, $period, $extra) {
		global $debug;
		$item = new ValueInterval($amount, $date_start, $date_end, $period, $extra);
		$this->list[] = $item;
		return $item;
	}
	
	function get_amount($date) {
		// return amount for a particular date
		global $debug;
		$val = null;
		$money = 0;
		//if ($debug) { echo "comparing value range for this object: "; print_r($item); }
		foreach ($this->list as $item) {
			if ($item->includes($date)) {
				$val = $item;
			}
		}
		if (!$val) {
			return $money;
		}
		$period = $val->period;
		switch ($period) {
		case 'weekly':
			// this is only debited once every week.
			// make sure it is a multiple of 1 week offset from $val->extra
			$offset_seconds = strtotime($date) - strtotime($val->extra);
			$offset_days = floor($offset_seconds / 86400);
			if ($offset_days % 7 == 0) {
				$money = $val->amount;
			}
			break;
		case 'biweekly':
			// this is only debited once every 2 weeks.
			// make sure it is a multiple of 2 weeks offset from $val->extra
			$offset_seconds = strtotime($date) - strtotime($val->extra);
			$offset_days = floor($offset_seconds / 86400);
			if ($offset_days % 14 == 0) {
				$money = $val->amount;
			}
			break;
		case 'monthly': 
			// this is debited once per month
			// make sure the day of month matches the day from $val->extra
			$day_amount = date('d', strtotime($val->extra));
			$day_this = date('d', strtotime($date));
			if ($day_this == $day_amount) {
				$money = $val->amount;
			}
			break;
		case 'semiannual': 
			// this is debited once per year
			// make sure the day matches the day from $val->extra
			$day_amount = date('m-d', strtotime($val->extra));
			$day_amount_semi = date('m-d', strtotime("+6 months", strtotime($val->extra)));
			$day_this = date('m-d', strtotime($date));
			if ($day_this == $day_amount || $day_this == $day_amount_semi) {
				$money = $val->amount;
			}
			break;
		case 'annual': 
			// this is debited once per year
			// make sure the day matches the day from $val->extra
			$day_amount = date('m-d', strtotime($val->extra));
			$day_this = date('m-d', strtotime($date));
			if ($day_this == $day_amount) {
				$money = $val->amount;
			}
			break;
		case 'once':
			// make sure date matches exactly with $val->extra
			if ($date == $val->extra) {
				$money = $val->amount;
			}
			break;
		case 'constant':
			// always return the same value
			$money = $val->amount;
			break;
		default:
			throw new Exception("unkown period '$period'");
		}
		return $money;
	}
}

class Loans {
	var $list = array();
	function add_loan($name) {
		$this_loan = new Loan();
		$this->list[$name] = $this_loan;
		return $this_loan;
	}
	function get_all_balances() {
		// return all balances
		$money = 0;
		foreach ($this->list as $this_loan) {
			$money += $this_loan->get_balance();
		}
		return $money;
	}
	function make_all_payments($date) {
		// make all payments for this date
		$money = 0;
		foreach ($this->list as $this_loan) {
			$money += $this_loan->make_payment($date);
		}
		return $money;
	}
	function compute_all_interest($date) {
		// compute interest on each loan, adding it to the balance
		global $debug;
		if ($debug) { echo "compute_all_interest('$date');\n";}
		$money = 0;
		foreach ($this->list as $this_loan) {
			$money += $this_loan->compute_interest($date);
		}
		return $money;
	}
}

class Loan extends MoneyItem {
	// a loan has items which represent the payment schedule
	// a loan also has a balance
	var $balance;
	// a loan also has an interest rate, but that can change over time
	var $apr_schedule; // this is a MoneyItem object
	// a loan also has an interest computation method
	var $interest_method;
	var $interest_extra;
	// since we can make payments, record date of last payment
	var $date_last_payment;
	function setup_loan($interest_method, $interest_extra) {
		$this->interest_method = $interest_method;
		$this->interest_extra = $interest_extra;
		$this->apr_schedule = new MoneyItem;
	}
	function set_balance($value) {
		$this->balance = $value;
	}
	function get_balance() {
		return $this->balance;
	}
	function add_interest($amount, $date_start, $date_end, $period, $extra) {
		$this->apr_schedule->add_amount($amount, $date_start, $date_end, $period, $extra);
	}
	function make_payment($date) {
		$amount = $this->get_amount($date);
		if ($this->balance < $amount) {
			$amount = $this->balance;
		}
		$this->balance -= $amount;
		$this->date_last_payment = $date;
		return $amount;
	}
	function compute_interest($date) {
		global $debug;
		if ($debug) { echo "compute_interest('$date');\n"; }
		$interest_method = $this->interest_method;
		$money = 0;
		$apr = $this->apr_schedule->get_amount($date);
		switch ($interest_method) {
		case 'monthly':
			// make sure day matches $this->interest_extra
			$day_this = date('d', strtotime($date));
			$day_interest = date('d', strtotime($this->interest_extra));
			if ($debug) { echo "monthly interest, comparing day_this '$day_this' to day_interest '$day_interest'\n";}
			if ($day_this != $day_interest) {
				break;
			}
			// interest formula
			$money = $this->balance * (0.01 * $apr / 12);
			break;
		// TODO: handle other interest methods, such as daily
		default:
			throw new Exception("unknown interest method '$interest_method'");
			break;
		}
		$this->balance += $money;
		return $money;
	}
}

class Account {
	// an account has a balance
	var $balance;
	
	function __construct() {
		$this->balance = 0;
	}

	function set_balance($value) {
		$this->balance = $value;
	}

	function get_balance() {
		return $this->balance;
	}
	
	// you can deposit money in an account
	function deposit($amount) {
		$this->balance += $amount;
	}

	// you can withdraw money from the account
	function withdraw($amount) {
		if ($this->balance < $amount) {
			// should we fail or just withdraw less?
			// for now we will fail
			throw new Exception("account overdraw! $this->balance < $amount");
		}
		$this->balance -= $amount;
	}
}

class Savings extends Account {
	// a savings account can be set up to have a recurring deposit from a checking account
	// TODO: if needed, set up a way to implement deposits to savings from more than one checking account
	var $checking; // an Account that is the source for this fund
	var $transfer_schedule; // a MoneyItem object referring to the schedule for transfers from checking savings
	// a savings account can also interest. In effect this is because you are GIVING a loan.
	var $earning; // a Loan object
	function __construct($checking) {
		$this->checking = $checking;
		$this->transfer_schedule = new MoneyItem;
		$this->earning = new Loan;
	}

	function add_transfer($amount, $date_start, $date_end, $period, $extra) {
		$this->transfer_schedule->add_amount($amount, $date_start, $date_end, $period, $extra);
	}

	function compute_transfers($date) {
		// take money from checking and put into savings
		$amount = $this->transfer_schedule->get_amount($date);
		$this->checking->withdraw($amount);
		$this->deposit($amount);
		return $amount;
	}

	function setup_earning($method, $extra) {
		$this->earning->setup_loan($method, $extra);
	}

	function add_interest($amount, $date_start, $date_end, $period, $extra) {
		$this->earning->add_interest($amount, $date_start, $date_end, $period, $extra);
	}

	function compute_interest($date) {
		// make sure we sync our balance to and from the earnings account
		$money = 0;
		if ($this->earning->apr_schedule) {
			$this->earning->set_balance($this->get_balance());
			$money = $this->earning->compute_interest($date);
			$this->set_balance($this->earning->get_balance());
		}
		return $money;
	}
}


?>
