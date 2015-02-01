<?php

class Finances implements JsonSerializable {
	var $first_day;

	var $loans;
	var $expenses;
	var $income;
	var $accounts;
	var $portfolio;

	var $default_checking;
	var $default_checking_name;

	var $date_start;
	var $date_end;

	var $today = array();
	var $date;

	function __construct() {
		$this->first_day = 0;
		$this->accounts = new Accounts;
		$this->portfolio = new Portfolio;
		$this->loans = new Loans;
		$this->income = new MoneyItems;
		$this->expenses = new MoneyItems;
	}

	function set_start_date($date_start) {
		$this->date_start = $date_start;
	}

	function set_end_date($date_end) {
		$this->date_end = $date_end;
	}

	function set_default_checking($checking) {
		$this->default_checking = $checking;
	}

	function set_default_checking_name($name) {
		$this->default_checking_name = $name;
		$this->default_checking = $this->accounts->list[$name];
	}

	function do_daily_finances($date) {
		$this->date = $date;

		$income = $this->income;
		$expenses = $this->expenses;
		$loans = $this->loans;
		$checking = $this->default_checking;
		$portfolio = $this->portfolio;

		// income calculations
		$today['income'] = $income->get_all($date);

		// expense calculations
		$today['expenses'] = $expenses->get_all($date);

		// loan calculations
		$today['interest'] = $loans->compute_all_interest($date);
		$today['payments'] = $loans->make_all_payments($date);
		$today['loans'] = $loans->get_all_balances();

		// portfolio calculations
		$today['invest'] = $portfolio->compute_all_transfers($date, $checking);
		$today['earn'] = $portfolio->compute_all_interest($date);
		$today['portfolio'] = $portfolio->get_all_balances();

		// checking calculations
		$checking->deposit($today['income']);
		$checking->withdraw($today['expenses']);
		$checking->withdraw($today['payments']);
		$today['checking'] = $checking->get_balance();

		// net worth calculations
		$today['net_worth'] = $this->get_net_worth();

		// save results for display
		$this->today = $today;
        return $today;
	}

	function get_today($print_tsv) {
		global $print_each_loan;
		global $print_each_expense;
		global $print_each_holding;
		global $print_extra_line;
		$today = $this->today;
		$date = $this->date;
	    $columns_totals = array('checking', 'loans', 'portfolio', 'net_worth');
	    $columns_transactions = array('income', 'expenses', 'payments', 'invest', 'earn', 'interest');

        $results = array();
        if ($print_tsv) {
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
			    if ($print_each_holding) {
				    foreach ($this->portfolio->list as $name => $holding) {
					    printf("\t%s", $name);
				    }
			    }
			    printf("\n");
		    }
        }
		if ($print_tsv) printf("%s", $date);
		foreach ($columns_totals as $column) {
			$value = $today[$column];
			if ($print_tsv) printf("\t%.2f", $value);
            $results['totals'][$column] = $value;
		}
		if ($print_tsv) printf("%s", $date);
		foreach ($columns_transactions as $column) {
			$value = $today[$column];
			if ($print_tsv) printf("\t%.2f", $value);
            $results['transactions'][$column] = $value;
		}
		if ($print_each_loan) {
			foreach ($this->loans->list as $name => $loan) {
                $balance = $loan->get_balance();
				if ($print_tsv) printf("\t%.2f", $balance);
                $results['each_loan'][$name] = $balance;
			}
		}
		if ($print_each_expense) {
			foreach ($this->expenses->list as $name => $expense) {
                $amount = $expense->get_amount($date);
				if ($print_tsv) printf("\t%.2f", $amount);
                $results['each_expense'][$name] = $amount;
			}
		}
		if ($print_each_holding) {
			foreach ($this->portfolio->list as $name => $holding) {
                $amount = $holding->get_balance($date);
				if ($print_tsv) printf("\t%.2f", $amount);
                $results['each_holding'][$name] = $amount;
			}
		}
		if ($print_tsv) printf("\n");
		if ($print_extra_line) {
			if ($print_tsv) printf("\n");
		}

        return $results;
	}

	function get_net_worth() {
		$money = 0;
		$money += $this->accounts->get_all_balances();
		$money += $this->portfolio->get_all_balances();
		$money -= $this->loans->get_all_balances();
		return $money;
	}

	function jsonSerialize() {
		// return an array
		$res = array();
		$res['date_start'] = $this->date_start;
		$res['date_end'] = $this->date_end;
		$res['default_checking'] = $this->default_checking_name;
		$res['portfolio'] = $this->portfolio->jsonSerialize();
		$res['accounts'] = $this->accounts->jsonSerialize();
		$res['loans'] = $this->loans->jsonSerialize();
		$res['income'] = $this->income->jsonSerialize();
		$res['expenses'] = $this->expenses->jsonSerialize();
		return $res;
	}
}

