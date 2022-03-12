<?php
//**********************************************************************
// メール項目
//
//  tag			: 項目名
//  type		: 入力タイプ
//  req			: 入力必須項目
//  maxlength	: 最大文字数　text, textarea
//  placeholder	: placeholder表示する場合は表示する文字列をセット
//  check		: 入力値適合チェック
//					（ kana=カタカナひらがな　zip=郵便番号　tel=電話番号　email=メールアドレス等）
//
//**********************************************************************
$ITEMS_ARRAY = array(
	'your_name'	=> array(
					'tag'=>'お名前',
					'type'=>'text',
					'require'=>'yes',
					'maxlength'=> 128,
	),
	
	'tel_no'	=> array(
					'tag'=>'電話番号',
					'type'=>'tel',
					'require'=>'yes',
					'maxlength'=> 16,
					/*'placeholder'=>'例）03-3548-3010',*/
					'check'=>'tel'
	),
	
	'mail_addr'	=> array(
					'tag'=>'メールアドレス',
					'type'=>'email',
					'require'=>'yes',
					'maxlength'=> 128,
					/*'placeholder'=>'例）sample@jara.co.jp',*/
					'check'=>'email'
	),
	
	'cmt_body'		=> array(
					'tag'=>'お問い合わせ内容',
					'type'=>'textarea',
					'require'=>'yes',
					'maxlength'=>1024
	)
);