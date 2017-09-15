<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
	use SoftDeletes;
	protected $fillable = ['sysid','type','title','description','level','resolved','options'];
}
