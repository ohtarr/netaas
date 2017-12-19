<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nanigans\SingleTableInheritance\SingleTableInheritanceTrait;

class Rule extends Model
{
	use SoftDeletes;
	use SingleTableInheritanceTrait;

	protected $table = "rules";
	protected static $singleTableTypeField = 'type';
	protected static $singleTableSubclasses = [BgpRule::class, DeviceRule::class];
	
	//public $name;
	//public $ruleset_id;
	//public $variable1;
	//public $operator;
	//public $variable2;
	//public $description;
	
	
}
