<?php

function smarty_modifier_rating($rating){
	if ($rating==0) {
		$html = '<span style="color:gray;">0</span>';
	} elseif ($rating>0){
		$html = '<span style="color:green">+'.$rating.'</span>';
	} else {
		$html = '<span style="color:red">'.$rating.'</span>';
	}
	return $html;
}