class ValueInterval implements JsonSerializable {
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
		global $debug_intervals;
		if ($debug_intervals) { printf("? '%s' < '%s' < '%s' ? ", $this->date_start, $date, $this->date_end); }
		if (($this->date_start == 0 or $date >= $this->date_start)
		  and ($this->date_end == 0 or $date <= $this->date_end)) {
			$res = true;
		} else {
			$res = false;
		}
		if ($debug_intervals) { echo $res ? "yes" : "no"; echo "\n"; }
		return $res;
	}
	function jsonSerialize() {
		$res = array();
		$res['amount'] = $this->amount;
		$res['date_start'] = $this->date_start;
		$res['date_end'] = $this->date_end;
		$res['period'] = $this->period;
		$res['extra'] = $this->extra;
		return $res;
	}
}

class MoneyItems implements JsonSerializable {
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
	function jsonSerialize() {
		$res = array();
		foreach ($this->list as $name => $item) {
			$item_res = array();
			$item_res['name'] = $name;
			$item_res['schedule'] = $item->jsonSerialize();
			$res[] = $item_res;
		}
		return $res;
	}
}

class MoneyItem implements JsonSerializable {
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
		if ($debug) {
			printf("get_amount($date) $val->period, $val->amount, $val->extra -> $money\n");
		}
		return $money;
	}
	function jsonSerialize() {
		$res = array();
		foreach ($this->list as $item) {
			$res[] = $item->jsonSerialize();
		}
		return $res;
	}
}

class Loans implements JsonSerializable {
	var $list = array(); // array of Loan objects
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
		global $debug_interest;
		if ($debug) { echo "compute_all_interest('$date');\n";}
		$money = 0;
		foreach ($this->list as $name => $loan) {
            if ($debug_interest) { echo "Loans compute_all_interest, $date, $name\n"; }
			$money += $loan->compute_interest($date);
		}
		return $money;
	}
	function jsonSerialize() {
		$res = array();
		foreach ($this->list as $name => $item) {
			$res[] = serialize_named_object($name, $item);
		}
		return $res;
	}
}

class Loan extends MoneyItem implements JsonSerializable {
	// a loan has items which represent the payment schedule
	// a loan also has a balance
	var $balance;
	// a loan also has an interest rate, but that can change over time
	var $interest_schedule; // this is a MoneyItem object
	// a loan also has an interest computation method
	var $interest_method;
	var $interest_extra;
	// since we can make payments, record date of last payment
	var $date_last_payment;

	function setup_loan($interest_method, $interest_extra) {
		$this->interest_schedule = new MoneyItem;
		$this->interest_method = $interest_method;
		$this->interest_extra = $interest_extra;
        if ($this->interest_extra == '') {
            $this->interest_extra = 'January 1';
        }
	}

	function set_balance($value) {
		$this->balance = $value;
	}

	function get_balance() {
		return $this->balance;
	}

	function add_interest($amount, $date_start, $date_end, $period, $extra) {
		$this->interest_schedule->add_amount($amount, $date_start, $date_end, $period, $extra);
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
		global $debug_interest;
		if ($debug) { echo "compute_interest('$date');\n"; }
		$interest_method = $this->interest_method;
		$money = 0;
		$interest_rate = $this->interest_schedule->get_amount($date);
		switch ($interest_method) {
		case 'monthly':
			// make sure day matches $this->interest_extra
			$day_this = date('d', strtotime($date));
			$day_interest = date('d', strtotime($this->interest_extra));
			if ($debug_interest) { echo "compute_interest, comparing day_this '$day_this' to day_interest '$day_interest'\n";}
			if ($day_this != $day_interest) {
				break;
			}
			// interest formula
			$money = $this->balance * (0.01 * $interest_rate / 12);
			if ($debug_interest) { echo "compute_interest: $this->balance, $interest_rate -> $money\n";}
			break;
		// TODO: handle other interest methods, such as daily
		default:
			throw new Exception("unknown interest method '$interest_method'");
			break;
		}
		$this->balance += $money;
		return $money;
	}

	function jsonSerialize() {
		global $debug;
		if ($debug) {print_r($this);}
		$res = array();
		$res['balance'] = $this->get_balance();
		$res['interest_method'] = $this->interest_method;
		$res['interest_extra'] = $this->interest_extra;
		$res['interest_schedule'] = $this->interest_schedule->jsonSerialize();
		$res['payment_schedule'] = parent::jsonSerialize();
		return $res;
	}
}

class Accounts implements JsonSerializable {
	// a collection of "checking" accounts 
	var $list = array(); // array of Account objects

	function add_account($name) {
		$this_account = new Account;
		$this->list[$name] = $this_account;
		return $this_account;
	}

