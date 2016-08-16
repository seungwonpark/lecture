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
			file_put_contents('accessdenial.txt',date('Y').'년 '.date('m').'월 '.date('d').'일 '.date('H').'시 '.date('i').' 분'.date('s').'초 ');
			exit;
		}
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
		
		/** List of text files **/
		/*
		
		idlist.txt : save id to determine whether or not already surveyed
		+ save name-id
		
		lecturelist.txt : save lecture's information - lectureNo, lectureName, lectureTime
		<lectureNo>.txt : save name of students who participate in
		
		*/
		
		$id_list = file_get_contents('idlist.txt');
		date_default_timezone_set("Asia/Seoul"); // 한국 시간대(KST)
		file_put_contents('log.txt',$id.'   '.date('Y').'.'.date('m').'.'.date('d').'.'.date('H').'.'.date('i').'.'.date('s')."\r\n",FILE_APPEND);
		
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "");
		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/score/graduationRequestInfo.do'); // 강의/성적 - 나의수강 탭
		$data = curl_exec($ch);
		$studentname = explode(' 학생', explode('href="#left_songjuk">', $data)[1])[0];
		$data = explode('<!-- 본문 -->', $data)[1];
		$data = explode('2016년 1학기',$data)[0];
		
		$notsurveyed = 0; // defined boolean(0,1)
		if(count(explode($id,$id_list)) == 1){ // not surveyed yet
			$notsurveyed = 1;
		}
		
		if($notsurveyed == 1){
			file_put_contents('idlist.txt',$studentname.'('.$id.') ',FILE_APPEND);
		}
		
		$splited = explode('"/student/score/lectureInfo.do?lectureOpenNo=', $data);
		$lecture_list = file_get_contents('lecturelist.txt');
		
		$lectureTXT = file_get_contents($lectureNo.'.txt');
		for($i = 1;$i < count($splited);$i += 9){
			$lectureNo = explode('">', $splited[$i])[0]; // get lectureNo
			$lectureName = explode('</a></td>', explode('">', $splited[$i+3])[1])[0]; // get lecture's name
			$get_lectureTime = explode('</a></td>', explode('">', $splited[$i+4])[1])[0];      // get lecture's time amount
			$tab_array = array("\r\n", "\n", "\r", "\t");
			$replace = array('','','','');
			$lectureTime = str_replace($tab_array, $replace, $get_lectureTime);
			if(count(explode($lectureNo, $lecture_list)) == 1){ // lecture info not obtained yet
				file_put_contents('lecturelist.txt', '<No:'.$lectureNo.'/Name:'.$lectureName.'/Time:'.$lectureTime.'>'."\r\n", FILE_APPEND);
			}
			if($notsurveyed == 1){
				file_put_contents($lectureNo.'.txt', '['.$id.']'.$studentname.'('.$id.')'."\r\n", FILE_APPEND);
			}
			$lectureTXT = file_get_contents($lectureNo.'.txt');
			for($other = 14001;$other < 14150;$other++){
				if($other == $id) continue; // exclude self
				if(count(explode($other,$lectureTXT)) != 1){
					$sameLectureList[$other] .= $lectureName . ', ';
					$cnt[$other] += $lectureTime;
					$otherstudentname[$other] = explode($other.']', explode('('.$other, $lectureTXT)[0])[1];
				}
			}
			for($other = 15001;$other < 15150;$other++){
				if($other == $id) continue; // exclude self
				if(count(explode($other,$lectureTXT)) != 1){
					$sameLectureList[$other] .= $lectureName . ', ';
					$cnt[$other] += $lectureTime;
					$otherstudentname[$other] = explode($other.']', explode('('.$other, $lectureTXT)[0])[1];
				}
			}
		}
		// github.com/seungwonpark/lecture/issues/3
		$otherstudentname[14029] = '김태연';
		
		// sorting
		ksort($cnt);
		arsort($cnt, SORT_NUMERIC);
		
		// printing
		echo '* 시간 수가 아닌 학점 수로 계산됩니다.';
		echo '<br />';
		echo $studentname . '의 2016학년도 2학기 공통수강학생 정보';
		echo '<br />';
		echo '<button class="btn" data-clipboard-action="copy" data-clipboard-target="clipboardjs1"> 공통수강정보 복사</button>';
		echo '<br />';
		echo '<clipboardjs1>';
		foreach($cnt as $key => $val){
			if($cnt == 0) continue;
			echo $otherstudentname[$key] . '(' . $key . ') : ' . $val . '학점 (' . mb_substr($sameLectureList[$key], 0, -2) . ') <br />';
		}
		echo '</clipboardjs1>';
	}
	else {
		echo '<form action="./check4.php" method="POST"><input type="text" name="id" placeholder="송죽학사 아이디(학번)"><input type="password" name="password" placeholder="송죽학사 비밀번호"><input type="submit"></form><br />';
		echo '* 비밀번호는 서버에 저장되지 않으며 RSA Encryption되어 전송됩니다.<br />';
		echo '* 강의/성적 - 나의수강 탭의 페이지 코드가 수집됩니다.<br /><br />';
		echo '* 결과 산출에는 10초 이상 소요될 수 있습니다.<br />';
		echo '그러니 제발 여러번 누르지 말아주세요...!!! ㅜㅜ';
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