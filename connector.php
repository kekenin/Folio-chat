<?php
	session_start();

	//check that method is post, form's token equals session's token and display channel's messages
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token']) && isset($_SESSION['token']) && $_POST['token']==$_SESSION['token']){

		$passwd = $_POST['token'];
		$dir = "../channels/";
		$file = $dir.$_SESSION['channel'];

		//check if file exists
		$content = @file_get_contents($file);

		//deny access and kill session if it doesn't
		if($content === FALSE) {
			echo "<p>Channel destroyed</p>";
			session_unset(); 
			session_destroy(); 
			header("Refresh:0;");

		} else {
			echo $content;
		}
		

	//deny access and kill session if mis use of AJAX or token misbehavior
	} else {
		echo "<p>Press ENTER to return</p>";
		session_unset(); 
		session_destroy(); 
	}



?>