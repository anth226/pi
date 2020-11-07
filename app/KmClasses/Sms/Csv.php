<?php
/**
 * Created by PhpStorm.
 * User: K
 * Date: 3/18/2019
 * Time: 11:06 AM
 */

namespace App\KmClasses\Sms;


class csv {
	public static function csv_to_array($filename='', $delimiter=',')
	{
		if(!file_exists($filename) || !is_readable($filename))
			return FALSE;

		$header = null;
		$data = array();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header) {
					$header = $row;
					//remove special simbols from header
					foreach($header as $k=>$h){
						$header[$k] = preg_replace("/[^\w\d]/","",strtolower($h));
					}
				}else {
					$data[] = array_combine( $header, $row );
				}
			}
			fclose($handle);
		}
		return $data;
	}


	public static function csv_row_total($filename='', $delimiter=',')
	{
		if(!file_exists($filename) || !is_readable($filename))
			return FALSE;

		$header = null;
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			$i = 0;
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				$i ++;
			}
			fclose($handle);
		}
		return $i;
	}

}