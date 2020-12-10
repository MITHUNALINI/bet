<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
  protected $table = 'bets';
  public $timestamps = true;
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'stake_amount',
  ];
}
