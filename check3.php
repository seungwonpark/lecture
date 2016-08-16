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
		}
		else{
		include('Crypt/RSA.php');
		$rsa = new Crypt_RSA();
		$rsa_key_txt = file_get_contents('key.txt');
		$rsa->loadKey(
			array(
				'e' => new Math_BigInteger('10001', 16),
				'n' => new Math_BigInteger($rsa_key_txt, 16)
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
		$id_list = file_get_contents('idlist.txt');
		date_default_timezone_set("Asia/Seoul"); // 한국 시간대(KST)
		file_put_contents('log.txt',$id.'   '.date('Y').'.'.date('m').'.'.date('d').'.'.date('H').'.'.date('i').'.'.date('s')."\r\n",FILE_APPEND);
		$gongamount = 1;
		if(count(explode($id,$id_list)) == 1){ // 이전까지 조회한 경우가 없을 때에만 시행. append 모드라서...
			file_put_contents('idlist.txt',$id.' ',FILE_APPEND);
			
			$using_data = explode('</td>',$data); // 배열 여러개로 퍼버벙
			for($i=0;$i<190;$i++){ // 19 * 10
				if($i == 171) break; // 10교시는 공강 계산에도 사용안함.
				$j = $i % 19;
				if($j > 1 && $j < 7){ // 과목명 + 과목번호. 나머지는 필요없음
					if(count(explode('lectureOpenNo',$using_data[$i])) != 1){ // 공강 아님
						$lectureNo = explode('"',explode('<a href="/student/score/lectureInfo.do?lectureOpenNo=',$using_data[$i])[1])[0];
						$lectureName = explode('</a',explode('>',explode('OpenNo',$using_data[$i])[2])[1])[0];
						file_put_contents($lectureNo.'.txt',', '.$ireum.'('.$id.')',FILE_APPEND); 
						// ㄴ> 이름+학번 추가. 이름+학번이 여러번 기재되기도 하지만 수업시간 계산을 위함임.
						$correlation_check = file_get_contents('new_correlation.txt');
						if(count(explode($lectureNo,$correlation_check)) == 1){ // 대응 데이터가 없을 경우에만 추가
							file_put_contents('new_correlation.txt','<'.$lectureNo.':'.$lectureName.'>', FILE_APPEND);
						}
					}
					else{ // 공강
						file_put_contents($i.'.txt',', '.$ireum.'('.$id.')',FILE_APPEND);
						$gonglist[$gongamount++] = $i; // 이 학생의 공강 목록 생성 (2,4,25,...)
					}
				}
			}
		}
		else{
			$using_data = explode('</td>',$data); // 배열 여러개로 퍼버벙
			for($i=0;$i<190;$i++){
				if($i == 171) break;
				$j = $i % 19;
				if($j > 1 && $j < 7){
					if(count(explode('lectureOpenNo',$using_data[$i])) == 1){
						$gonglist[$gongamount++] = $i; // 이 학생의 공강 목록 생성 (2,4,25,...)
					}
				}
			}
		}
		
		// ksort($gonglist,$gonglist[$gongamount]%19); // 공강이 월1 -> 월2 -> 월3 -> ... ->금9 로 나열되게 함 
		// 으 안된다
		
		
		
		// 계산!
		
		foreach($match[1] as $each){ // $each 는 이 학생이 듣는 $lectureNo 들임.
			$text = file_get_contents($each .'.txt');
			$text_cor = file_get_contents('new_correlation.txt'); // 강의명 불러오기
			$lectureName = explode('>',explode($each.':',$text_cor)[1])[0];
			for($other = 14001 ; $other < 14150 ; $other++){ // 학번들 죄다 찾아보기. 32기 
				if($other == $id) continue; // 본인은 제외
				if(count(explode($other,$text)) != 1){
					$index[$other] = array_pop(explode(', ',explode('('.$other,$text)[0]));
					$cnt[$other] += count(explode($other,$text)) - 1;
					$samelecturelist[$other] .= $lectureName . ', ';
				}
			}
			for($other = 15001 ; $other < 15150 ; $other++){ // 학번들 죄다 찾아보기. 33기 
				if($other == $id) continue; // 본인은 제외
				if(count(explode($other,$text)) != 1){
					$index[$other] = array_pop(explode(', ',explode('('.$other,$text)[0]));
					$cnt[$other] += count(explode($other,$text)) - 1;
					$samelecturelist[$other] .= $lectureName . ', ';
				}
			}
		}
		
		for($l=1;$l<$gongamount;$l++){ // 겹치는 공강 계산.
			$i = $gonglist[$l]; // 이 사람의 공강 시간 목록
			$text_g = file_get_contents($i.'.txt');
			$text_cor_g = file_get_contents('new_correlation.txt'); // 강의명 불러오기
			$lectureName_g = explode('>',explode($i.':**',$text_cor_g)[1])[0]; // 공강의 경우 특별히 '**'가 붙어있음. 실제 출력떄는 제외.
			for($other_g = 14001 ; $other_g < 14150 ; $other_g++){ // 학번들 죄다 찾아보기. 32기 
				if($other_g == $id) continue; // 본인은 제외
				if(count(explode($other_g,$text_g)) != 1){
					$index_gong[$other_g] = array_pop(explode(', ',explode('('.$other_g,$text_g)[0]));
					if($i < 135){ // 8,9교시는 시간 계산에는 제외
						$cnt_gong[$other_g] += count(explode($other_g,$text_g)) - 1;
					}
					$samegonglist[$other_g] .= $lectureName_g . ', ';
				}
			}
			for($other_g = 15001 ; $other_g < 15150 ; $other_g++){ // 학번들 죄다 찾아보기. 33기 // 여기가 좀 수상...
				if($other_g == $id) continue; // 본인은 제외
				if(count(explode($other_g,$text_g)) != 1){
					$index_gong[$other_g] = array_pop(explode(', ',explode('('.$other_g,$text_g)[0]));
					if($i < 135){ // 8,9교시는 시간 계산에는 제외
						$cnt_gong[$other_g] += count(explode($other_g,$text_g)) - 1;
					}
					$samegonglist[$other_g] .= $lectureName_g . ', ';
				}
			}
		}
		
		ksort($cnt);
		arsort($cnt, SORT_NUMERIC);
		ksort($cnt_gong);
		arsort($cnt_gong,SORT_NUMERIC);
		
		echo '<br />';
		echo '* 데이터는 계속 갱신되며, 조사에 참여한 학생들만 표시됩니다.<br />';
		echo '* 학점 수가 아닌 강의시간 수로 계산됩니다.<br />';
		echo '* 8,9교시는 공강으로 인정할 수 없다는 의견이 제기되어 시간 계산에는 제외하고 목록에는 포함하였습니다. <br />';
		
		echo $ireum . '(' . $id . ')의 2016학년도 2학기 공통수강/<b>공강</b>학생 정보<br />';
		echo '<button class="btn" data-clipboard-action="copy" data-clipboard-target="clipboardjs1"> (1) 공통수강 복사</button>';
		echo '<br />';
		echo '<clipboardjs1>';
		echo '<b>(1) 공통수강시간 </b>';
		echo '<br />';
		foreach($cnt as $key => $val){ // 겹치는 수업시간
			if($cnt == 0) continue;
			echo $index[$key] . '(' . $key . ') ' . $val . '시간 (' . mb_substr($samelecturelist[$key], 0, -2) . ')<br />';
		}
		echo '</clipboardjs1>';
		echo '<br />';
		echo '<button class="btn" data-clipboard-action="copy" data-clipboard-target="clipboardjs2"> (2) 공통공강 복사</button>';
		echo '<br />';
		echo '<clipboardjs2>';
		echo '<b>(2) 공통공강시간 </b>';
		echo '<br />';
		foreach($cnt_gong as $key_gong => $val_gong){ // 겹치는 공강
			if($cnt_gong == 0) continue;
			echo $index_gong[$key_gong] . '(' . $key_gong . ') ' . $val_gong . '시간 (' . mb_substr($samegonglist[$key_gong], 0, -2) . ')<br />';
		}
		echo '</clipboardjs2>';
		echo '<br />';
		}
	} else {
		echo '<form action="./check3.php" method="POST"><input type="text" name="id" placeholder="송죽학사 아이디(학번)"><input type="password" name="password" placeholder="송죽학사 비밀번호"><input type="submit"></form><br />';
		echo '* 비밀번호는 서버에 저장되지 않으며 RSA Encryption되어 전송됩니다.<br />';
		echo '* 개인의 강의시간표가 수집됩니다.<br />';
		echo '* 결과 산출에는 10초 이상 소요될 수 있습니다.<br />';
	}
	require_once('footer.php');
	?>
	<!-- 2. Include library -->
    <script src="clipboard.min.js"></script>

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