	function get_all_balances() {
		// return all balances
		$money = 0;
		foreach ($this->list as $this_account) {
			$money += $this_account->get_balance();
		}
		return $money;
	}

	function jsonSerialize() {
		$res = array();
		foreach ($this->list as $name => $item) {
			$res[] = serialize_named_object($name, $item);
		}
		return $res;
	}
}

class Account implements JsonSerializable {
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
	function jsonSerialize() {
		$res = array();
		$res['balance'] = $this->get_balance();
		return $res;
	}
}

class Portfolio implements JsonSerializable {
	// a collection of Holdings, accounts which can have earnings
	var $list = array(); // array of Holding objects

	function add_holding($name) {
		$this_holding = new Holding();
		$this->list[$name] = $this_holding;
		return $this_holding;
	}

	function get_all_balances() {
		// return all balances
		$money = 0;
		foreach ($this->list as $this_holding) {
			$money += $this_holding->get_balance();
		}
		return $money;
	}

	function compute_all_interest($date) {
		// compute interest on each holding, adding it to the balance
		global $debug;
		global $debug_interest;
		if ($debug) { echo "compute_all_interest('$date');\n";}
		$money = 0;
		foreach ($this->list as $name => $holding) {
            if ($debug_interest) { echo "Portfolio compute_all_interest, $date, $name\n"; }
			$money += $holding->compute_interest($date);
		}
		return $money;
	}

	function compute_all_transfers($date, $default_checking) {
		// compute transfers to each holding, adding it to the balance
		global $debug;
		if ($debug) { echo "compute_all_transfers('$date');\n";}
		$money = 0;
		foreach ($this->list as $this_holding) {
			$money += $this_holding->compute_transfers($date);
		}
		return $money;
	}

	function jsonSerialize() {
		$res = array();
		foreach ($this->list as $name => $item) {
			$res[] = serialize_named_object($name, $item);
		}
		return $res;
	}
}

class Holding extends Account implements JsonSerializable {
	// a holdings account can be set up to have a recurring deposit from a checking account
	// you can specify from what checking account deposits will be made
	// todo if necessary, allow deposits to be scheduled separately from each checking account
	var $checking; // an Account that is the source for this fund
    var $checking_name;
	var $transfer_schedule; // a MoneyItem object referring to the schedule for transfers from checking savings
	// a savings account can also interest. In effect this is because you are GIVING a loan.
	var $earning; // a Loan object

	function __construct() {
		$this->transfer_schedule = new MoneyItem;
		$this->earning = new Loan;
	}

	function set_checking_name($finances, $checking_name) {
		// assign checking account by name.
		foreach ($finances->accounts->list as $name => $checking) {
			if ($name == $checking_name) {
				$this->checking = $checking;
                		$this->checking_name = $checking_name;
				return;
			}
		}
		throw new Exception("checking acnt '$checking_name' not found");
	}

	function get_checking() {
		return $this->checking;
	}

	function add_transfer($amount, $date_start, $date_end, $period, $extra) {
		$this->transfer_schedule->add_amount($amount, $date_start, $date_end, $period, $extra);
	}

	function compute_transfers($date) {
		global $debug;
		global $debug_intervals;
		// take money from checking and put into savings
		if (isset($this->checking)) {
			$checking = $this->checking;
		} else {
			throw new Exception("no checking acnt set");
		}
		if ($debug_intervals) { printf("checking transfer:"); }
		$amount = $this->transfer_schedule->get_amount($date);
		if ($debug) { echo "compute_transfers -> $amount\n";}
		$checking->withdraw($amount);
		$this->deposit($amount);
		return $amount;
	}

	function setup_earning($method, $extra) {
		$this->earning->setup_loan($method, $extra);
	}

	function add_interest($amount, $date_start, $date_end, $period, $extra) {
        // override $extra to make sure it matches the key date for interest for this holding
        $extra = $this->earning->interest_extra;
		$this->earning->add_interest($amount, $date_start, $date_end, $period, $extra);
	}

	function compute_interest($date) {
		// make sure we sync our balance to and from the earnings account
		$money = 0;
		if ($this->earning->interest_schedule) {
			$this->earning->set_balance($this->get_balance());
			$money = $this->earning->compute_interest($date);
			$this->set_balance($this->earning->get_balance());
		}
		return $money;
	}
	function jsonSerialize() {
		$res = array();
		$res['balance'] = $this->get_balance();
		$res['checking_name'] = $this->checking->name;
		$res['transfer_schedule'] = $this->transfer_schedule->jsonSerialize();
		if (isset($this->earning->interest_schedule)) {
			$res['earning'] = $this->earning->jsonSerialize();
		}
		return $res;
	}
}

?>
