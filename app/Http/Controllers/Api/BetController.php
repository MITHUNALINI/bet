<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use App\Player;
use App\Bet;
use App\BetSelections;
use App\BalanceTransactions;

class BetController extends Controller
{
    public function createBet(Request $request) {

      // $request = $request->getContent();
      $request = $request->all(); //get all request from body

      //rules for validations
      $rules = [
        'player_id' => 'required|integer',
        'stake_amount' => 'required|numeric|min:0.3|max:10000|regex:/^\d+(\.\d{1,2})?$/',
        'selections' => 'required|array|min:1|max:20',
        'selections.*.id' => 'required|integer|distinct',
        'selections.*.odds' => 'required|numeric|min:1|max:10000|regex:/^\d+(\.\d{1,3})?$/',
      ];

      //custom validation messages
      $messages = [
        'required' => '1-Betslip structure mismatch',
        'integer' => '1-Betslip structure mismatch',
        'numeric' => '1-Betslip structure mismatch',
        'regex' => '1-Betslip structure mismatch',
        'array' => '1-Betslip structure mismatch',
        'stake_amount.min' => '2-Minimum stake amount is: 0.3',
        'stake_amount.max' => '3-Maximum stake amount is: 10000',
        'selections.min' => '4-Minimum number of selection is: 1',
        'selections.max' => '5-Maximum number of selection is: 20',
        'selections.*.odds.min' => '6-Minimum odds are 1',
        'selections.*.odds.max' => '7-Maximum odds are 10000',
        'selections.*.id.distinct' => '8-Duplicate selection found'
      ];

      //check validations
      $validator = Validator::make($request, $rules, $messages);

      //if pass the validation
      if ($validator->passes()) {

        $multi_odds = 1;
        $playerId = $request['player_id'];
        // check all selection odds and ids and calculate the odds
        foreach ($request['selections'] as $inner_array){
          $multi_odds*= $inner_array['odds'];
        }

        //calculate max amount
        $max_amount = $request['stake_amount'] * $multi_odds;

        // validation for max amount
        if ($max_amount > 20000)
          return $this->Errorhandling(9, "Maximum win amount is 20000");

        // if no validation error

        //player table update
        $player = Player::where('player_id', '=', $playerId)->first();
        if ($player === null) {
           $player = new Player();
           $player->player_id = $playerId;
           $player->save();
        }

        $playerupdate = Player::find($player->id);
        if ($playerupdate->balance < $request['stake_amount'])
          return $this->Errorhandling(11, "Insufficient balance");

        $playerupdate->balance -= $request['stake_amount'];
        $playerupdate->save();



        //bet table update
        $bet = new Bet();
        $bet->player_id = $playerId;
        $bet->stake_amount = $request['stake_amount'];
        $bet->save();
        $betId = $bet->id;
        //bet selections table update
        foreach ($request['selections'] as $inner_array){
          $selectionId = $inner_array['id'];
          $odds = $inner_array['odds'];

          $bs = new BetSelections();
          $bs->bet_id = $betId;
          $bs->selection_id = $selectionId;
          $bs->odds = $odds;
          $bs->save();
        }

        //balance transaction table update
        $last_bt = BalanceTransactions::where('player_id', '=', $playerId)->latest('created_at')->first();
        $lastamout = 0;
        if ($last_bt !== null){
          $lastamout = $last_bt->amount;
        }

        $bt = new BalanceTransactions();
        $bt->player_id = $playerId;
        $bt->amount = $max_amount + $lastamout;
        $bt->amount_before = $lastamout;
        $bt->save();

        return response()->json([]);

      //if fail the validations
      } else {

        // $errors = $validator->failed();
        $messge = $validator->errors()->first();
        $split_message = explode("-", $messge);
        return $this->Errorhandling($split_message[0], $split_message[1]);

      }
    }

    // error handling methods
    function Errorhandling($code=0, $messge = 'Unknown error'){

      $error_code = $error_messge = $selection_code = $selection_messge = '';
      // global error
      if (in_array($code, array(0,1,2,3,4,5,9,10,11,12))){
        $error_code = $code;
        $error_messge = $messge;
      }
      else { // selection error
        $selection_code = $code;
        $selection_messge = $messge;
      }

      return response()->json([
        "errors" => [[
          "code" => $error_code,
          "message" => $error_messge
        ]],
        "selections" => [[
          "code" => $selection_code,
          "message" => $selection_messge
        ]]
      ], 400);
    }
}
