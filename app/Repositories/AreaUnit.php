<?php


namespace App\Repositories;


class AreaUnit {



	public static function convertToSqft($unit,$input_area)
	{
		$converted_area = 0;

		switch ($unit) {
			case 'Sq Ft':
				$converted_area = $input_area;
				break;
			case 'Sq Mt':
				$converted_area = 10.7639 * $input_area;
				break;
			case 'Acres':
				$converted_area = 43560 * $input_area;
				break;
			case 'Sq Yards':
				$converted_area = 9 * $input_area;
				break;											
			default:
				$converted_area = -1;
				break;
		}

		return floor($converted_area);
	}


	public static function unitFormula($unit)
	{
		$unit_value = 0;

		switch ($unit) {
			case 'Sq Ft':
				$unit_value = 1;
				break;
			case 'Sq Mt':
				$unit_value = 10.7639 ;
				break;
			case 'Acres':
				$unit_value = 43560 ;
				break;
			case 'Sq Yards':
				$unit_value = 9 ;
				break;											
			default:
				$unit_value = -1;
				break;
		}

		return $unit_value;
	}


}


?>