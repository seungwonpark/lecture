<!DOCTYPE html>
<html>
<head>
	<?php require_once('header.html'); ?>
</head>
<body>
<?php
	error_reporting(0); // 자꾸 strpos 가지고 에러 떠서...
	require_once('navbar.php');
	$list = scandir('./');
	$samename = file_get_contents('samename.txt'); // 동명이인 리스트 참조하여 학번과 함께 출력
	foreach($list as $each) {
		if (strpos($each, 'lecture') !== false) {
			$data = file_get_contents($each);
			$human = explode('<tr>', $data);
			$cnt = 0;
			foreach($human as $person) {
				if ($cnt++ == 0) continue;
				$key = explode('</td>', explode('<td class="item2" >', $person)[1])[0];
				$name = explode('</td>', explode('<td class="item1" >', $person)[1])[0];
				$class = explode('</td>', explode('<td class="item3" >', $person)[1])[0] . '-' . explode('</td>', explode('<td class="item4" >', $person)[1])[0];
				
				//$hakneon = explode('</td>', explode('<td class="item3" >', $person)[1])[0];
				//$ban = explode('</td>', explode('<td class="item4" >', $person)[1])[0];
				if (!isset($classlist[$class])) {
					$classlist[$class] = '';
					$classlist_print[$class] = '';
					$cnt2[$class] = 0;
				}
				if (strpos($classlist[$class], $key) === false) { // 해당 $key 가 없을 경우에만 리스트에 추가. 따라서 $key 가 중요. 
				//if (strpos($classlist[$class], $key) == false) {
					// 그래서 $classlist 와 $classlist_print 를 분리함...
					$index[$key] = $name;
					$classlist[$class] .= $name . '(' . $key . ') ';
					if(strpos($samename,$key) !== false){ // 동명이인 리스트에 학번이 포함되어 있을 경우 (이름으로 하니까 안됨...)
						$classlist_print[$class] .= ', '. $name . '(' . $key . ')';
					}
					else{
						$classlist_print[$class] .= ', '. $name;
					}
					$cnt2[$class]++;
				} 
			}
		}
		if (strpos($each, 'teacher') !== false) {
			$data = file_get_contents($each);
			for($i=1;$i<=3;$i++){
				for($j=1;$j<=8;$j++){
					$data_teacher[$i][$j] = explode('선생님', explode($i . '-' . $j . ':' ,$data)[1])[0];
				}
			}
		}
	}
	ksort($classlist_print);
	$cnt = 0;
	foreach($classlist_print as $key => $val) {
		if ($cnt++ == 0) continue;
		$hakneon = explode('-',$key)[0];
		$ban = explode('-',$key)[1];
		if($ban == 0) continue; // 2학년 0반, 3학년 0반
		if(empty($data_teacher[$hakneon][$ban])){
			echo $key . '(현재 ' . $cnt2[$key] . '명 파악됨) - ' . substr($val,2) . '<br />';
		}
		else{
			echo $key . '(현재 ' . $cnt2[$key] . '명 파악됨, ' . $data_teacher[$hakneon][$ban] . ' 선생님) - ' . substr($val,2) . '<br />';
		}
	}
	echo '<br />* 본 정보는 수강신청 통계 산출 과정에서 얻은 정보를 수합하여 만들어지며, 정보 수합 상황에 따라 지속적으로 갱신됩니다.<br />';
	echo '* 추가기능 - 담임선생님 확인 기능 - 은 외출증 승인교사 정보를 수합하여 만들어집니다.<br />';
	echo '<s>으아아 이름 정렬 귀찮아</s>';
	require_once('footer.php');
	?>
</body>
</html>