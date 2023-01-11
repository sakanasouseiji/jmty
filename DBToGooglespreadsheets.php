<?php



class	DBToGooglespreadsheets{
	public	$key;
	public	$spreadsheetID;
	public	$range;

	public	$values;
	function	add(){
		
		/*
		 * googleスプレッドシート追加のサンプル
		 *実行前：
		 * ---------------
		 * 1.まだ行っていない場合は、Google SheetsAPIを有効にします
		 *でプロジェクトの割り当てを確認してください
		 * https://console.developers.google.com/apis/api/sheets
		 * 2.Composerを使用してPHPクライアントライブラリをインストールします。インストールを確認してください
		 * https：//github.com/google/google-api-php-clientの手順。
		 */

		// Composerを自動ロードします。
		require_once(__DIR__.'/vendor/autoload.php'); 

		//$client = getClient （）;

		$key = __DIR__.'/tunakan-455ecb1b14d9.json';//取得したサービスキーのパスを指定
		 
		$client = new Google_Client();//Googleクライアントインスタンスを作成
		$client->setScopes([//スコープを以下の内容でセット
				\Google_Service_Sheets::SPREADSHEETS,
					\Google_Service_Sheets::DRIVE,]);
		$client->setAuthConfig($key);//サービスキーをセット

		$service = new Google_Service_Sheets($client);  

		//更新するスプレッドシートのID。
		//$SpreadsheetId = 'my-spreadsheet-id' ; // TODO：プレースホルダー値を更新します。   
		$SpreadsheetId = '1Rgvjpy9AKXUBR7VPr-Hlorr9osnCqs5pk640rI2pAbQ';// TODO：プレースホルダー値を更新します。

		//データの論理テーブルを検索するための範囲のA1表記。
		//値はテーブルの最後の行の後に追加されます。
		//$range = 'my-range' ; // TODO：プレースホルダー値を更新します。   
		$range = 'cyclespotKakakuResult';// TODO：プレースホルダー値を更新します。

		// TODO： `requestBody`の目的のプロパティに値を割り当てます：

		$values = [
		　["A1セルに書き込む内容", "B1セルに書き込む内容", "C1セルに書き込む内容"],
		　["A2セルに書き込む内容", "B2セルに書き込む内容", "C2セルに書き込む内容"],
		　["A3セルに書き込む内容", "B3セルに書き込む内容", "C3セルに書き込む内容"]
		];
		 */
		//
		////書き込む内容を収めた配列をbodyに格納
		$requestBody = new Google_Service_Sheets_ValueRange(['values' => $values,]);


		$response = $service->Spreadsheets_values->append($SpreadsheetId,$range,$requestBody);

		// TODO：以下のコードを変更して `response`オブジェクトを処理します：
		echo '<pre>',var_export($response,true),'</pre>',"\n";   

		//関数getClient （）{ 
			// TODO：以下のプレースホルダーを変更して、認証資格情報を生成します。見る
			// https://developers.google.com/sheets/quickstart/php#step_3_set_up_the_sample
			//
			//次のスコープのいずれかを使用して承認します。
			//'https://www.googleapis.com/auth/drive '
			//'https://www.googleapis.com/auth/drive.file '
			//'https://www.googleapis.com/auth/spreadsheets '
			//nullを返す; 
		//}
	}
}
