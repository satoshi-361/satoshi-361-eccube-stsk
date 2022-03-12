<?php 

require_once('tcpdf.php');
require_once('fpdi/autoload.php');
 
$pdf = new setasign\Fpdi\Tcpdf\Fpdi();
 
$pdf->SetMargins(0, 0, 0); //�}�[�W������
$pdf->SetAutoPageBreak(false); //�������y�[�W����
$pdf->setPrintHeader(false); //�w�b�_�[����
$pdf->setPrintFooter(false); //�t�b�^�[����

$page = $pdf->setSourceFile('template.pdf');

//�ŏ���1�y�[�W���擾
$pdf->AddPage('P'); //P�F�c(����Ȃ̂Ŏw�肵�Ȃ��Ă��悢)/L�F��
$first = $pdf->importPage(1);
$pdf->useTemplate($first);

//1�y�[�W�ڂɕ����ǉ�
$pdf->SetFont('kozminproregular', '', 10); //�t�H���g�̐ݒ�
$pdf->SetTextColor(0, 0, 0); //�����F
//���t
$pdf->SetXY(45, 50);
$pdf->Write(10, date('Y�Nn��j��'));
//����
$pdf->SetXY(120, 50);
$pdf->Write(10, $_POST['name']);

//2�y�[�W�ڍs���擾
for ($i = 2; $i <= $page; $i++) {
    $pdf->AddPage();
    $tpl = $pdf->importPage($i);
    $pdf->useTemplate($tpl);
}

//��ʂɕ\��������ꍇ�́A������
$pdf->Output('');

//�_�E�����[�h����ꍇ�́A������
//���̗Ⴞ��download.pdf�Ƃ����t�@�C�����Ńt�@�C�����_�E�����[�h�����
$pdf->Output('download', 'd');

?>