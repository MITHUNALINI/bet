<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BetSelections extends Model
{
  protected $table = 'bet_selections';
  public $timestamps = true;
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'bet_id', 'selection_id', 'odds',
  ];
}
