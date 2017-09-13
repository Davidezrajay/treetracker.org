<?php

use Symfony\Component\CssSelector\Parser\Token;

// These constants may be changed without breaking existing hashes.
define("PBKDF2_HASH_ALGORITHM", "sha256");
define("PBKDF2_ITERATIONS", 1000);
define("PBKDF2_SALT_BYTE_SIZE", 24);
define("PBKDF2_HASH_BYTE_SIZE", 24);

define("HASH_SECTIONS", 4);
define("HASH_ALGORITHM_INDEX", 0);
define("HASH_ITERATION_INDEX", 1);
define("HASH_SALT_INDEX", 2);
define("HASH_PBKDF2_INDEX", 3);



class UserController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{

	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		
			
	}
	
	

	/**
	 * Handle signup here
	 *
	 * @return Response
	 */
	public function signup()
	{
		//signup is handled here
		$input = Input::json()->all();
	
		$user = new User;
		$user->first_name = $input['first_name'];
		$user->last_name = $input['last_name'];
		$user->email = $input['username'];
		$user->password = Hash::make($input['password']);
		$user->organization = $input['organization'];
		$user->phone = $input['phone'];
			
		try {
			$user->save();
		} catch (Exception $e) {
			$error['error'] = $e->getMessage();
			return Response::json($error, 409);
		}
		
		$output = $user->toArray();
		
		DB::unprepared("INSERT INTO tokens(token, expires, user_id) VALUES((SELECT md5(NOW())), (SELECT NOW() + INTERVAL 100 YEAR), $user->id)");
		
		$token = DB::table('tokens')->where('user_id', '=', $user->id)->first();
		
		$output['token'] = $token->token;
		
		$settings = Setting::find(1);
		
		$output['next_update'] = $settings->next_update;
		$output['min_gps_accuracy'] = $settings->min_gps_accuracy;
		
		return Response::json($output, 200);
	}
	
	
	/**
	 * Handle login here
	 *
	 * @return Response
	 */
	public function login()
	{
		//login is handled here
		$input = Input::json()->all();
		
		$user = User::whereRaw('email = ?', array($input['username']))->first();
		
		if ($user == null)
			return Response::json(array("error" => "Invalid credentials"), 401);
		
		
		$validPassword = Hash::check($input['password'], $user->password);
		
		
		if ($user != null && $validPassword) {
			$token =  AuthToken::whereRaw('user_id = ? and DATE_SUB(NOW(),INTERVAL 0 DAY) <= expires', array($user->id))->first();
			
			if ($token == null) {
				//return new token
				
				DB::unprepared("INSERT INTO tokens(token, expires, user_id) VALUES((SELECT md5(NOW())), (SELECT NOW() + INTERVAL 100 YEAR), $user->id)");
				
				$token =  AuthToken::whereRaw('user_id = ? and DATE_SUB(NOW(),INTERVAL 0 DAY) <= expires', array($user->id))->first();
				
			}
			
			$output = $user->toArray();
			
			$output['token'] = $token->token;
			
			$settings = Setting::find(1);
			
			$output['next_update'] = $settings->next_update;
			$output['min_gps_accuracy'] = $settings->min_gps_accuracy;
			
		} else {
			return Response::json(array("error" => "Invalid credentials"), 401);
		}
		
		
		return Response::json($output, 200);
	}
	
	
	/**
	 * Handle password forgot here
	 *
	 * @return Response
	 */
	public function forgot()
	{
		//login is handled here
		$input = Input::json()->all();
	
		
		$user = User::whereRaw('email = ?', array($input['username']))->first();
		
		if ($user == null)
			return Response::json(array("error" => "Incorrect e-mail address."), 401);
		
		
// 		var_dump($user->email, $user->first_name .' '. $user->last_name);die;
		
		// the data that will be passed into the mail view blade template
		$data = array(
				'detail'=>'Your awesome detail here',
				'name'  => 'ime',
				'token' => 'asdasd'
		);		
		
		Mail::send('emails.auth.reminder', $data, function($message)
		{
			$input = Input::json()->all();
			$user = User::whereRaw('email = ?', array($input['username']))->first();
			
			$message->from('admin@treetracker.com', 'TreeTracker');
		    $message->to($user->email, $user->first_name .' '. $user->last_name)->subject('Password reset.');
		});
		
	
		return Response::json("", 200);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}