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
		include('Crypt/RSA.php');
		$rsa = new Crypt_RSA();
		$rsa->loadKey(
			array(
				'e' => new Math_BigInteger('10001', 16),
				'n' => new Math_BigInteger('0xa84f535e7f4531973c4f966e2f0cac1594057ce2618bb7031ec46933638e03f6b8d08e2ef0a18d61605b13347f8648c2729596683dec96efb17c74635a4a3a0a71410ba42bdfcba51051e6213f7110926c16d4f40d570e4cc9d96a380a48d84a4aba9feffcf86f26434e20b55076eed4a8fcd67af3266f4453ec7cd72e47d127', 16)
			)
		);

		$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
		$id = $_POST['id'];
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

		date_default_timezone_set("Asia/Seoul"); // 한국 시간대(KST)
		file_put_contents('log2.txt',$id.'   '.date('Y').'.'.date('m').'.'.date('d').'.'.date('H').'.'.date('i').'.'.date('s')."\r\n",FILE_APPEND);
		
		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/score/lectureList.do?schedule=1602');
		$data = curl_exec($ch);	
		$ireum = explode(' 학생',explode('<a class="brand" href="#left_songjuk">',$data)[1])[0];
		
		preg_match_all('/<td class="item1" ><a href="\/student\/score\/lectureInfo\.do\?lectureOpenNo=([0-9]+)/', $data, $match);

		foreach($match[1] as $each) {
			curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/score/lectureInfo.do?lectureOpenNo=' . $each);
			$data = curl_exec($ch);
			$name = explode(' &gt; 개설교과 &gt; 경기과학고등학교</title>',explode('<title>',$data)[1])[0]; // 미분방정식(미분방정) -> 미분방정식
			file_put_contents('lectures_' . $each . '.txt', explode('<!DOCTYPE html>', explode('<div id="content" class="span9" >', $data)[1])[0]);
			
			$list = explode('<td class="item2" >', $data);
			
			// $time = count(explode('<li><span class="strong">', $data)) - 1;  // 선생님이 2명 이상 등록되어 있으면 망함...
			$data_for_time_calc = explode('</dd>',explode('<dt>수강시간표</dt>',$data)[1])[0];
			$time = count(explode('<li><span class="strong">', $data_for_time_calc)) - 1;
			
			if ($time == 0) { // <li><span class="strong">의 횟수세는걸로 안되면 이걸로 함
				$time = explode('</dd>', explode('<dd>', $data)[3])[0];
			}
			//$name = explode('</span>', explode('<dd><span class="strong">', $data)[1])[0];
			$cnt = 0;
			foreach($list as $i) {
				if ($cnt++ == 0) continue;
				$key = explode('</td>', $i)[0];
				$index[$key] = explode('</td>', explode('<td class="item1" >', $i)[1])[0];
				if (!isset($same[$key])) {
					$same[$key] = 0;
					$lst[$key] = '';
				}
				$same[$key] += $time;
				$lst[$key] .= $name . ', ';
			}
		}
		/* (2학기에는 필요없음)
		// Homeroom teacher (experimental function)
		curl_setopt($ch, CURLOPT_URL, 'http://student.gs.hs.kr/student/well/editOutPass.do');
		$data_teacher = explode('" readonly="readonly" onclick="teacherPopup(this.id);"/>',explode('<input type="text" name="teacherName" id="teacherName"  value="', curl_exec($ch))[1])[0];
		$check_teacher = file_get_contents('teacherlist.html');
		if(count(explode($hakneon.'-'.$ban,$check_teacher))==1){ // 리스트에 없을 경우에만 추가.
			file_put_contents('teacherlist.html',$hakneon . '-' . $ban . ':' . $data_teacher . '선생님', FILE_APPEND | LOCK_EX);
		}
		*/
		// Sorting
		ksort($same);
		arsort($same, SORT_NUMERIC);
		
		// Print results
		echo '* 학점 수가 아닌 강의시간 수로 계산됩니다.<br />';
		echo $ireum . '(' . $id . ')의 2016학년도 1학기 공통수강학생 정보<br />';
		echo '<button class="btn" data-clipboard-action="copy" data-clipboard-target="clipboardjs"> 정보 복사 </button>';
		echo '<br />';
		echo '<clipboardjs>';
		$cnt = 0;
		foreach($same as $key => $val) {
			if ($cnt++ == 0) continue;
			echo $index[$key] . '(' . $key . '): ' . $val . '시간 (' . mb_substr($lst[$key], 0, -2) . ')<br />';
		}
		echo '</clipboardjs>';
		echo '<br />';
		echo '* 수업시간이 나오지 않은 과목은 1학점 = 1시간으로 계산됩니다.<br />';
		//echo '<a href="class.php">2016년도 반 배정</a>도 확인하세요!<br />';
	}
	else {
		echo '<form action="./check.php" method="POST"><input type="text" name="id" placeholder="송죽학사 아이디(학번)"><input type="password" name="password" placeholder="송죽학사 비밀번호"><input type="submit"></form><br />';
		echo '* 비밀번호는 서버에 저장되지 않으며 RSA Encryption되어 전송됩니다.<br />';
		echo '* 각 강의별 학생 목록이 전체 통계 산출을 위해 수집됩니다.<br />';
		echo '* 결과 산출에는 10초 이상 소요될 수 있습니다.<br />';
	}
	require_once('footer.php');
	?>
	<!-- Clipboard js -->
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
	
	<!-- ajax -->
</body>
</html>