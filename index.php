<?php
	//session start before anything else
	session_start();

	//function to generate random token
	function generateToken() {
		global $token;
	    $token = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION['token']= $token;
	}

?>

<!doctype html>

<html lang="en">
<head>
  	<meta charset="utf-8">
 	<meta name='viewport' content='width=device-width, initial-scale=1.0'> 

 	<title>Folio-chat</title>
 	<meta name="description" content="Folio-chat 2.0 for safe online messaging.">
 	<meta name="author" content="Keke Rasilainen">

  	<link rel="stylesheet" href="styles.css">

</head>

<body>

	<div class="instructions">
		<p>/clear = clears the messages</p>
		<p>/quit or /exit = leaves the channel</p>
		<p>/kill = destroys the channel</p>
	</div>

	<div class="topic"> 
		<h1>Folio-chat 2.0</h1>
	</div>

	<div id="outputDIV" class="outputDIV">


	<?php 



	//check user input if user want's to connect
	if(!isset($_SESSION['connected'])){
		if(isset($_POST['join'])&&$_SESSION['token']==$_POST['formToken']){
			$input = $_POST['userInput'];
			$input = strtolower($input);			
			if($input=="y"){
				$_SESSION['connected']=TRUE;
			} else if ($input=="n") {
				session_unset(); 
				session_destroy(); 
				header("Refresh:0");
			} else {
				unset($_POST['join']);
			}
		}

	}

	//first time user inputs
	if(!isset($_POST['join'])&&!isset($_SESSION['connected'])) {
												
		//check if posted token matches session's token and store hash from channel user input to session variable
		
		if(isset($_POST['channel'])&&$_SESSION['token']==$_POST['formToken']){
			//validate user input for channel name (letters and numbers only)
			$channel = $_POST['userInput'];
			$channel = preg_replace("/[^a-zA-Z0-9\s]/", "", $channel);
			$channel = trim($channel);
			$channel = stripslashes($channel);
			$channel = htmlspecialchars($channel);
			if(strlen($channel)>0&&strlen($channel<50)){
				$_SESSION['channelPlain']=$channel;
				$_SESSION['channel'] = sha1($channel);
			}

		}

		//check if posted token matches session's token and store user alias to session variable
		if(isset($_POST['alias'])&&$_SESSION['token']==$_POST['formToken']){
			//validate user input for user alias (no special characters and whitespace)
			$uname = $_POST['userInput'];
			$uname = trim($uname);
			$uname = stripslashes($uname);
			$uname = htmlspecialchars($uname);
			if(strlen($uname)>0&&strlen($uname)<50){
				$_SESSION['alias'] = $uname;
			}
		}

		//new random token each time user input form is displayed
		generateToken();

		//1. user input for channel
		if(!isset($_SESSION['channel'])) {
			echo '<p>Enter channel name:</p>';
			echo '</div>';
			echo "<div class='inputDIV' id='inputDIV'>";
			echo "	
			<form method='post' autocomplete='off'> 
				<label onclick='document.getElementById('input').click();' class='inputLabel' for='input'></label>
				<input class='inputText' type='text' maxlength='50' id='input' name='userInput'>
				<input type='hidden' value='$token' name='formToken'>
				<input type='hidden' id='sendBTN' class='sendBTN' type='submit' name='channel' value=''>
			</form>
			" ;
			echo "</div>";

		//2. user input for alias
		} else if(!isset($_SESSION['alias'])) {
			echo "<p class='inputAnswer'>Enter alias: </p>";
			echo '</div>';
			echo "<div class='inputDIV' id='inputDIV'>";
			echo "	
			<form method='post' autocomplete='off'> 
				<label class='inputLabel' for='input'></label>
				<input class='inputText' maxlength='500' type='text' id='input' name='userInput'>
				<input type='hidden' value='$token' name='formToken'>
				<input type='hidden'class='sendBTN' type='submit' name='alias' value=''> 
			</form>
			";		
			echo "</div>";
		//3. user input to confirm settings			
		} else if (!isset($_SESSION['join'])) {

			echo "<p class='output'>Join channel ".$_SESSION['channelPlain']." using alias ".$_SESSION["alias"]."? [Y/N]</p>";
			echo '</div>';
			echo "<div class='inputDIV' id='inputDIV'>";
			echo "	
			<form method='post' autocomplete='off'> 
				<label class='inputLabel' for='input'></label>
				<input class='inputText' maxlength='500' type='text' id='input' name='userInput'>
				<input type='hidden' value='$token' name='formToken'>
				<input type='hidden' class='sendBTN' type='submit' name='join' value=''> 
			</form>
			";	
			echo "</div>";

		}


		
	} else {

		


		//check if posted token matches session's token and handle the join message
		if(!isset($_SESSION['firstMSG'])&&!isset($_SESSION['input'])&&isset($_SESSION['token'])&&isset($_POST['formToken'])&&$_SESSION['token']==$_POST['formToken']){
			$time = date('H:i:s', time());
			$_SESSION['firstMSG']=TRUE;

			//create channels folder outside web root
			$dir = "../channels/";		
			if (!file_exists($dir)) {
   				mkdir($dir, 0777, true);
			}

			//create file named using the hashed value of the channel name to channels folder
			$file = $dir.$_SESSION['channel'];

			//write join message to file
			if(!is_file($file)){		   
				$msg = "<p class='keep output channelName'>".$_SESSION['channelPlain']."</p>"; 				//add 'keep' class to join message to save it when clearing messages
				$msg .="<p class='keep output'>"."[". $time. '] '.$_SESSION['alias'].' joined '.$_SESSION['channelPlain'] ."</p>";
			    file_put_contents($file, $msg);
			} else {
				//add 'keep' class to join message to save it when clearing messages
				$msg ="<p class='keep output'>"."[". $time. '] '.$_SESSION['alias'].' joined '.$_SESSION['channelPlain'] ."</p>";
				file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
			}

			//display content's of the file
			$content = file_get_contents($file);
			echo $content;
			
		//check if posted token matches session's token and handle other than the first message sent
		} else if(isset($_POST['input'])&&isset($_SESSION['token'])&&isset($_POST['formToken'])&&$_SESSION['token']==$_POST['formToken']){

			
			
			//check user input for commands
			if(isset($_POST['userInput'])&&strlen($_POST['userInput'])>0&&strlen($_POST['userInput'])<500){


				$dir = "../channels/";
				$file = $dir.$_SESSION['channel'];


				//validate user input
				$userInput = $_POST['userInput'];
				$userInput = trim($userInput);
				$userInput = stripslashes($userInput);
				$userInput = htmlspecialchars($userInput);
				$command = strtolower($userInput);

				//actions on exit || quit
				if($command=="/exit"||$command=="/quit"){

					$time = date('H:i:s', time());
					//add 'keep' class to exit message to save it when clearing messages
					$msg = "<p class='keep output '>".'['.$time. '] '.$_SESSION['alias'] ." left the channel.</p>";
					if(!is_file($file)){		    
			   			file_put_contents($file, $msg);
					} else {
						file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
					}

					session_unset(); 
					session_destroy(); 
					header("Refresh:0");
				//actions on clear
				} else if ($command=="/clear") {

					//get all <p> elements from file to array
					$msg = file_get_contents($file);					
					$dom = new DOMDocument();
					$paragraphs = array();
					$dom->loadHTML($msg);
					foreach($dom->getElementsByTagName('p') as $node)
					{	
					    $paragraphs[] = $dom->saveHTML($node);
					}

					$msg=null;

					//check if any <p> element contains 'keep' class to prevent clearing that message (join&exit messages and channel's name)
					for ($i=0;$i<count($paragraphs);$i++)  
					{	
						//the word 'keep' has to be in the class to prevend deletion, not in the message it self
					    if(strpos($paragraphs[$i], "keep")&&strpos($paragraphs[$i], "keep")==10){
					    	$msg.=$paragraphs[$i];
					    } //write value by index
					}
					


					file_put_contents($file, $msg);
				//actions on destroy
				} else if ($command=="/kill") {

					$msg = "";
					file_put_contents($file, $msg);
					echo "<p>Destroying...</p>";
					unlink($file);

				//if no action, append to channel file
				} else {
					$time = date('H:i:s', time());
					$msg = "<p class='output'>".'['.$time. '] '."&lt;".$_SESSION['alias']."> " .$userInput."</p>";
					if(!is_file($file)){		    
			   			file_put_contents($file, $msg);
					} else {
						file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
					}
				}




			}

			//display channel file
			$content = @file_get_contents($file);
			echo $content;

		}

		//random token always before user input...
		generateToken();


		//prints user input
		echo '</div>';
		echo "<div class='inputDIV' id='inputDIV'>";
	
		echo "

		<form method='post' autocomplete='off' class='input'> 
			<label class='inputLabel' for='input'></label>
			<input class='inputText' maxlength='500' type='text' id='input' name='userInput'>
			<input type='hidden' value='$token' name='formToken'>
			<input type='hidden' class='sendBTN' type='submit' name='input' value=''> 
		</form>
		";
		echo '</div>';


		//php to print javascript function to load messages with AJAX every 1,5s
		echo "
			<script type='text/javascript'>
			function requestMSGs() {


			    var token ='$token';
			    var creds = 'token='+token;
			    var ajx = new XMLHttpRequest();
			    ajx.onreadystatechange = function () {
			        if (ajx.readyState == 4 && ajx.status == 200) {
			            	var oldOutput = document.getElementById('outputDIV');
						    var newOutput = this.responseText;
							oldOutput.innerHTML=newOutput;
			        }
			    };
			    ajx.open('POST', 'connector.php', true);
			    ajx.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			    ajx.send(creds);
			}

			window.setInterval(requestMSGs, 1500);

		</script>";

	}

?>




<!--Script to keep input area always focused-->
<script type="text/javascript">

	function focusInput() {
	try {
		document.getElementById("input").focus();

	} catch(err) {
	}

}

window.setInterval(focusInput, 500);

</script>
</body>
</html>