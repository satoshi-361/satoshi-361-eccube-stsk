<?php 

require_once('tcpdf.php');
require_once('fpdi/autoload.php');
 
$pdf = new setasign\Fpdi\Tcpdf\Fpdi();
 
$pdf->SetMargins(0, 0, 0); //マージン無効
$pdf->SetAutoPageBreak(false); //自動改ページ無効
$pdf->setPrintHeader(false); //ヘッダー無効
$pdf->setPrintFooter(false); //フッター無効

$page = $pdf->setSourceFile('template.pdf');

//最初の1ページを取得
$pdf->AddPage('P'); //P：縦(既定なので指定しなくてもよい)/L：横
$first = $pdf->importPage(1);
$pdf->useTemplate($first);

//1ページ目に文字追加
$pdf->SetFont('kozminproregular', '', 10); //フォントの設定
$pdf->SetTextColor(0, 0, 0); //文字色
//日付
$pdf->SetXY(45, 50);
$pdf->Write(10, date('Y年n月j日'));
//氏名
$pdf->SetXY(120, 50);
$pdf->Write(10, $_POST['name']);

//2ページ移行を取得
for ($i = 2; $i <= $page; $i++) {
    $pdf->AddPage();
    $tpl = $pdf->importPage($i);
    $pdf->useTemplate($tpl);
}

//画面に表示させる場合は、こちら
$pdf->Output('');

//ダウンロードする場合は、こちら
//この例だとdownload.pdfというファイル名でファイルがダウンロードされる
$pdf->Output('download', 'd');

?>