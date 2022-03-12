<?php
    require_once('./TCPDF/tcpdf.php');
    require_once('./TCPDF/FPDI/autoload.php');
        
    $pdf = new setasign\Fpdi\Tcpdf\Fpdi();
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $page = $pdf->setSourceFile('./template.pdf');
    
    $pdf->AddPage('P');
    $first = $pdf->importPage(1);
    $pdf->useTemplate($first);
    
    // 文字色
    $pdf->SetTextColor(0, 0, 0);
    // フォント、文字サイズ指定
    $pdf->SetFont('kozminproregular', '', 18); 

    // 氏名(ログインしている場合)
    if (!empty($_POST['order']['companyName'])) {
        $pdf->SetXY(30, 76);
        $pdf->Write(10, $_POST['order']['companyName']);    
    }
    if (!empty($_POST['order']['userName'])) {
        $pdf->SetXY(30, 85);
        $pdf->Write(10, $_POST['order']['userName']);    
    }

    // 見積金額
    $pdf->SetXY(78, 122);
    $pdf->Write(10, number_format($_POST['order']['totalAmount']) . ' 円' );

    // フォントサイズ変更
    $pdf->SetFont('kozminproregular', '', 10);
    // 日付
    $pdf->SetXY(40, 48);
    $pdf->Write(10, date('Y年n月j日'));

    /* 商品 */ 
    {
        // 商品名
        $width = mb_strwidth($_POST['order']['productName'], 'UTF-8');
        if ($width >= 36) {
            // フォントサイズ変更
            $pdf->SetFont('kozminproregular', '', 8);
            $productName = mb_str_split($_POST['order']['productName'], 20 , 'UTF-8');
            if (empty($productName[1])) {
                $pdf->SetXY(30, 171);
                $pdf->Write(10, $_POST['order']['productName']);         
            } else {
                $pdf->SetXY(30, 171);
                $pdf->Write(10, $productName[0]);
                $pdf->SetXY(30, 171);
                $dispName = empty($productName[2]) ? $productName[1] : $productName[1].$productName[2];
                $pdf->Write(10, $dispName);    
            }
            // フォントサイズ変更
            $pdf->SetFont('kozminproregular', '', 10);
        } else {
            $pdf->SetXY(30, 171);
            $pdf->Write(10, $_POST['order']['productName']);
        }
        // 数量
        // $pdf->SetXY(103, 171);
        // $pdf->Write(10, $_POST['order']['totalQuantity']);
        // 単価
        // $pdf->SetXY(124, 171);
        // $pdf->Write(10, number_format($_POST['order']['productPrice']));
// print_r(number_format($_POST['order']['totalQuantity'])); exit;
        $pdf->SetXY(95, 172);
        $pdf->Cell(20,8,number_format($_POST['order']['totalQuantity']),0,0,'C');
        $pdf->Cell(40,8,number_format($_POST['order']['productPrice']),0,0,'C');
        $pdf->Cell(25,8,number_format($_POST['order']['totalQuantity'] * $_POST['order']['productPrice']),0,0,'R');

        // 商品合計（税抜）
        // $pTotal = number_format($_POST['order']['totalQuantity'] * $_POST['order']['productPrice']);
        // $pdf->SetXY(165, 171);
        // $pdf->Write(10, $pTotal);
    }

    /* 版代 */
    {
        // 項目名
        $pdf->SetXY(30, 179);
        $pdf->Write(10, '版代');

        // 数量
        // $pdf->SetXY(103, 179);
        // $pdf->Write(10, '1');
        // 単価
        // $pdf->SetXY(124, 179);
        // $pdf->Write(10, number_format($_POST['order']['dataPlacementFee']));

        $pdf->SetXY(95, 180);
        $pdf->Cell(20,8,'1',0,0,'C');
        $pdf->Cell(40,8,number_format($_POST['order']['dataPlacementFee']),0,0,'C');
        $pdf->Cell(25,8,number_format('1' * $_POST['order']['dataPlacementFee']),0,0,'R');

        // 版代合計（税抜）
        // $dPFTotal = number_format('1' * $_POST['order']['dataPlacementFee']);
        // $pdf->SetXY(165, 179);
        // $pdf->Write(10, $dPFTotal);
    }
    /* 印刷代 */
    {
        // 項目名
        $pdf->SetXY(30, 188);
        $pdf->Write(10, '印刷代');
        // 数量
        // $pdf->SetXY(103, 187);
        // $pdf->Write(10, $_POST['order']['printingFeeQuantity']);
        // 単価
        // $pdf->SetXY(124, 187);
        // $pdf->Write(10, number_format($_POST['order']['printingFee']));

        $pdf->SetXY(95, 189);
        $pdf->Cell(20,8,$_POST['order']['printingFeeQuantity'],0,0,'C');
        $pdf->Cell(40,8,number_format($_POST['order']['printingFee']),0,0,'C');
        $pdf->Cell(25,8,number_format($_POST['order']['printingFeeQuantity'] * $_POST['order']['printingFee']),0,0,'R');

        // 印刷合計（税抜）
        // $pFTotal = number_format($_POST['order']['printingFeeQuantity'] * $_POST['order']['printingFee']);
        // $pdf->SetXY(165, 187);
        // $pdf->Write(10, $pFTotal);

    }
    /* 送料 */
    {
        // 項目名
        $pdf->SetXY(30, 196);
        $pdf->Write(10, '送料');
        // 数量
        // $pdf->SetXY(103, 195);
        // $pdf->Write(10, '1');
        // 単価
        // $pdf->SetXY(124, 195);
        // $pdf->Write(10, number_format($_POST['order']['shipmentFee']));

        $pdf->SetXY(95, 197);
        $pdf->Cell(20,8,'1',0,0,'C');
        $pdf->Cell(40,8,number_format($_POST['order']['shipmentFee']),0,0,'C');
        $pdf->Cell(25,8,number_format($_POST['order']['shipmentFee'] * 1),0,0,'R');

        // 送料合計（税抜）
        // $shipmentTotal = number_format($_POST['order']['shipmentFee'] * 1);
        // $pdf->SetXY(165, 195);
        // $pdf->Write(10, $shipmentTotal);
    }
    // 小計（税抜）
    // $pdf->SetXY(165, 219);
    // $pdf->Write(10, number_format($_POST['order']['zeinuki']));
    $pdf->SetXY(155, 222);
    $pdf->Cell(25,8,number_format($_POST['order']['zeinuki']) . ' 円',0,0,'R');
    // 消費税（10%）
    // $pdf->SetXY(165, 227);
    // $pdf->Write(10, number_format($_POST['order']['tax']));
    $pdf->SetXY(155, 230);
    $pdf->Cell(25,8,number_format($_POST['order']['tax']) . ' 円',0,0,'R');
    // 合計（税込）
    // $pdf->SetXY(165, 235);
    // $pdf->Write(10, number_format($_POST['order']['totalAmount']) . ' 円' );
    $pdf->SetXY(155, 239);
    $pdf->Cell(25,8,number_format($_POST['order']['totalAmount']) . ' 円',0,0,'R');
         
    $fileName = mb_convert_encoding(" 見積概算書.pdf", 'SJIS-WIN', 'auto');
    $pdf->Output($fileName, 'd');
?>