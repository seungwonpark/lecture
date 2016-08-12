# lecture
경기과학고 [송죽학사](http://student.gs.hs.kr)를 통한 공통수강/공강학생 확인 매크로

* Based on : [PHP](http://php.net), [php**sec**lib](http://phpseclib.sourceforge.net/)

* Also uses : [Bootstrap](http://getbootstrap.com/), [clipboard.js](https://clipboardjs.com/)

- Initiated by @hletrd

# 개인정보 사용 현황
## 기본(2016.2학기 - check4.php 기준)
로그인 정보를 입력하고 '제출' 버튼을 클릭함으로써, 당신은 :
- 조사자 전체 명단과 각 강의별 수강명단에 자신의 이름과 학번을 추가하고,
- 웹페이지 관리를 위한 로그 파일에 클릭 기록을 남기고,
- 강의별 정보(강의명, 강의코드) 조사를 위한 정보를 제공하는 데에

동의하게 됩니다.

## check.php
- 개인의 수강목록, 강의별 인원정보가 모두 공개되어 있을 때  
- 2015.1, 2015.2학기에 사용됨
  1. 강의/성적 - [수강목록](http://student.gs.hs.kr/student/score/lectureList.do)
  2. 강의별 개설교과정보

## check3.php
- 개인의 강의시간표만 공개되어 있을 때
- 2016.1학기에 사용됨
- 공통**공강**학생 확인 가능
- **조사에 참여한 학생과의 비교만 가능**
  1. 강의/성적 - [나의수강](http://student.gs.hs.kr/student/score/graduationRequestInfo.do) (학반 확인을 위해 수강목록 대신 나의수강 사용)
  2. 강의/성적 - [강의시간표](http://student.gs.hs.kr/student/score/studentTimetable.do?schedule=1601)

## check4.php
- 개인의 강의시간표도 비공개이고, [나의수강](http://student.gs.hs.kr/student/score/graduationRequestInfo.do) 에서 강의목록만 뜬 경우
- 2016.2학기에 사용됨
- **조사에 참여한 학생과의 비교만 가능**
  1. 강의/성적 - [나의수강](http://student.gs.hs.kr/student/score/graduationRequestInfo.do)
