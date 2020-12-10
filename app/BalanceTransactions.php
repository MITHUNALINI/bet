<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BalanceTransactions extends Model
{
  protected $table = 'balance_transactions';
  public $timestamps = true;
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'player_id', 'amount', 'amount_before',
  ];
}
