<?php


require_once(__DIR__."/lib/scraping.php");
require_once(__DIR__."/lib/function.php");

//dbパラメーター
require_once("jmtyDbPath/scrapingDBParameter.php");
//スクレイピングの実行サンプル。例としてジモティーを使う
//備忘録
//サイトurlを指定してスクレイピング
//その中から正規表現を使ってアウトラインパターンから一度抜き出し、
//さらにその中からディティールパターンでもう一回抜き出す
//




//サンプル、サイクルスポット電動自転車安いもの順
$ECsiteList=array(
	"Jmty"=>array(		//ジモティー
		"Name"=>"Jmty",	//サイト名,吐き出すファイル名にもなる
		//"Url"=>"https://cyclespot.jp/store/CategoryList.aspx?ccd=F1000518&wkcd=F1000510&SKEY=price&SORDER=0&page=",	//スクレイピングするurl(電動自転車全体)
		"url"=>"https://cyclespot.jp/store/CategoryList.aspx?ccd=F1000544&wkcd=F1000510-F1000518&SKEY=price&SORDER=0&page=|",	//スクレイピングするurl(子乗せ電動自転車)pageナンバーは"|"で記述すること。
		"startPageNo"=>"0",	//最初のページナンバー
		"nextPageNo"=>"1",	//次のページナンバー
		"FileName"=>"CycleSpot.html",	//吐き出すファイル名
		"outlinePattern"=>"/並び順.[\s\S]*?pagination/iu",	//アウトラインパターン
		"detailsPattern"=>	//ディティールパターン	
		"/(lis_mk.[\s\S]*?sclistanc)|".
		"(lis_nm\">.[\s\S]*?<\/span>)|".
		"(priceB2\'>\n?<span\s?class=\"priceNumeric\">[0-9]{2,3}\,[0-9]{3}<\/span>)/iu",

		//index置き換えパターン
		"changeIndexSarch"=>array(
			"/^.[\s\S]*?("."href".")(.[\s\S]*?$)/iu",
			"/("."lis_nm".")(.[\s\S]*?$)/iu",
			"/("."price".")(.[\s\S]*?$)/iu"
		),	
		"changeIndexName"=>array(
			"url",
			"mongon",
			"$1"
		),
		//トリミングパターン
		"replacePattern"=>array(
			"/(^.[\s\S]*?href=\')(.[\s\S]*?)(\'\sonclick.[\s\S]*?$)/iu",
			"/(lis_nm\">)(.[\s\S]*?)(<\/span>)/iu",
			"/(^.[\s\S]*?)([0-9]{1,4})\,([0-9]{3})(.[\s\S]*?$)/iu"
		),
		//"replacement"=>"$2",
		"replacement"=>array(
			"https://cyclespot.jp"."$2",
			"$2",
			"$2$3"
		),
		//特殊修正
		//一律で置き換え、トリムできないパターンはこちらで処理する。
		//車種確定用
		"modifi"=>array(
			"tableName"=>"shashuIndexCS",	//車種確定用のテーブル名
			"subjectName"=>"mongon",		//車種確定時の正規表現対象subject名
			"addColum"=>"index_no",		//車種確定時の追加コラム名
			"patternKey"=>array("seikihyougen_name","seikihyougen_year")	//同じく車種確定時のパターンキー。複数ある場合foreachで回して先行先読みでAND化
		),
		//書き込みdb、テーブル名,付随情報
		"recordTable"=>array(
			"tableName"=>"cyclespotScrapingResult",		//テーブル名
			"dateFlag"=>true,							//日付情報の追加,フラグを立てると別途カラム「YMD」にINSERT当日の日付を入れる
			"outputDataColumn"=>array("href","mongon","price","index_no")	//inputArrayを収めるdbカラム。"YMD"はdateFlagが立っている場合に備えて使用禁止。
		)
		
	)
);
$outputData="no,href,mongon,price,index_no\r\n";

$siteGet=new siteGet();
$putCsv=new putCsv();
$putPrintR=new putPrintR();
$db=new db();
$shashuKakutei=new shashuKakutei($db);	//センス無いネーミング

//サイトごとに取得、出力
foreach($ECsiteList as $EC){
	$siteGet->EC=$EC;
	$result=$siteGet->go();

	//車種確定(入力配列に)
	$shashuKakutei->inputArray=$result;		//スクレイピング配列
	$shashuKakutei->inputArrayKeyColum=$EC["modifi"]["subjectName"];	//正規表現対象カラム名(mongon)
	$shashuKakutei->db=$db;		//db
	$shashuKakutei->shashuIndexTableName=$EC["modifi"]["tableName"];	//車種インデックステーブル名
	$shashuKakutei->modificationPatternKey=$EC["modifi"]["patternKey"];	//車種インデックスで利用するキー
	$shashuKakutei->addColum=$EC["modifi"]["addColum"];					//車種インデックスで紐付けするカラム
	$shashuKakutei->recordTable=$EC["recordTable"];								//db書き込み情報
	$result=$shashuKakutei->go();
	$index=$shashuKakutei->shashuIndex;	//車種インデックス出力(デバグ用)



	//test
	$putPrintR->array=$index;
	$putPrintR->filename="shashuIndex.txt";
	$putPrintR->go();

	$putPrintR->array=$result;
	$putPrintR->filename=$EC["Name"]."Result.txt";
	$putPrintR->go();

//csv形式での出力
	$putCsv->filename=$EC["Name"]."Result.csv";
	$putCsv->array=$result;
	$result=$putCsv->go($outputData);
}
?>
