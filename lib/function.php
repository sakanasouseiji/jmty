<?php

//データをcsv形式で吐き出す。デバグ用のクラス
//classにしているのはrequire_onceで先頭に並べたいだけ
class	putCsv{
	public	$filename;
	public	$array;
	function go($outputData=""){
		$filename=$this->filename;
		$array=$this->array;
		//見出し行
		//$outputData="no,href,mongon,price\n";

		//本文
		foreach($array	as	$no	=>	$kobetu){
			$outputData=$outputData.$no;
			foreach($kobetu	as $key => $i){
				$outputData=$outputData.",".$i;
			}
			$outputData=$outputData."\r\n";
		}
	$result=file_put_contents($filename,var_export($outputData,true)	);
	print $outputData;
	return $result;
	}
}
//データをprint_rでtxt形式で吐き出す。上に同じデバグ用のクラス。
class	putPrintR{
	public	$filename;
	public	$array;
	function	go(){
		$array=$this->array;
		$filename=$this->filename;


		//本体ここから
		//print "<pre>";
		$txt=print_r($array,true);
		file_put_contents($filename,$txt);
		//print "</pre>";
		//ここまで

		return;
	}
}


//車種確定()
//車種確定は一度確定するとbreakで次の車種に飛ぶので正規表現は長いものを先にすることを忘れないこと。
class	shashuKakutei{
	public	$inputArray;
	public	$indexArrayKeyColum;
	public	$result;
	public	$shashuIndexTableName;
	public	$shashuIndex;
	public	$modificationPatternKey;
	public	$addColum;
	public	$db;
	public	$recordTable;
	public	$putPrintR;

	//事前準備
	function	__construct($db){
		$this->db=$db;
		$openFlag=$this->db->open();
		if(	!isset($openFlag)	){
			print "dbオープン失敗、終了します。\r\n";
			exit();
		}
	}


	function	go(){
		$tableName=$this->shashuIndexTableName;
		$patternKey=$this->modificationPatternKey;	//ここではソート用として使う
		//車種インデックス読み込み
		$this->shashuIndex=$this->db->readAll($tableName,$patternKey);
		$shashuIndex=$this->shashuIndex;

		//スクレイピング結果と車種インデックスの連携その1(テスト)php上で変数で行なう

		
		$inputArray=$this->inputArray;
		$addColum=$this->addColum;
		$inputArrayKeyColum=$this->inputArrayKeyColum;
		$modificationPatternKey=$this->modificationPatternKey;
		$modifiLog="";
		$this->putPrintR=new putPrintR();
		$putPrintR=$this->putPrintR;
		$matchCommand=array();

		foreach(	$inputArray	as	$i	=>	$ob	){
			$subject=$ob[$inputArrayKeyColum];

			foreach(	$shashuIndex	as	$jitensha	){	
				//新規判定ルーチン(if文)
				foreach(	$modificationPatternKey	as	$key => $j	){
					$keyBit=$jitensha[$j];
					$pattern="/".$keyBit."/ius";


					//preg_matchと同時に$match[0]に内容がある(全部のパターン合致している)ことを確認する。
					if(	preg_match($pattern,$subject,$match)	){
						if(	isset($match[0])	){


							//デバグ用
							//$matchCommand[]="preg_match(\"".$pattern."\",\"".$subject."\",\"".$match[0]."\"),そのときの\$jitensha:".$jitensha[$addColum];

							//配列の最後判定、foreachが回りきってるということ(array_key_lastがphp7.3以降でしか使えないことに注意)
							if(	$key === array_key_last($modificationPatternKey)	){
								//車種確定フラグたて
								$inputArray[$i]+=array(	$addColum=>$jitensha[$addColum]	);

								//デバグ用
								//$matchCommand[]="フラグ！！！！\r\n";

								break 2;									//確定すると次の車種に飛ぶ

							}
						}else{
							break;
						}
					}else{
						break;
					}
				}
				//$inputArray[$i]+=array(	$addColum=>0	);					//ループがまわり切った場合(該当車種がなかった場合0を入れる)
			}
		}

		//デバグ用
		//
	/*	
		$putPrintR->array=$matchCommand;
		$putPrintR->filename="matchCommandList.txt";
		$putPrintR->go();
	 */	

		//db書き込みの前準備
		//


		$recordTable=$this->recordTable;
		$result=$this->db->wrightAll($recordTable,$inputArray);
		

		return $inputArray;
	}
}		
			//スクレイピング結果と車種インデックスの連携その2(テスト)mysql上で行なう

/*
//resultを受け取ってdocumentExtractionクラス(抜き出し、置き換え)でできない修正をする
//(要は細かいつじつま合わせ)
class	modification{
	public	$subjectName;			//subject名	
	public	$inputArray;			//入力配列
	public	$cycleNameList;			//車種名リスト

	//商品名(車名)の確定
	function	productNameDiscrimination(){
		$cycleNameList=$this->cycleNameList;
		$inputArray=$this->inputArray;
		$subject=$this->productNameSubject;

		$result=array();

		foreach($inputArray	as	$kobetu){




		}

		return $result;
	}
}
 */

//db取扱
class	db{
	public	$db;
	public	$host;
	public	$dbName;
	public	$dbUser;
	public	$dbPass;
	public	$PDO;
	public	$dbParameter;

	//wrightAll時の既定パラメーター
	private	$dt;	//DateTime
	private	$wrightAllTableName;
	private	$wrightAllInputArray;

