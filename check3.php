<!DOCTYPE html>
<html>
<head>
	<?php require_once('header.html'); ?>
</head>

<body>
	<?php
	error_reporting(0);
	
	require_once('navbar.php');
	if (isset($_POST['id'])) {
		$id = $_POST['id'];
		if($id < 14001 || ($id > 14129 && $id < 15001) || $id > 15150){
			echo '<b>Access Denied.<br /></b>';
			echo '이상현상이 감지되어 관리자에게 알림이 전송되었습니다.';
			file_put_contents('accessdenial3.txt',date('Y').'년 '.date('m').'월 '.date('d').'일 '.date('H').'시 '.date('i').' 분'.date('s').'초 ');
		}
		else{
		include('Crypt/RSA.php');
		$rsa = new Crypt_RSA();
		$rsa->loadKey(
			array(
				'e' => new Math_BigInteger('10001', 16),
				'n' => new Math_BigInteger('0xa84f535e7f4531973c4f966e2f0cac1594057ce2618bb7031ec46933638e03f6b8d08e2ef0a18d61605b13347f8648c2729596683dec96efb17c74635a4a3a0a71410ba42bdfcba51051e6213f7110926c16d4f40d570e4cc9d96a380a48d84a4aba9feffcf86f26434e20b55076eed4a8fcd67af3266f4453ec7cd72e47d127', 16)
			)
		);

		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$pw = $rsa->encrypt($_POST['password']);
		$pw = unpack('H*', $pw)[1];

		$ch = curl_init('http://student.gs.hs.kr/student/api/login.do');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, md5(microtime()));
		curl_setopt($ch, CURLOPT_COOKIEFILE, md5(microtime()));
		curl_setopt($ch, CURLOPT_USERAGENT, 'crawling');
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'key=d56b699830e7&userId=' . $id . '&pwd=' . $pw . '&type=STUD');
		$data = curl_exec($ch);
		$data_teacher = curl_exec($ch);

		if (strpos($data, 'OK') === false) {
			echo '로그인 실패';
			exit;
		}

		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "");

		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/score/graduationRequestInfo.do');
		$data = explode('<!-- 본문 -->', curl_exec($ch))[1];
		
		preg_match_all('/<td class="item3" ><a href="\/student\/score\/lectureInfo\.do\?lectureOpenNo=([0-9]+)/', $data, $match);
		// ㄴ> 이 학생이 듣는 강의의 번호들만을 수집. $data = graduation 어쩌구 일때 써야함!
			
			
		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/score/studentTimetable.do?schedule=1602');
		$rawdata = curl_exec($ch);
		$ireum = explode(' 학생',explode('<a class="brand" href="#left_songjuk">',$rawdata)[1])[0];
		$data = explode('<h3 class="hidden" >강의 시간표</h3>',$rawdata)[1];	
		
		$weekday = array("월","화","수","목","금");
		$id_list = file_get_contents('idlist3.txt');
		date_default_timezone_set("Asia/Seoul"); // 한국 시간대(KST)
		file_put_contents('log3.txt',$id.'   '.date('Y').'.'.date('m').'.'.date('d').'.'.date('H').'.'.date('i').'.'.date('s')."\r\n",FILE_APPEND);
		
		if(count(explode($id,$id_list)) == 1){ // 조사에 참여했던 적이 없는 경우
			file_put_contents('idlist3.txt',$id.' ',FILE_APPEND);
		}
		
		$using_data = explode('<td class="item',$data); // 배열 여러개로 퍼버벙
		for($j=1;$j<=5;$j++){
			for($i=0;$i<7;$i++){ // 7교시까지만
				$num = 15 * $i + $j;
				if(count(explode('gs_bg_light_gray"', $using_data[$num])) == 1){ // 공강!
					if(count(explode($id,$id_list)) == 1){ // 조사에 참여했던 적이 없는 경우
						file_put_contents($i . '-' . $j . '.txt', ', '.$ireum.'('.$id.')',FILE_APPEND);
					}
					// 공강 목록을 따로 만들 필요 없이 바로 계산
					$text_g = file_get_contents($i . '-' . $j . '.txt');
					for($other_g = 14001; $other_g < 15140; $other_g++){
						if($other_g == 14140){
							$other_g = 15000;
							continue;
						}
						if($other_g == $id) continue; // exclude $id itself
						if(count(explode($other_g, $text_g)) != 1){
							$index_gong[$other_g] = array_pop(explode(', ',explode('('.$other_g,$text_g)[0]));
							$samegonglist[$other_g] .= $weekday[$j-1] . ($i+1) . ', ';
							$cnt_gong[$other_g]++;
						}
					}
				}
			}
		}
		ksort($cnt_gong);
		arsort($cnt_gong,SORT_NUMERIC);
		
		echo '* 조사에 참여한 학생들만 표시됩니다.<br />';
		echo '* 8,9교시는 공강으로 인정할 수 없다는 의견이 제기되어 목록에서 제외하였습니다. <br />';
		
		echo '<br />';
		echo '<button class="btn" data-clipboard-action="copy" data-clipboard-target="clipboardjs2">공통공강 복사</button>';
		echo '<br />';
		echo '<clipboardjs2>';
		echo $ireum . '(' . $id . ')의 2016학년도 2학기 공통공강학생 정보<br />';
		echo '<br />';
		foreach($cnt_gong as $key_gong => $val_gong){ // 겹치는 공강
			if($cnt_gong == 0) continue;
			echo $index_gong[$key_gong] . '(' . $key_gong . ') : ' . $val_gong . '시간 (' . mb_substr($samegonglist[$key_gong], 0, -2) . ')<br />';
		}
		echo '</clipboardjs2>';
		echo '<br />';
		}
	} else {
		echo '<form action="./check3.php" method="POST"><input type="text" name="id" placeholder="송죽학사 아이디(학번)"><input type="password" name="password" placeholder="송죽학사 비밀번호"><input type="submit"></form><br />';
		echo '* 비밀번호는 서버에 저장되지 않으며 RSA Encryption되어 전송됩니다.<br />';
		echo '* 개인의 강의시간표가 수집됩니다.<br /><br />';
		echo '* 결과 산출에는 10초 이상 소요될 수 있습니다.<br />';
		echo '그러니 제발 여러번 누르지 말아주세요...!!! ㅜㅜ <br/>';
		echo '서버가 느릴 경우 500 Internal Server Error가 발생할 수 있습니다. 이 경우 다시 시도해주세요.';
	}
	require_once('footer.php');
	?>
	<!-- 2. Include library -->
    <script src="static/js/clipboard.min.js"></script>

    <!-- 3. Instantiate clipboard -->
    <script>
    var clipboard = new Clipboard('.btn');

    clipboard.on('success', function(e) {
        console.log(e);
    });

    clipboard.on('error', function(e) {
        console.log(e);
    });
    </script>
	
</body>
</html>