<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Password Reset</h2>


			@if($errors->any())
				@if($errors->first() == 'reminders.password')
				<h4>Passwords must be equal and longer than 6 characters.</h4>
				@endif
				
				@if($errors->first() == 'reminders.user')
				<h4>No such user.</h4>
				@endif
				
				@if($errors->first() == 'reminders.token')
				<h4>Invalid token.</h4>
				@endif
			@endif

		<div>
			<form action="{{ action('RemindersController@postReset') }}" method="POST">
			    <input type="hidden" name="token" value="{{ $token }}">
			    <label for="email">e-mail</label>
			    <input type="email" name="email">
			    <label for="password">New password</label>
			    <input type="password" name="password">
			    <label for="password_confirmation">Confirm new password</label>
			    <input type="password" name="password_confirmation">
			    <input type="submit" value="Reset Password">
			</form>
		</div>
	</body>
</html>