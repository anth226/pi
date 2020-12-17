<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Exception;

class SalespeopleReport extends Model
{
	protected $fillable = [
		'report_date',
		'salespeople_id',
		'invoice_id',
		'sales_price',
		'percentage',
		'earnings'
	];

	public static function saveToReport(Invoices $invoice){
		try{
			$report_date = $invoice->access_date;
			$salespeople = $invoice->salespeople;
			foreach($salespeople as $sp){
				$salespeople_id = $sp->salespeople_id;
				$data = [
					'sales_price' => $invoice->sales_price,
					'earnings' => self::calcEarning($invoice, $salespeople_id ),
					'salespeople_id' => $salespeople_id,
					'report_date' => $report_date,
					'percentage' => ''
				];
				//return self::create($data);
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleReport',
				'function' => 'saveToReport'
			]);
			return false;
		}
	}

	protected static function calcEarning(Invoices $invoice, $salesperson_id){
		try{
			$sales_price = $invoice->sales_price;
			$percentages = self::getCurrentPercentage($invoice);
			if($invoice->salespeople->count() == 1){ // only one salesperson
				$percentage = $percentages[$salesperson_id];
				$earning = $sales_price/100*$percentage;
				if($earning > $sales_price/2){
					$earning = $sales_price/2;
				}
				return $earning;
			}
			else{// multiple salespeople
				return false;
			}

		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleReport',
				'function' => 'calcEarning'
			]);
			return false;
		}
	}

	protected static function getCurrentPercentage(Invoices $invoice){
		try{
			$report_date = $invoice->access_date;
			$salespeople = $invoice->salespeople;
			$sp_percentages = [];
			foreach($salespeople as $sp){
				$salespeople_id = $sp->salespeople_id;
				$percentage = SalespeoplePecentageLog::where('salespeople_id', $salespeople_id)
				                                     ->where('created_at', '<=', $report_date.' 23:59:59')
				                                     ->orderBy('created_at', 'desc')
				                                     ->value('percentage')
					;
				if(!$percentage) { // first available
					$percentage = SalespeoplePecentageLog::where( 'salespeople_id', $salespeople_id )
					                                                     ->orderBy( 'created_at', 'asc' )
					                                                     ->value( 'percentage' )
					;
				}
				$sp_percentages[$salespeople_id] = $percentage;
			}
			return $sp_percentages;
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleReport',
				'function' => 'getCurrentPercentage'
			]);
			return false;
		}
	}
}