	//db基本情報読み込み
	function	__construct(){
		$this->dbParameter=new dbParameter();
		$this->db=$this->dbParameter->db;
		$this->host=$this->dbParameter->host;
		$this->dbName=$this->dbParameter->dbName;
		$this->dbUser=$this->dbParameter->dbUser;
		$this->dbPass=$this->dbParameter->dbPass;

		$this->dt=new DateTime();

		//全書き込み入力配列設定
		$this->wrightAllInputArray=null;	//デフォでnull。
		//全書き込みデフォルトテーブル名設定
		$today=$this->dt->format("Ymd");
		$this->wrightAllTableName="uknown".$today;	//未設定の場合の既定テーブル名
		$this->putPrintR=new putPrintR();

	}
	
	//dbオープン
	function	open(){

		$db=$this->db;
		$host=$this->host;
		$dbName=$this->dbName;
		$dbUser=$this->dbUser;
		$dbPass=$this->dbPass;
		//エラーチェック
		if(	!isset($db)	){
			print "empty db	false!\r\n";
			return false;
		}
		if(	!isset($host)	){
			print "empty host	false!\r\n";
			return false;
		}
		if(	!isset($dbName)	){
			print "empty dbName	false!\r\n";
			return false;
		}
		if(	!isset($dbUser)	){
			print "empty dbUser	false!\r\n";
			return false;
		}
		if(	!isset($dbPass)	){
			print "empty dbPass	false!\r\n";
			return false;
		}
		try{
			$this->PDO=new PDO($db.":host=".$host.";dbname=".$dbName,$dbUser,$dbPass);
		}catch(PDOException $error){
			print "connect false!\r\n";
			print $error->getMessage();
			return false;
		}
		print "connect complete\r\n";
		return true;
	}
	//読込、指定テーブル名のfetchAllを結果として吐き出す。
	//そもそもfetchAllを大分忘れているのでおためし用
	//正規表現のチェックのためのsortなので基本DESC(そしてチェック後はループを抜ける)
	function	readAll($tableName,$sortColumn){
		if(	is_array($sortColumn)	){									//ソート用のカラムが配列(複数だった場合)
			$query2="";
			foreach($sortColumn	as $str){
				$query2=$query2."LENGTH(".$str.") DESC,";
			}
			$query2=rtrim($query2,",");
		}else{
			$query2="LENGTH(".$sortColumn.") DESC";	//ソート用のカラムが文字列だった場合。それ以外は面倒だから判別しない
		}

			
		$query='SELECT * FROM '.$tableName." ORDER BY ".$query2;
		print $query;
		print "\r\n";

		$stmt=$this->PDO->query($query);
		$result=$stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

	//全書き込み、指定配列をテーブルに書き込む
	//
	//引数がひとつだけの場合は
	function	wrightAll($recordTable=null,$inputArray=null){
		$PDO=$this->PDO;
		$putPrintR=$this->putPrintR;

		//引数チェック
		if(	is_null($recordTable)	||	is_null($inputArray)	){
			//引数が設定されていない場合
			print "no parameter error!!\r\n";
			exit();
		}

		$tableName=$recordTable["tableName"];
		$dateFlag=$recordTable["dateFlag"];
		$outputDataColumn=$recordTable["outputDataColumn"];

		if(	is_bool($dateFlag)	&&	$dateFlag===true	){
			$recordDateColumn="recordDate";					//当日日付カラム(カラム名は固定)
			$today="CURDATE()";					//当日日付。
		}

		//
		foreach(	$inputArray	as	$jitenshaData	){

			$sql=$this->makeInsertStmt($tableName,$dateFlag,$outputDataColumn);
			$stmt=$PDO->prepare($sql);

			/*
			$putPrintR->array=$outputDataColumn;
			$putPrintR->filename="outputDataColumn.txt";
			$putPrintR->go();

			$putPrintR->array=$jitenshaData;
			$putPrintR->filename="jitenshaData.txt";
			$putPrintR->go();
			*/


			//index_noが無い(車種確定ができなかった)ものには0をつけてやる
			//↑暫定処理
			if(	!array_key_exists("index_no",$jitenshaData)	){
				$jitenshaData+=array("index_no"=>0);
			}
			//print_r($jitenshaData);
			//ここまで暫定処理


			$exArray=array_combine($outputDataColumn,$jitenshaData);
			

			if(	$exArray==false	){
				print "array_combine error!\r\n";
				exit();
			}
			$result=$stmt->execute($exArray);
		}

		return $result;


	}
	
	//INSERT文を作るだけのメソッド
	function	makeInsertStmt($tableName,$dateFlag,$outputDataColumn){
		$sql="INSERT INTO ".$tableName." (";
		if(	$dateFlag===true	){
			$outputDataColumn[]="YMD";
		}


		foreach(	$outputDataColumn	as	$i	){
			$sql=$sql.$i.",";
		}
		$sql=rtrim($sql,",").") VALUES(";

		foreach(	$outputDataColumn	as	$j	){
			if(	$j!="YMD"	){
				$sql=$sql.":".$j.",";
			}else{
				$sql=$sql."CURDATE(),";
			}

		}
		$sql=rtrim($sql,",").")";

		//print $sql."\r\n";

		return $sql;	
	}


	//dbクローズ
	function	close(){
		print "db close\r\n";
		$this->PDO=null;
	}
}

?>
